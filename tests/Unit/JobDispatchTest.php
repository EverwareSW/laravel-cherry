<?php

use Everware\LaravelCherry\Tests\TestCase;
use Workbench\App\Jobs\EmptyJob;
use Workbench\App\Jobs\EmptyWithClassParamsJob;
use Workbench\App\Jobs\EmptyWithScalarParamsJob;

pest()->extends(TestCase::class);

test('job:dispatch', function () {
    \Queue::fake();

    $this->artisan('job:dispatch EmptyJob')
        ->assertSuccessful();

    \Queue::assertPushed(EmptyJob::class, function(EmptyJob $job) {
        return $job->connection === null;
    });

    $this->artisan('job:dispatch EmptyWithScalarParamsJob 2 dollejuin')
        ->assertSuccessful();

    \Queue::assertPushed(EmptyWithScalarParamsJob::class, function(EmptyWithScalarParamsJob $job) {
        return $job->connection === null
            && $job->int === 2
            && $job->string === 'dollejuin';
    });

    $this->artisan('job:dispatch Workbench\\\App\\\Jobs\\\EmptyWithClassParamsJob 0 5 1.2 natnek "[3,2,1]" --sync')
        ->assertSuccessful();

    \Queue::assertPushed(EmptyWithClassParamsJob::class, function(EmptyWithClassParamsJob $job) {
        return $job->connection === 'sync'
            && $job->justSomeClass->bool === false
            && $job->justSomeClass->int === 5
            && $job->justSomeClass->float === 1.2
            && $job->justSomeClass->string === 'natnek'
            && $job->justSomeClass->array === [3,2,1];
    });
});