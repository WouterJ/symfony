<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Guard\Provider;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AuthenticatorInterface;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;
use Symfony\Component\Security\Guard\Token\GuardTokenInterface;
use Symfony\Component\Security\Guard\Token\PreAuthenticationGuardToken;

/**
 * @author Ryan Weaver <ryan@knpuniversity.com>
 *
 * @internal
 */
trait GuardAuthenticationProviderTrait
{
    private function authenticateViaGuard(AuthenticatorInterface $guardAuthenticator, PreAuthenticationGuardToken $token): GuardTokenInterface
    {
        // get the user from the GuardAuthenticator
        $user = $guardAuthenticator->getUser($token->getCredentials(), $this->userProvider);

        if (null === $user) {
            throw new UsernameNotFoundException(sprintf('Null returned from %s::getUser()', \get_class($guardAuthenticator)));
        }

        if (!$user instanceof UserInterface) {
            throw new \UnexpectedValueException(sprintf('The %s::getUser() method must return a UserInterface. You returned %s.', \get_class($guardAuthenticator), \is_object($user) ? \get_class($user) : \gettype($user)));
        }

        $this->userChecker->checkPreAuth($user);
        if (true !== $checkCredentialsResult = $guardAuthenticator->checkCredentials($token->getCredentials(), $user)) {
            if (false !== $checkCredentialsResult) {
                @trigger_error(sprintf('%s::checkCredentials() must return a boolean value. You returned %s. This behavior is deprecated in Symfony 4.4 and will trigger a TypeError in Symfony 5.', \get_class($guardAuthenticator), \is_object($checkCredentialsResult) ? \get_class($checkCredentialsResult) : \gettype($checkCredentialsResult)), E_USER_DEPRECATED);
            }

            throw new BadCredentialsException(sprintf('Authentication failed because %s::checkCredentials() did not return true.', \get_class($guardAuthenticator)));
        }
        if ($this->userProvider instanceof PasswordUpgraderInterface && $guardAuthenticator instanceof PasswordAuthenticatedInterface && null !== $this->passwordEncoder && (null !== $password = $guardAuthenticator->getPassword($token->getCredentials())) && method_exists($this->passwordEncoder, 'needsRehash') && $this->passwordEncoder->needsRehash($user)) {
            $this->userProvider->upgradePassword($user, $this->passwordEncoder->encodePassword($user, $password));
        }
        $this->userChecker->checkPostAuth($user);

        // turn the UserInterface into a TokenInterface
        $authenticatedToken = $guardAuthenticator->createAuthenticatedToken($user, $this->providerKey);
        if (!$authenticatedToken instanceof TokenInterface) {
            throw new \UnexpectedValueException(sprintf('The %s::createAuthenticatedToken() method must return a TokenInterface. You returned %s.', \get_class($guardAuthenticator), \is_object($authenticatedToken) ? \get_class($authenticatedToken) : \gettype($authenticatedToken)));
        }

        return $authenticatedToken;
    }

    private function findOriginatingAuthenticator(PreAuthenticationGuardToken $token): ?AuthenticatorInterface
    {
        // find the *one* GuardAuthenticator that this token originated from
        foreach ($this->guardAuthenticators as $key => $guardAuthenticator) {
            // get a key that's unique to *this* guard authenticator
            // this MUST be the same as GuardAuthenticationListener
            $uniqueGuardKey = $this->getGuardKey($key);

            if ($uniqueGuardKey === $token->getGuardProviderKey()) {
                return $guardAuthenticator;
            }
        }

        // no matching authenticator found - but there will be multiple GuardAuthenticationProvider
        // instances that will be checked if you have multiple firewalls.

        return null;
    }

    abstract protected function getGuardKey(string $key): string;
}
