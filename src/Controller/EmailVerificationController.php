<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailVerificationController extends AbstractController
{
    public function __construct(
        private EmailVerificationService $emailVerificationService,
        private UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Verify email address using token from email link
     */
    #[Route('/verify-email/{token}', name: 'app_verify_email', methods: ['GET'])]
    public function verifyEmail(string $token): Response
    {
        $user = $this->emailVerificationService->verifyToken($token);

        if (!$user) {
            $this->addFlash('error', 'Invalid or expired verification token.');
            return $this->redirectToRoute('app_user_login');
        }

        try {
            $this->emailVerificationService->sendConfirmationEmail($user);
        } catch (\Exception $e) {
            // Log error but don't fail the verification
            $this->addFlash('warning', 'Your email has been verified, but the confirmation email could not be sent.');
        }

        $this->addFlash('success', 'Your email has been verified successfully! You can now log in.');
        return $this->redirectToRoute('app_user_login');
    }

    /**
     * Request email verification (resend link)
     */
    #[Route('/request-verification', name: 'app_request_verification', methods: ['GET', 'POST'])]
    public function requestVerification(Request $request): Response
    {
        // Allow unauthenticated users to request verification
        $email = $request->request->get('email', '');
        
        if ($request->isMethod('POST') && !empty($email)) {
            // Find user by email
            $userRepository = $this->entityManager->getRepository(User::class);
            $user = $userRepository->findOneBy(['email' => $email]);
            
            if (!$user) {
                $this->addFlash('warning', 'If an account exists with this email, a verification link will be sent.');
            } elseif ($this->emailVerificationService->isExemptFromEmailVerification($user)) {
                $this->addFlash('info', 'Admin accounts do not require email verification. You can log in directly.');
                return $this->redirectToRoute('app_user_login');
            } elseif ($user->isVerified()) {
                $this->addFlash('info', 'This email address is already verified. You can log in directly.');
                return $this->redirectToRoute('app_user_login');
            } else {
                // Generate new verification token
                $verificationToken = $this->emailVerificationService->generateVerificationToken();
                $user->setVerificationToken($verificationToken);
                $this->entityManager->flush();
                
                // Send verification email
                $verificationUrl = $this->urlGenerator->generate('app_verify_email', ['token' => $verificationToken], UrlGeneratorInterface::ABSOLUTE_URL);
                // Queue email send asynchronously (prevents nginx/PHP timeouts)
                $this->emailVerificationService->queueSendVerificationEmail($user, $verificationUrl);
                $this->addFlash('success', 'A verification link has been sent to ' . htmlspecialchars($email) . '. Please check your email.');
            }

            
            return $this->redirectToRoute('app_user_login');
        }
        
        return $this->render('email_verification/request.html.twig');
    }
}
