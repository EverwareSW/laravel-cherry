<?php

namespace Everware\LaravelCherry\Tests\Concerns;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Notifications\AnonymousNotifiable;
use Mockery\MockInterface;

trait TestCaseHelper
{
    use WithFaker;

    /**
     * Make sure the factories' fields are set equally every run.
     * Useful when using snapshot comparisons, so you don't have to replace dynamic content in the result string.
     * @NOTE Does NOT work with {@see fake()} because that suffixes locale to class and binds new instance! Use $this->faker in factories.
     * @param int $seed Pass a hardcoded random int at the top of every test.
     */
    public function setFactorySeed(int $seed)
    {
        /** {@see Factory::withFaker()} and {@see WithFaker::makeFaker()} bound in {@see DatabaseServiceProvider::registerFakerGenerator()}. */
        $this->faker()->seed($seed);
    }

    /**
     * When you {@see resolve()} or {@see app()} classes that not have been registered beforehand,
     * you must {@see Application::bind()} the mock instead of {@see Application::instance()}.
     *
     * @param class-string $class
     * @param \Closure(MockInterface):(MockInterface|void)|null $definition
     * @return MockInterface
     */
    public function bindMock(string $class, ?\Closure $definition): MockInterface
    {
        $mock = $this->mock($class, $definition);
        $this->app->bind($class, fn() => $mock);
        return $mock;
    }

    /**
     * @Todo Move to Pest?
     *
     * Casts whole numbers to int so PHPUnit compares the number correctly when comparing jsons.
     */
    public static function numCast(float|int|string|null $number, ?int $precision = null): float|int|null
    {
        if ($number === null) {
            return null;
        }
        if (floor($number) == $number) {
            return (int) $number;
        }
        if ($precision !== null) {
            return round($number, $precision);
        }

        return (float) $number;
    }

    /**
     * @Todo Move to Pest?
     *
     * @see \Notification::assertSentTo() does not actually check if an e-mail was sent to
     * the given @see AnonymousNotifiables::routes (e-mail addresses).
     */
    public function assertNotificationSentAddresses(AnonymousNotifiable $notifiable, array $tos): bool
    {
        // assertSentTo() does not actually check if an e-mail was sent to the given addresses.
        $routes = $notifiable->routeNotificationFor('mail');

        $addresses = array_values(\Arr::map($routes, fn(string|Address $route, int|string $key) =>
            is_string($route) ? $key : $route->getAddress()
        ));
        $names = array_values(\Arr::map($routes, fn(string|Address $route, int|string $key) =>
            is_string($route) ? $route : $route->getName()
        ));

        return array_keys($tos) == $addresses && array_values($tos) == $names;
    }
}