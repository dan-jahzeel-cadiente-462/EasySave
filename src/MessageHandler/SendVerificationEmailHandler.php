<?php

namespace App\MessageHandler;

use App\Message\SendVerificationEmailMessage;
use App\Service\EmailVerificationService;
use App\Repository\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

#[AsMessageHandler]
class SendVerificationEmailHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EmailVerificationService $emailVerificationService,
    ) {
    }

    public function __invoke(SendVerificationEmailMessage $message): void
    {
        $user = $this->userRepository->find($message->userId);
        if (!$user) {
            return;
        }

        $this->emailVerificationService->sendVerificationEmail($user, $message->verificationUrl);
    }
}

