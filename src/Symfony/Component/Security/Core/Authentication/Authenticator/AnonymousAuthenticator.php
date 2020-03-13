<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Core\Authentication\Authenticator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author Wouter de Jong <wouter@wouterj.nl>
 */
class AnonymousAuthenticator implements AuthenticatorInterface
{
    private $secret;
    private $tokenStorage;

    public function __construct(string $secret, TokenStorageInterface $tokenStorage)
    {
        $this->secret = $secret;
        $this->tokenStorage = $tokenStorage;
    }

    public function supports(Request $request): ?bool
    {
        // do not overwrite already stored tokens (i.e. from the session)
        return null === $this->tokenStorage->getToken();
    }

    public function getCredentials(Request $request)
    {
        return [];
    }

    public function getUser($credentials): ?UserInterface
    {
        return new User('anon.', null);
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return true;
    }

    public function createAuthenticatedToken(UserInterface $user, string $providerKey): TokenInterface
    {
        return new AnonymousToken($this->secret, 'anon.', []);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey): ?Response
    {
        return null;
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }
}
