<?php

namespace Everware\LaravelCherry\Mail\Fakes;

use Illuminate\Contracts\Mail\Mailable;

class MailFake extends \Illuminate\Support\Testing\Fakes\MailFake
{
    public function shiftSentMailable(): ?Mailable
    {
        return array_shift($this->mailables);
    }
}