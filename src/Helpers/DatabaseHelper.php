<?php

namespace Everware\LaravelCherry\Helpers;

use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;

class DatabaseHelper
{
    /**
     * @NOTE This method only works on MySQL.
     * If you ever need this for other database types {@see Connection::isUniqueConstraintError()}.
     */
    public static function retryDuplicate(callable $callback, int $times = 2, null|int|\Closure $sleep = 0): mixed
    {
        return retry(
            $times,
            $callback,
            $sleep ?? fn()=> mt_rand(200, 2000),
            /**
             * "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry"
             */
            fn(\Throwable $e)=> $e instanceof UniqueConstraintViolationException || $e instanceof QueryException && $e->getCode() == 23000
        );
    }

    /**
     * @NOTE This method only works on MySQL.
     * If you ever need this for other database types {@see Connection::isUniqueConstraintError()}.
     */
    public static function retryDeadlock(callable $callback, int $times = 2, null|int|\Closure $sleep = 0): mixed
    {
        return retry(
            $times,
            $callback,
            $sleep ?? fn()=> mt_rand(200, 2000),
            /**
             * "SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock; try restarting transaction"
             * @Note we are not using {@see DeadlockException} because
             * that's only thrown above a transaction {@see ManagesTransactions::handleTransactionException()}
             * and not while running a query {@see Connection::runQueryCallback()}.
             */
            fn(\Throwable $e)=> $e instanceof QueryException && $e->getCode() == 40001
        );
    }
}