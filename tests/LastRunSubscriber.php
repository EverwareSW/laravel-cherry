<?php declare(strict_types=1);

namespace Everware\LaravelCherry\Tests;

use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\FailedSubscriber;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Runner\ResultCache\ResultCacheId;
use PHPUnit\TextUI\Configuration\Configuration;

class LastRunSubscriber
    // implements ErroredSubscriber, FailedSubscriber, MarkedIncompleteSubscriber, SkippedSubscriber, PassedSubscriber
    // implements AfterLastTestMethodCalledSubscriber
    // implements FinishedSubscriber
    implements FailedSubscriber
{
    public function __construct(
        private Configuration $configuration
    ){}

    // public function notify(Finished $event): void
    // {
    //     /** {@see Application::initializeTestResultCache()}. */
    //     $path = dirname($this->configuration->testResultCacheFile());
    //     /** {@see ResultCacheHandler::testFailed()}. */
    //     $data = ResultCacheId::fromTest($event->test())->asString();
    //     /** {@see DefaultResultCache::persist()}. */
    //     file_put_contents(
    //         $path . DIRECTORY_SEPARATOR . 'last-run',
    //         $data,
    //         LOCK_EX,
    //     );
    // }

    public function notify(Failed $event): void
    {
        /** {@see Expectation::toMatchSnapshot()}. */
        if ($event->hasComparisonFailure()
        // && $event->throwable()->className() === ExpectationFailedException::class
        && str_starts_with($event->throwable()->message(), 'Failed asserting that the string value matches its snapshot')) {
            /** {@see Application::initializeTestResultCache()}. */
            $path = dirname($this->configuration->testResultCacheFile());
            /** {@see ResultCacheHandler::testFailed()}. */
            $data = ResultCacheId::fromTest($event->test())->asString();
            /** {@see DefaultResultCache::persist()}. */
            file_put_contents(
                $path . DIRECTORY_SEPARATOR . 'last-failed-snapshot',
                $data,
                LOCK_EX,
            );
        }
    }
}