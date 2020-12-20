<?php

declare(strict_types=1);

namespace Model\Payment;

use Nette\Mail\IMailer;
use Nette\Mail\Message;

class NullMailer implements IMailer
{
    public function send(Message $mail): void
    {
    }
}
