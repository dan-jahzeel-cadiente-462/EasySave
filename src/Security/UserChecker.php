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

        error_log("[UserChecker] PreAuth check for: " . $user->getUsername() . " (ID: " . $user->getId() . ")");

        // 1) Check if user account is deactivated
        if (!$user->isActive()) {
            error_log("[UserChecker] Failed: User deactivated");
            throw new CustomUserMessageAuthenticationException('Your account has been deactivated. Please contact support.');
        }

        // 2) Check roles
        $roles = $user->getRoles();
        error_log("[UserChecker] User Roles: " . implode(', ', $roles));

        // 3) Email verification: required for regular users; admins and staff exempt
        if (!$user->isVerified()) {
            error_log("[UserChecker] User not verified. Checking exemptions.");
            $isGoogleOAuth = $user->getProvider() === 'google';
            $isAdministrative = in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_STAFF', $roles, true);

            if (!$isGoogleOAuth && !$isAdministrative) {
                error_log("[UserChecker] Failed: Not verified and not exempt.");
                throw new CustomUserMessageAuthenticationException('Please verify your email before logging in. Check your inbox for the verification link.');
            }
            error_log("[UserChecker] Exempt from verification.");
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
