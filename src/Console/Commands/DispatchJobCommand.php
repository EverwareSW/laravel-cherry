<?php

namespace Everware\LaravelCherry\Console\Commands;

use Everware\LaravelCherry\Console\Commands\Interfaces\CreateFromStringParams;
use Illuminate\Console\Command;
use Laravel\Tinker\ClassAliasAutoloader;

/**
 * Dispatch a job from the command line.
 * Use {@see CreateFromStringParams} to parse string command line parameters to required constructor parameters.
 */
class DispatchJobCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'job:dispatch {class} {params?*} {--sync}';

    /**
     * @var string
     */
    protected $description = 'Dispatch a job';

    /**
     * @return int
     */
    public function handle()
    {
        // Based on https://github.com/mxl/laravel-job
        $jobClass = $jobName = $this->argument('class');

        if (!class_exists($jobClass)) {
            $jobClass = "\\App\\Jobs\\$jobClass";
        }
        if (!class_exists($jobClass)) {
            $jobClass = "\\Workbench$jobClass";
        }
        if (!class_exists($jobClass)) {
            $this->error("Job class $jobName not found");
            return static::FAILURE;
        }

        $params = $this->argument('params') ?? [];
        if (in_array(CreateFromStringParams::class, class_implements($jobClass))) {
            $jobInstance = $jobClass::fromParams(...$params);
        } else {
            /** Based on {@see Container::build()} into {@see Container::resolveDependencies()}. */
            // $paramNames = collect((new \ReflectionClass($jobClass))->getConstructor()?->getParameters())->map->getName();
            // $params = $paramNames->combine($params)->all();
            $paramValues = collect((new \ReflectionClass($jobClass))->getConstructor()?->getParameters())
                ->mapWithKeys(fn(\ReflectionParameter $param, $index) => [$param->getName() => $params[$index] ?? $param->getDefaultValue()])
                ->all();
            $jobInstance = app($jobClass, $paramValues);
        }

        $this->option('sync') ? dispatch_sync($jobInstance) : dispatch($jobInstance);

        // /** Based on @see TinkerCommand::handle() */
        // $loader = ClassAliasAutoloader::register(
        //     $shell, $path, $config->get('tinker.alias', []), $config->get('tinker.dont_alias', [])
        // );
    }
}