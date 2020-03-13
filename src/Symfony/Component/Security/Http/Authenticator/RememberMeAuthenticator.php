<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Http\Authenticator\Token;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\RememberMe\AbstractRememberMeServices;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategy;

/**
 * The RememberMe *Authenticator* performs remember me authentication.
 *
 * This authenticator is executed whenever a user's session
 * expired and a remember me cookie was found. This authenticator
 * then "re-authenticates" the user using the information in the
 * cookie.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 * @author Wouter de Jong <wouter@wouterj.nl>
 *
 * @final
 */
class RememberMeAuthenticator implements AuthenticatorInterface
{
    private $rememberMeServices;
    private $secret;
    private $tokenStorage;
    private $options;
    private $sessionStrategy;

    public function __construct(AbstractRememberMeServices $rememberMeServices, string $secret, TokenStorageInterface $tokenStorage, array $options, ?SessionAuthenticationStrategy $sessionStrategy = null)
    {
        $this->rememberMeServices = $rememberMeServices;
        $this->secret = $secret;
        $this->tokenStorage = $tokenStorage;
        $this->options = $options;
        $this->sessionStrategy = $sessionStrategy;
    }

    public function supports(Request $request): ?bool
    {
        // do not overwrite already stored tokens (i.e. from the session)
        if (null !== $this->tokenStorage->getToken()) {
            return false;
        }

        if (($cookie = $request->attributes->get(AbstractRememberMeServices::COOKIE_ATTR_NAME)) && null === $cookie->getValue()) {
            return false;
        }

        if (!$request->cookies->has($this->options['name'])) {
            return false;
        }

        // the `null` return value indicates that this authenticator supports lazy firewalls
        return null;
    }

    public function getCredentials(Request $request)
    {
        return [
            'cookie_parts' => explode(AbstractRememberMeServices::COOKIE_DELIMITER, base64_decode($request->cookies->get($this->options['name']))),
            'request' => $request,
        ];
    }

    /**
     * @param array $credentials
     */
    public function getUser($credentials): ?UserInterface
    {
        return $this->rememberMeServices->performLogin($credentials['cookie_parts'], $credentials['request']);
    }

    public function createAuthenticatedToken(UserInterface $user, string $providerKey): TokenInterface
    {
        return new RememberMeToken($user, $providerKey, $this->secret);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->rememberMeServices->loginFail($request, $exception);

        return null;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey): ?Response
    {
        if ($request->hasSession() && $request->getSession()->isStarted()) {
            $this->sessionStrategy->onAuthentication($request, $token);
        }

        return null;
    }
}
