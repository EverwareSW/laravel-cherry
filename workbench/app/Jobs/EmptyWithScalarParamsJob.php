<?php

namespace Workbench\App\Jobs;

use Everware\LaravelCherry\Jobs\Traits\FailedToSentry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EmptyWithScalarParamsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, FailedToSentry;

    public function __construct(
        public int $int,
        public string $string,
    ){}

    public function handle(): void
    {
        //
    }
}