<?php

namespace Everware\LaravelCherry\Jobs\Traits;

trait FailedToSentry
{
    /**
     * When jobs are queued instead of synced, Sentry is not notified of exceptions.
     * When mail is queued instead of sent, Sentry is not notified of exceptions.
     * Add this trait to your Queueable classes to fix that.
     *
     * {@see CallQueuedHandler::failed()} and {@see SendQueuedMailable::failed()} and {@see SendQueuedNotifications::failed()}.
     * https://docs.sentry.io/platforms/php/guides/laravel/usage/#queue-jobs
     */
    public function failed(\Throwable $exception): void
    {
        if (class_exists('\Sentry\Laravel\Facade')) {
            /** Also {@see Handler::register()} */
            \Sentry\Laravel\Facade::captureException($exception);
        }
    }
}