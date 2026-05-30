<?php

namespace App\Message;

class SendVerificationEmailMessage
{
    public function __construct(
        public readonly int $userId,
        public readonly string $verificationUrl
    ) {
    }
}

