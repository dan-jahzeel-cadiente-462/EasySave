<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Email;

class EmailVerificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private MessageBusInterface $bus,
        #[Autowire('%app.mailer_from%')]
        private string $mailerFrom,
    ) {
    }

    public function isExemptFromEmailVerification(User $user): bool
    {
        // Only ROLE_ADMIN is exempt from email verification; ROLE_STAFF and ROLE_USER must verify
        return in_array('ROLE_ADMIN', $user->getRoles(), true);
    }

    public function generateVerificationToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function verifyToken(string $token): ?User
    {
        $user = $this->userRepository->findOneBy(['verificationToken' => $token]);
        if (!$user) {
            return null;
        }

        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $this->entityManager->flush();

        return $user;
    }

    public function getVerifiedUser(string $token): ?User
    {
        return $this->userRepository->findOneBy(['verificationToken' => $token]);
    }

    public function queueSendVerificationEmail(User $user, string $verificationUrl): void
    {
        if (!$user->getId()) {
            return;
        }

        $this->bus->dispatch(new \App\Message\SendVerificationEmailMessage($user->getId(), $verificationUrl));
    }

    /**
     * Synchronous send (called by Messenger handler)
     */
    public function sendVerificationEmail(User $user, string $verificationUrl): void
    {
        $this->logger->info('Starting email verification send', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
        ]);

        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($user->getEmail())
            ->subject('Verify Your Email Address')
            ->html($this->renderVerificationEmailTemplate($user, $verificationUrl))
            ->text($this->renderVerificationEmailText($user, $verificationUrl));

        $this->mailer->send($email);

        $this->logger->info('Verification email sent successfully', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
        ]);
    }

    /**
     * Synchronous send (called by controller / could be queued later)
     */
    public function sendConfirmationEmail(User $user): void
    {
        $this->logger->info('Starting email confirmation send', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
        ]);

        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($user->getEmail())
            ->subject('Email Verified Successfully - Welcome to EasySave!')
            ->html($this->renderConfirmationEmailTemplate($user))
            ->text($this->renderConfirmationEmailText($user));

        $this->mailer->send($email);

        $this->logger->info('Confirmation email sent successfully', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
        ]);
    }

    private function renderVerificationEmailTemplate(User $user, string $verificationUrl): string
    {
        $template = <<<HTML
            <!DOCTYPE html>
            <html lang="en">
                <head>
                    <meta charset="UTF-8" />
                    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                    <title>Verify Your Email</title>
                </head>
                <body style="margin:0;padding:0;font-family: Arial, sans-serif;background-color:#f5f7fb;color:#333;">
                    <table cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f5f7fb;padding:20px;width:100vw;">
                        <tr>
                            <td align="center">
                                <table width="600" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 18px rgba(0,0,0,0.08);">
                                    <tr>
                                        <td style="padding:30px;text-align:left;">
                                            <h1 style="margin:0 0 20px;color:#2c3e50;font-size:28px;">Welcome to EasySave!</h1>
                                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">Hello {{username}},</p>
                                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">Thank you for registering with EasySave. To complete your registration and verify your email address, please click the button below:</p>
                                            <p style="text-align:center;margin:30px 0;">
                                                <a href="{{verificationUrl}}" style="display:inline-block;padding:14px 28px;background-color:#2563eb;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:700;">Verify Email</a>
                                            </p>
                                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">If the button does not work, copy and paste the following link into your browser:</p>
                                            <p style="word-break:break-all;font-size:14px;line-height:1.5;color:#2563eb;"><a href="{{verificationUrl}}" style="color:#2563eb;text-decoration:none;">{{verificationUrl}}</a></p>
                                            <p style="margin:24px 0 0;font-size:14px;line-height:1.6;color:#555;">This link will expire in 24 hours. If you did not create this account, you can safely ignore this email.</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:20px 30px;background-color:#f8fafc;color:#7a7f8c;font-size:13px;line-height:1.5;">
                                            <p style="margin:0;">© 2026 EasySave. All rights reserved.</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </body>
            </html>
            HTML;

        return str_replace(
            ['{{username}}', '{{verificationUrl}}'],
            [htmlspecialchars($user->getUsername()), htmlspecialchars($verificationUrl)],
            $template,
        );
    }

    private function renderVerificationEmailText(User $user, string $verificationUrl): string
    {
        return sprintf(
            "Hello %s,\n\n" .
                "Thank you for registering with EasySave. To complete your registration and verify your email address, please open the link below:\n\n" .
                "%s\n\n" .
                "If you did not create this account, please ignore this message.\n\n" .
                "© 2026 EasySave. All rights reserved.",
            $user->getUsername(),
            $verificationUrl,
        );
    }

    private function renderConfirmationEmailTemplate(User $user): string
    {
        $template = <<<HTML
            <!DOCTYPE html>
            <html lang="en">
                <head>
                    <meta charset="UTF-8" />
                    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                    <title>Email Verified</title>
                </head>
                <body style="margin:0;padding:0;font-family: Arial, sans-serif;background-color:#f5f7fb;color:#333;">
                    <table cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f5f7fb;padding:20px;width:100vw;">
                        <tr>
                            <td align="center">
                                <table width="600" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 18px rgba(0,0,0,0.08);">
                                    <tr>
                                        <td style="padding:30px;text-align:left;">
                                            <div style="text-align:center;margin-bottom:30px;">
                                                <div style="font-size:48px;color:#22c55e;">✓</div>
                                                <h1 style="color:#2c3e50;margin:10px 0;font-size:28px;">Email Verified Successfully!</h1>
                                            </div>
                                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">Hello {{username}},</p>
                                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">Great news! Your email address has been successfully verified. Your EasySave account is now fully activated and ready to use.</p>
                                            <div style="background-color:#f3f4f6;padding:20px;border-left:4px solid #22c55e;margin:20px 0;">
                                                <h3 style="color:#2c3e50;margin-top:0;font-size:18px;">What's next?</h3>
                                                <ul style="color:#666;font-size:15px;line-height:1.6;margin:0;padding-left:20px;">
                                                    <li>Log in to your account to get started</li>
                                                    <li>Set up your first savings goal</li>
                                                    <li>Start tracking your expenses</li>
                                                    <li>Collaborate with family members</li>
                                                </ul>
                                            </div>
                                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">If you have any questions or need assistance, feel free to reach out to our support team.</p>
                                            <p style="text-align:center;margin:30px 0;">
                                                <a href="https://easysave.up.railway.app/login" style="display:inline-block;padding:14px 28px;background-color:#16a34a;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:700;">Go to EasySave</a>
                                            </p>
                                            <p style="margin:0;font-size:14px;line-height:1.6;color:#555;">Welcome to the EasySave community!</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:20px 30px;background-color:#f8fafc;color:#7a7f8c;font-size:13px;line-height:1.5;">
                                            <p style="margin:0;">© 2026 EasySave. All rights reserved.</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </body>
            </html>
            HTML;

        return str_replace(
            ['{{username}}'],
            [htmlspecialchars($user->getUsername())],
            $template,
        );
    }

    private function renderConfirmationEmailText(User $user): string
    {
        return sprintf(
            "Hello %s,\n\n" .
                "Your email address has been successfully verified. Your EasySave account is now active and ready to use.\n\n" .
                "Visit EasySave to log in and get started: https://easysave.up.railway.app/login\n\n" .
                "© 2026 EasySave. All rights reserved.",
            $user->getUsername(),
        );
    }
}

