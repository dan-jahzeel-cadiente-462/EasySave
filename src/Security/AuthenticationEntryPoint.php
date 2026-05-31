<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class AuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): RedirectResponse
    {
        $path = $request->getPathInfo();

        // If the user is trying to access an admin route, send them to admin login
        if (str_starts_with($path, '/admin')) {
            return new RedirectResponse($this->urlGenerator->generate('app_admin_login'));
        }

        // Default to the main login page
        return new RedirectResponse($this->urlGenerator->generate('app_user_login'));
    }
}
