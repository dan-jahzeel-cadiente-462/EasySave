<?php

namespace App\Message;

class SendConfirmationEmailMessage
{
    public function __construct(
        public readonly int $userId
    ) {
    }
}

