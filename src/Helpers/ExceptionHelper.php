<?php

namespace Everware\LaravelCherry\Helpers;

// Can be `use`d from the current namespace because ExceptionHelper is autoloaded using composer.json.
class TimeoutException extends \Exception {}

class ExceptionHelper
{
    /**
     * Run the callback and when any Exception is thrown, just ignore it and return default.
     *
     * @param  callable  $callback
     * @param  null  $default
     * @return mixed
     */
    public static function ignore(callable $callback, $default = null): mixed
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * @NOTE This only works for PHP CLI, not FPM. See https://stackoverflow.com/a/35029409/3017716
     *
     * @param int $timeout in seconds
     * @param bool $throw when `false` does not throw TimeoutException but returns `null`.
     * @throws TimeoutException
     */
    public static function runWithTimeout(int $timeout, callable $c, bool $throw = true): mixed
    {
        // $startTime = microtime(true);
        //
        // $result = null;
        // $timedOut = false;
        // // $pid is the child process PID.
        // $pid = pcntl_fork();
        //
        // // Forking failed.
        // if ($pid == -1) {
        //     throw new \Exception('Forking failed');
        // }
        // // We are the child process.
        // elseif ($pid == 0) {
        //     // Stop execution when the signal is received
        //     pcntl_signal(SIGTERM, fn()=> exit);
        //     $result = $c();
        //     exit;
        // }
        // // We are the parent process.
        // elseif ($pid) {
        //     // Wait for the child process to finish or timeout
        //     while (pcntl_wait($status, WNOHANG) == 0) {
        //         sleep(1);
        //         $elapsedTime = microtime(true) - $startTime;
        //         if ($elapsedTime >= $timeout) {
        //             // Send a SIGTERM signal to the child process
        //             posix_kill($pid, SIGTERM);
        //             $timedOut = true;
        //             break;
        //         }
        //     }
        // }
        //
        // // Return the result or indicate timeout
        // if ($timedOut) {
        //     throw new TimeoutException("Function timed-out.");
        // } else {
        //     return $result;
        // }

        //TODO Fix https://stackoverflow.com/a/35029409/3017716
        \pcntl_async_signals(true);
        // Set a signal handler for the SIGALRM signal
        \pcntl_signal(SIGALRM, fn()=> throw new TimeoutException("Function timed-out."));
        // Set the alarm for the specified timeout
        \pcntl_alarm($timeout);

        $return = null;
        try {
            $return = $c();
        } catch (TimeoutException $e) {
            if ($throw) {
                throw $e;
            }
        }
        // Cancel the alarm if the function finishes on time.
        \pcntl_alarm(0);

        return $return;
    }
}