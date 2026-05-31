<?php

namespace App\Security;

use App\Entity\User;
use App\Service\EmailVerificationService;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function __construct(
        private EmailVerificationService $emailVerificationService,
    ) {
    }

    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // 1) Check if user account is deactivated
        if (!$user->isActive()) {
            throw new CustomUserMessageAuthenticationException('Your account has been deactivated. Please contact support.');
        }

        // 2) Check if user is admin or staff for admin login route
        // This validation applies during admin panel access
        $roles = $user->getRoles();
        if (!in_array('ROLE_ADMIN', $roles, true) && !in_array('ROLE_STAFF', $roles, true)) {
            // Only enforce this if accessing admin login - regular users accessing user login should not be blocked here
            // The route-level access control will prevent unauthorized access
        }

        // 3) Email verification: required for regular users; admins and staff exempt
        if (!$user->isVerified()) {
            $isGoogleOAuth = $user->getProvider() === 'google';
            $roles = $user->getRoles();
            $isAdministrative = in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_STAFF', $roles, true);

            if (!$isGoogleOAuth && !$isAdministrative) {
                throw new CustomUserMessageAuthenticationException('Please verify your email before logging in. Check your inbox for the verification link.');
            }
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Additional post-authentication checks can be added here if needed
    }
}
