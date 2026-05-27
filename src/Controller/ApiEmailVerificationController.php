<?php

namespace App\Controller;

use App\Service\EmailVerificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/api')]
class ApiEmailVerificationController extends AbstractController
{
    public function __construct(
        private EmailVerificationService $emailVerificationService,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository
    ) {
    }

    /**
     * Verify email with token (Mobile App)
     * POST /api/verify-email
     * 
     * For mobile apps: After receiving email, extract token and call this endpoint
     */
    #[Route('/verify-email', name: 'api_verify_email', methods: ['POST'])]
    public function verifyEmail(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;

        if (!$token) {
            return $this->json([
                'success' => false,
                'message' => 'Verification token is required',
                'code' => 'MISSING_TOKEN'
            ], 400);
        }

        try {
            $user = $this->userRepository->findOneBy(['verificationToken' => $token]);

            if (!$user) {
                return $this->json([
                    'success' => false,
                    'message' => 'Invalid or expired verification token',
                    'code' => 'INVALID_TOKEN'
                ], 400);
            }

            // Mark user as verified
            $user->setIsVerified(true);
            $user->setVerificationToken(null); // Clear the token after use
            $this->entityManager->flush();

            try {
                $this->emailVerificationService->sendConfirmationEmail($user);
            } catch (\Exception $emailException) {
                error_log('API confirmation email send failed: ' . $emailException->getMessage());
            }

            return $this->json([
                'success' => true,
                'message' => 'Email verified successfully',
                'code' => 'VERIFIED',
                'user' => [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'isVerified' => $user->isVerified(),
                    'roles' => $user->getRoles()
                ]
            ], 200);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Verification failed: ' . $e->getMessage(),
                'code' => 'VERIFICATION_ERROR'
            ], 500);
        }
    }

    /**
     * Resend verification email (Mobile App)
     * POST /api/resend-verification
     * 
     * Requires JWT authentication
     */
    #[Route('/resend-verification', name: 'api_resend_verification', methods: ['POST'])]
    public function resendVerification(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Authentication required',
                'code' => 'UNAUTHORIZED'
            ], 401);
        }

        if ($this->emailVerificationService->isExemptFromEmailVerification($user)) {
            return $this->json([
                'success' => false,
                'message' => 'Your account does not require email verification (Admin/Staff)',
                'code' => 'EXEMPT_FROM_VERIFICATION'
            ], 400);
        }

        if ($user->isVerified()) {
            return $this->json([
                'success' => false,
                'message' => 'Email is already verified',
                'code' => 'ALREADY_VERIFIED'
            ], 400);
        }

        try {
            // Generate new token
            $verificationToken = $this->emailVerificationService->generateVerificationToken();
            $user->setVerificationToken($verificationToken);
            $this->entityManager->flush();

            // Create verification URL (for web verification or deep link)
            $verificationUrl = $this->generateUrl(
                'app_verify_email',
                ['token' => $verificationToken],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            // Send email
            $this->emailVerificationService->sendVerificationEmail($user, $verificationUrl);

            return $this->json([
                'success' => true,
                'message' => 'Verification email sent successfully',
                'code' => 'EMAIL_SENT',
                'data' => [
                    'email' => $user->getEmail(),
                    'tokenExpiration' => '24 hours',
                    'deepLink' => $verificationUrl  // For mobile app deep linking
                ]
            ], 200);
        } catch (\Exception $e) {
            error_log('API verification email send failed: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'message' => 'Failed to send verification email',
                'code' => 'EMAIL_SEND_FAILED',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check verification status (Mobile App)
     * GET /api/verification-status
     * 
     * Requires JWT authentication
     */
    #[Route('/verification-status', name: 'api_verification_status', methods: ['GET'])]
    public function verificationStatus(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Authentication required',
                'code' => 'UNAUTHORIZED'
            ], 401);
        }

        return $this->json([
            'success' => true,
            'message' => 'Verification status retrieved',
            'code' => 'STATUS_OK',
            'data' => [
                'userId' => $user->getId(),
                'email' => $user->getEmail(),
                'isVerified' => $user->isVerified(),
                'isExempt' => $this->emailVerificationService->isExemptFromEmailVerification($user),
                'exemptReason' => $this->emailVerificationService->isExemptFromEmailVerification($user) 
                    ? 'Admin or Staff account' 
                    : null,
                'roles' => $user->getRoles()
            ]
        ], 200);
    }

    /**
     * Development/Testing endpoint - Get verification token for testing
     * GET /api/dev/verification-token?email=user@example.com
     * 
     * DEVELOPMENT ONLY - Must have APP_ENV=dev
     * Used for testing mobile app without actual email delivery
     */
    #[Route('/dev/verification-token', name: 'api_dev_verification_token', methods: ['GET'])]
    public function devGetVerificationToken(Request $request): JsonResponse
    {
        // Only available in development mode
        if ($_ENV['APP_ENV'] !== 'dev' && $_ENV['APP_ENV'] !== 'test') {
            return $this->json([
                'success' => false,
                'message' => 'This endpoint is only available in development mode',
                'code' => 'NOT_AVAILABLE'
            ], 403);
        }

        $email = $request->query->get('email');
        if (!$email) {
            return $this->json([
                'success' => false,
                'message' => 'Email parameter is required',
                'code' => 'MISSING_EMAIL'
            ], 400);
        }

        try {
            $user = $this->userRepository->findOneBy(['email' => $email]);
            
            if (!$user) {
                return $this->json([
                    'success' => false,
                    'message' => 'User not found',
                    'code' => 'USER_NOT_FOUND'
                ], 404);
            }

            if ($user->isVerified()) {
                return $this->json([
                    'success' => false,
                    'message' => 'User email is already verified',
                    'code' => 'ALREADY_VERIFIED'
                ], 400);
            }

            return $this->json([
                'success' => true,
                'message' => 'Verification token retrieved (Dev Mode Only)',
                'code' => 'DEV_TOKEN_RETRIEVED',
                'warning' => '⚠️ This endpoint is for DEVELOPMENT ONLY - Do not expose in production',
                'data' => [
                    'email' => $user->getEmail(),
                    'verificationToken' => $user->getVerificationToken(),
                    'verificationUrl' => $this->generateUrl(
                        'app_verify_email',
                        ['token' => $user->getVerificationToken()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                    'apiVerifyUrl' => '/api/verify-email'
                ]
            ], 200);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error retrieving token: ' . $e->getMessage(),
                'code' => 'ERROR'
            ], 500);
        }
    }

    /**
     * Development/Testing endpoint - Check email in mailbox
     * GET /api/dev/mailbox?email=user@example.com
     * 
     * Returns info about user's verification status and token (dev mode only)
     */
    #[Route('/dev/mailbox', name: 'api_dev_mailbox', methods: ['GET'])]
    public function devMailbox(Request $request): JsonResponse
    {
        // Only available in development mode
        if ($_ENV['APP_ENV'] !== 'dev' && $_ENV['APP_ENV'] !== 'test') {
            return $this->json([
                'success' => false,
                'message' => 'This endpoint is only available in development mode',
                'code' => 'NOT_AVAILABLE'
            ], 403);
        }

        $email = $request->query->get('email');
        if (!$email) {
            return $this->json([
                'success' => false,
                'message' => 'Email parameter is required',
                'code' => 'MISSING_EMAIL'
            ], 400);
        }

        try {
            $user = $this->userRepository->findOneBy(['email' => $email]);
            
            if (!$user) {
                return $this->json([
                    'success' => false,
                    'message' => 'User not found',
                    'code' => 'USER_NOT_FOUND'
                ], 404);
            }

            $verificationLink = null;
            $verificationToken = null;

            if ($user->getVerificationToken()) {
                $verificationToken = $user->getVerificationToken();
                $verificationLink = $this->generateUrl(
                    'app_verify_email',
                    ['token' => $verificationToken],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
            }

            return $this->json([
                'success' => true,
                'message' => 'User email status (Dev Mode Only)',
                'code' => 'DEV_MAILBOX_INFO',
                'data' => [
                    'email' => $user->getEmail(),
                    'isVerified' => $user->isVerified(),
                    'status' => $user->isVerified() ? 'VERIFIED' : 'PENDING',
                    'verificationToken' => $verificationToken,
                    'verificationLink' => $verificationLink,
                    'apiVerifyEndpoint' => 'POST /api/verify-email',
                    'apiVerifyPayload' => [
                        'token' => $verificationToken ?: 'none'
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'code' => 'ERROR'
            ], 500);
        }
    }
}