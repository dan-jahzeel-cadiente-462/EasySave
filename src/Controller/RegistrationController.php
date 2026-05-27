<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\LoginAuthenticator;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RegistrationController extends AbstractController
{
    public function __construct(
        private EmailVerificationService $emailVerificationService,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // Generate verification token and set user as not verified
            $verificationToken = $this->emailVerificationService->generateVerificationToken();
            $user->setVerificationToken($verificationToken);
            $user->setIsVerified(false);

            $entityManager->persist($user);
            $entityManager->flush();

            // Send verification email
            $verificationUrl = $this->urlGenerator->generate('app_verify_email', ['token' => $verificationToken], UrlGeneratorInterface::ABSOLUTE_URL);
            try {
                $this->emailVerificationService->sendVerificationEmail($user, $verificationUrl);
                $this->addFlash('success', 'Registration successful! Please check your email to verify your account.');
            } catch (\Exception $e) {
                // Log the detailed error for debugging
                error_log('Email verification sending failed: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                
                // Provide helpful message to user
                $this->addFlash('warning', 'Registration successful! However, we could not send the verification email. Please check: 1) Your email address is correct, 2) Check your spam folder, 3) Contact support if problems persist.');
            }

            return $this->redirectToRoute('app_user_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
