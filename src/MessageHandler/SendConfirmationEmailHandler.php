<?php

namespace App\MessageHandler;

use App\Message\SendConfirmationEmailMessage;
use App\Repository\UserRepository;
use App\Service\EmailVerificationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

#[AsMessageHandler]
class SendConfirmationEmailHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EmailVerificationService $emailVerificationService,
    ) {
    }

    public function __invoke(SendConfirmationEmailMessage $message): void
    {
        $user = $this->userRepository->find($message->userId);
        if (!$user) {
            return;
        }

        $this->emailVerificationService->sendConfirmationEmail($user);
    }
}

