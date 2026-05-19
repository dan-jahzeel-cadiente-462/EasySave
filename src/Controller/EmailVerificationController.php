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
        $error = $this->emailVerificationService->verifyToken($token);

        if ($error !== null) {
            $this->addFlash('error', $error);
            return $this->redirectToRoute('app_user_login');
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
            } else if ($user->isVerified()) {
                $this->addFlash('info', 'This email address is already verified. You can log in directly.');
                return $this->redirectToRoute('app_user_login');
            } else {
                // Generate new verification token
                $verificationToken = $this->emailVerificationService->generateVerificationToken();
                $user->setVerificationToken($verificationToken);
                $this->entityManager->flush();
                
                // Send verification email
                $verificationUrl = $this->urlGenerator->generate('app_verify_email', ['token' => $verificationToken], UrlGeneratorInterface::ABSOLUTE_URL);
                try {
                    $this->emailVerificationService->sendVerificationEmail($user, $verificationUrl);
                    $this->addFlash('success', 'A verification link has been sent to ' . htmlspecialchars($email) . '. Please check your email.');
                } catch (\Exception $e) {
                    error_log('Email verification resend failed: ' . $e->getMessage());
                    $this->addFlash('error', 'Failed to send verification email. Please try again later.');
                }
            }
            
            return $this->redirectToRoute('app_user_login');
        }
        
        return $this->render('email_verification/request.html.twig');
    }
}
