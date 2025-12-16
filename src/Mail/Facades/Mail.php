<?php

namespace Everware\LaravelCherry\Mail\Facades;

use Everware\LaravelCherry\Mail\Fakes\MailFake;

/**
 * @mixin MailFake
 */
class Mail extends \Illuminate\Support\Facades\Mail
{
    /**
     * @inheritDoc
     */
    public static function fake()
    {
        $actualMailManager = static::isFake()
                ? static::getFacadeRoot()->manager
                : static::getFacadeRoot();

        return tap(new MailFake($actualMailManager), function ($fake) {
            static::swap($fake);
        });
    }
}