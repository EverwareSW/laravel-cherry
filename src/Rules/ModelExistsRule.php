<?php

namespace Everware\LaravelCherry\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\Conditionable;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

/**
 * This Rule exists because Laravels {@see \Illuminate\Validation\Rule::exists()} uses a Query\Builder
 * and because of that, using Rule::exists(), global scopes and such are not called.
 * This ModelExistsRule, however, uses an Eloquent\Builder, so global scopes are called.
 *
 * {@see Validator::validateExists()}.
 *
 * @template T of Model
 * @mixin Builder<T>
 */
class ModelExistsRule implements Rule
{
    use Conditionable;

    /** @var T */
    protected Model $model;
    /** @var array<int, \Closure> */
    protected array $rootWheres = [];
    /** @var array<int, array{string, array}> */
    protected array $dynamicWheres = [];

    protected bool    $isIterable = false;
    protected ?string $transKey   = null;

    /**
     * @param T|class-string<T> $model
     */
    public function __construct(
        Model|string $model,
        public string $column = 'id',
        public null|int|string|RouteParameterReference $ignoredKey = null,
        public string $ignoredKeyColumn = 'id',
    ){
        $this->model = is_string($model) ? resolve($model) : $model;
    }

    /**
     * @param T|class-string<T> $model
     */
    public static function make(
        Model|string $model,
        string $column = 'id',
        null|int|string|RouteParameterReference $ignoredKey = null,
        string $ignoredKeyColumn = 'id',
    ) : static {
        return new static($model, $column, $ignoredKey, $ignoredKeyColumn);
    }

    /** Based on {@see Unique::ignore()}. */
    public function ignore(null|int|string|RouteParameterReference $key, string $column = 'id'): static
    {
        $this->ignoredKey = $key;
        $this->ignoredKeyColumn = $column;
        return $this;
    }

    /**
     * @deprecated Use __call() e.g. `$rule->whereHas('something')`
     */
    public function rootWhere(\Closure $where): static
    {
        $this->rootWheres[] = $where;
        return $this;
    }

    public function __call(string $method, array $arguments)
    {
        $this->dynamicWheres[] = [$method, $arguments];
        return $this;
    }

    public function passes($attribute, $value): bool
    {
        $query = $this->model->newQuery();
        $table = $this->model->getTable();

        /** {@see Validator::validateExists()} {@see Validator::getExistCount()} */
        if ($this->isIterable = is_iterable($value)) {
            $count = count(array_unique($value));
            /** @see DatabasePresenceVerifier::getMultiCount() */
            $query->whereIn("$table.$this->column", $value);
        } else {
            $count = 1;
            /** @see DatabasePresenceVerifier::getCount() */
            $query->where("$table.$this->column", '=', $value);
        }

        foreach ($this->rootWheres as $where) {
            $where($query);
        }
        foreach ($this->dynamicWheres as [$method, $arguments]) {
            $query->$method(...$arguments);
        }
        // foreach ($this->wheres as $where) {
        //     $query->where(...$where);
        // }

        if ($this->ignoredKey instanceof RouteParameterReference) {
            $this->ignoredKey = $this->ignoredKey->getValue();
        }
        /** Based on {@see ValidatesAttributes::validateUnique()}. */
        if ($this->ignoredKey !== null) {
            $query->where($this->ignoredKeyColumn, '<>', $this->ignoredKey);
        }

        /** {@see Validator::validateExists()} */
        return $query->count() >= $count;
    }

    public function transKey(?string $transKey): static
    {
        $this->transKey = $transKey;
        return $this;
    }

    public function message(): string
    {
        return trans_choice($this->transKey ?? 'cherry::validation.model-exists', 1 + $this->isIterable);
    }
}