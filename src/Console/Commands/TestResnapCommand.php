<?php

namespace Everware\LaravelCherry\Console\Commands;

use NunoMaduro\Collision\Adapters\Laravel\Commands\TestCommand;

class TestResnapCommand extends TestCommand
{
    protected $signature = 'test:resnap
        {--without-tty : Disable output to TTY}
        {--compact : Indicates whether the compact printer should be used}
        {--coverage : Indicates whether code coverage information should be collected}
        {--min= : Indicates the minimum threshold enforcement for code coverage}
        {--p|parallel : Indicates if the tests should run in parallel}
        {--profile : Lists top 10 slowest tests}
        {--recreate-databases : Indicates if the test databases should be re-created}
        {--drop-databases : Indicates if the test databases should be dropped}
        {--without-databases : Indicates if database configuration should be performed}
    ';

    protected $description = 'Run the last test with a failed snapshot assertion, refreshing its snapshot.';

    protected $filters = null;

    /**
     * @return int
     */
    public function handle()
    {
        /** {@see PHPUnitExtension::bootstrap()}. */
        $lastFailedSnapshot = base_path('vendor/pestphp/pest/.temp/last-failed-snapshot');

        if (!file_exists($lastFailedSnapshot)) {
            $this->error('No last run found, have you added extension in phpunit.xml?');
            return 1;
        }

        // E.g. "P\Tests\Feature\Api\ProviderTest::__pest_evaluable_index"
        $result = \File::get($lastFailedSnapshot);

        // We can't use --update-snapshots (icw --filter) because that deletes all other snapshots.
        $segments = str_replace('::', '\\', str_replace('__pest_evaluable_', '', $result));
        $segments = array_slice(explode('\\', $segments), 2);
        /** {@see SnapshotRepository::save()} from {@see Expectation::toMatchSnapshot()} from {@see \Pest\TestSuite::__construct()}. */
        $path = base_path('tests/.pest/snapshots/' . join(DIRECTORY_SEPARATOR, $segments) . '.snap');
        $this->info("Deleting snapshot $path");
        \File::delete($path);

        /** Based on {@see MutationTest::start()}. */
        preg_match('/\\\\([a-zA-Z0-9]*)::(__pest_evaluable_)?([^#]*)"?/', $result, $matches);
        if ($matches[2] === '__pest_evaluable_') {
            $this->filters[] = $matches[1].'::(.*)'.str_replace(['__', '_'], ['.{1,2}', '.'], $matches[3]);
        } else {
            $this->filters[] = $matches[1].'::(.*)'.$matches[3];
        }
        $this->filters = array_unique($this->filters);

        $this->info("Rerunning test using --filter=".implode('|', $this->filters));
        return parent::handle();

        // /** Based on {@see Application::initializeTestResultCache()} from {@see Merger::merge()}. */
        // $resultPath = base_path('vendor/pestphp/pest/.temp/test-results');
        // $result = \File::json($resultPath);
        //
        // $this->filters = [];
        // foreach($result['defects'] as $test => $status) {
        //     /** Based on {@see DefaultResultCache::load()}. */
        //     $status = TestStatus::from($status);
        //     if (!$status->isFailure() && !$status->isError()) {
        //         continue;
        //     }
        //
        //     /** Based on {@see MutationTest::start()}. */
        //     preg_match('/\\\\([a-zA-Z0-9]*)::(__pest_evaluable_)?([^#]*)"?/', $test, $matches);
        //     if ($matches[2] === '__pest_evaluable_') {
        //         $this->filters[] = $matches[1].'::(.*)'.str_replace(['__', '_'], ['.{1,2}', '.'], $matches[3]);
        //     } else {
        //         $this->filters[] = $matches[1].'::(.*)'.$matches[3];
        //     }
        // }
        // $this->filters = array_unique($this->filters);
        //
        // if ($this->ask("Update snapshots for:\n ".implode("\n ", $this->filters)."\n ? [y|n]") == 'y') {
        //     $exitCode = parent::handle();
        //     // \File::delete($resultPath);
        //     return $exitCode;
        // }
    }

    protected function commonArguments()
    {
        $array = parent::commonArguments();

        if (!empty($this->filters)) {
            /** Based on {@see MutationTest::start()}. */
            $array[] = '--filter='.implode('|', $this->filters);
            // We can't use --update-snapshots (icw --filter) because that deletes all other snapshots.
            // $array[] = '--update-snapshots';
        }

        return $array;
    }
}