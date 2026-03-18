<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailVerificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private MailerInterface $mailer
    ) {}

    /**
     * Generate a unique verification token
     */
    public function generateVerificationToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Verify email token and mark user as verified
     */
    public function verifyToken(string $token): ?User
    {
        $user = $this->userRepository->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            return null;
        }

        // Mark user as verified
        $user->setIsVerified(true);
        $user->setVerificationToken(null); // Clear the token after use

        $this->entityManager->flush();

        return $user;
    }

    /**
     * Send verification email to user
     */
    public function sendVerificationEmail(User $user, string $verificationUrl): void
    {
        $email = (new Email())
            ->from('noreply@easysave.local')
            ->to($user->getEmail())
            ->subject('Verify Your Email Address')
            ->html($this->renderEmailTemplate($user, $verificationUrl));

        $this->mailer->send($email);
    }

    /**
     * Render email HTML template
     */
    private function renderEmailTemplate(User $user, string $verificationUrl): string
    {
        return sprintf(
            <<<HTML
            <html>
                <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                        <h1 style="color: #2c3e50;">Welcome to EasySave!</h1>
                        <p>Hello %s,</p>
                        <p>Thank you for registering with EasySave. To complete your registration and verify your email address, please click the button below:</p>
                        <div style="text-align: center; margin: 30px 0;">
                            <a href="%s" style="display: inline-block; padding: 12px 30px; background-color: #3498db; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">Verify Email</a>
                        </div>
                        <p>Or copy and paste this link in your browser:</p>
                        <p><a href="%s" style="color: #3498db;">%s</a></p>
                        <p>This link will expire in 24 hours.</p>
                        <p>If you did not create this account, please ignore this email.</p>
                        <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">
                        <p style="font-size: 12px; color: #777;">© 2026 EasySave. All rights reserved.</p>
                    </div>
                </body>
            </html>
            HTML,
            htmlspecialchars($user->getUsername()),
            htmlspecialchars($verificationUrl),
            htmlspecialchars($verificationUrl),
            htmlspecialchars($verificationUrl)
        );
    }
}
