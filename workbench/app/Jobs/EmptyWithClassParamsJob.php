<?php

namespace Workbench\App\Jobs;

use Everware\LaravelCherry\Console\Commands\Interfaces\CreateFromStringParams;
use Everware\LaravelCherry\Jobs\Traits\FailedToSentry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Workbench\App\JustSomeClass;

class EmptyWithClassParamsJob implements ShouldQueue, CreateFromStringParams
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, FailedToSentry;

    public static function fromParams(string ...$params): static
    {
        return new static(
            new JustSomeClass(
                $params[0],
                $params[1],
                $params[2],
                $params[3],
                json_decode($params[4]),
            )
        );
    }

    public function __construct(
        public JustSomeClass $justSomeClass
    ){}

    public function handle(): void
    {
        //
    }
}