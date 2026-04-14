<?php

namespace App\Controller;

use App\Service\EmailVerificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EmailVerificationController extends AbstractController
{
    public function __construct(
        private EmailVerificationService $emailVerificationService
    ) {
    }

    /**
     * Verify email address using token from email link
     */
    #[Route('/verify-email/{token}', name: 'app_verify_email', methods: ['GET'])]
    public function verifyEmail(string $token): Response
    {
        $error = $this->emailVerificationService->verifyToken($token);

        if ($error !== null) {
            $this->addFlash('error', $error);
            return $this->redirectToRoute('app_login');
        }

        $this->addFlash('success', 'Your email has been verified successfully! You can now log in.');
        return $this->redirectToRoute('app_login');
    }

    /**
     * Request email verification (resend link)
     * This would typically be shown after registration or on a separate page
     */
    #[Route('/request-verification', name: 'app_request_verification', methods: ['GET', 'POST'])]
    public function requestVerification(): Response
    {
        // This is a placeholder - would need additional implementation
        // to check if user is authenticated and needs verification
        
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('email_verification/request.html.twig', [
            'user' => $user
        ]);
    }
}
