<?php

namespace Everware\LaravelCherry\Mail\Abstracts;

use Everware\LaravelCherry\Jobs\Traits\FailedToSentry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Extend your Mailable from this class to be able to test more easily using
 * ```
 * \Mail::assertSent(OfferSendMail::class, 1);
 * $mailable = \Mail::shiftSentMailable();
 * $mailable->assertHasTo('example@email.com');
 * $mailable->assertHasBcc('example@email.com');
 * $mailable->assertHasSubject("Example subject");
 * $mailable->assertSeeInHtml("Some text in HTML", false);
 * $mailable->assertHasAttachedData(fn($attachment)=> true)
 * ```
 */
abstract class BaseMailable extends Mailable
{
    use FailedToSentry, Queueable, SerializesModels;

    /*
     *
     * Better testing / asserting
     *
     */

    /**
     * Also {@see MailFake}.
     *
     * Makes the method public.
     * @inheritDoc
     */
    public function renderForAssertions(): array
    {
        return parent::renderForAssertions();
    }

    /**
     * Also {@see MailFake}.
     *
     * @inheritDoc
     * @param string|callable $data
     */
    public function hasAttachedData($data, $name = null, array $options = []): bool
    {
        return collect($this->rawAttachments)->contains(fn($attachment) =>
            is_callable($data) ? $data($attachment) : (
                $attachment['data'] === $data &&
                $attachment['name'] === $name &&
                array_filter($attachment['options']) === array_filter($options) &&
                true
            )
        );
    }

    /**
     * Also {@see MailFake}.
     *
     * @inheritDoc
     * @param string|callable $data
     */
    public function assertHasAttachedData($data, $name = null, array $options = []): static
    {
        return parent::assertHasAttachedData($data, $name, $options);
    }
}