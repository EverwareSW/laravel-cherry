<?php

namespace Everware\LaravelCherry\Models\Repositories;

use Everware\LaravelCherry\Http\Requests\IndexRequest;
use Everware\LaravelCherry\Models\Traits\SoftDeletedBy;
use Illuminate\Database\Eloquent\Builder as EBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QBuilder;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class ModelRepository
{
    /**
     * @param string $model
     */
    public function __construct(
        protected string $model
    ){
        // if (!in_array(ModelBase::class, class_uses_recursive($this->model))) {
        //     throw new \Exception("Class '$model' should implement `use ModelBase;`.");
        // }
    }

    protected function model(): Model
    {
        return new $this->model;
    }

    /*
     *
     * Public methods
     *
     */

    /**
     * Overwrite this to add functionality to index query.
     *
     * @param IndexRequest $request
     * @param null|callable(EBuilder):(null|EBuilder) $before
     * @param null|callable(EBuilder):(null|EBuilder) $after
     * @return EBuilder
     */
    public function query(IndexRequest $request, ?callable $before = null, ?callable $after = null): EBuilder
    {
        $query = $this->newQuery();
        $before and $query = $before($query) ?: $query;
        $this->addSoftDeletesToQuery($query, $request);
        $this->addFiltersToQuery($query, $request);
        $this->addSearchToQuery($query, $request);
        $this->addSortsToQuery($query, $request);
        $this->withDeletedBy($query, $request);
        $after and $query = $after($query) ?: $query;
        return $query;
    }

    /**
     * Standard index method. This should suffice in most cases.
     * However, it's safer to create your own index() method with extended IndexRequest class (for validation).
     *
     * @param IndexRequest $request
     * @param null|callable(EBuilder):(null|EBuilder) $before
     * @param null|callable(EBuilder):(null|EBuilder) $after
     * @return LengthAwarePaginator
     */
    public function paginate(IndexRequest $request, ?callable $before = null, ?callable $after = null, ?callable $total = null): LengthAwarePaginator
    {
        $query = $this->query($request, $before, $after);

        $paginator = $query->paginate(
            $request->get('pageSize') ?? null,
            ['*'],
            'page',
            $request->get('page') ?? null,
            value($total, $query),
        );

        $paginator->withQueryString();
        $paginator->through(fn(Model $model)=> $this->paginateThrough($model, $request));

        return $paginator;
    }

    /**
     * Overwrite this to add functionality to index model view data.
     */
    protected function paginateThrough(Model $model, IndexRequest $request): Model
    {
        return $model;
    }

    /*
     *
     * Query
     *
     */

    protected function newQuery(): EBuilder
    {
        $model = $this->model();
        $table = $model->getTable();

        return $model->newQuery()->select("$table.*");
    }

    /*
     *
     * Query modifiers
     *
     */

    protected function addSoftDeletesToQuery(EBuilder $query, Request $request): EBuilder
    {
        if ($request->get('deleted', false) && in_array(SoftDeletes::class, class_uses_recursive($this->model))) {
            /** {@see SoftDeletes::onlyTrashed()} {@see SoftDeletingScope::addOnlyTrashed()} */
            $query->onlyTrashed();
        }

        return $query;
    }

    protected function addFiltersToQuery(EBuilder $query, IndexRequest $request): EBuilder
    {
        foreach ($request->getFilteredValues() as $filter => $value) {
            ($f = function(EBuilder $query, string $column, $value) use($request, $filter, &$f) {
                /**
                 * Anything within this `if` has to do with Related Relations.
                 * E.g. `?has:products.gt:sku=1337` means filter by sku of related Products ('has:products').
                 */

                // $column='has:products.gt:sku' or $column='has:products'
                $e = explode('.', $column, 2);
                // $e=['has:products', 'gt:sku'] or $e=['has:products']
                if (isset($e[1]) || str_starts_with($column, 'has:') || str_starts_with($column, 'doesntHave:')) {
                    [$relation, $column] = $e + [1 => null];
                    // $relation='has:products', $column='gt:sku' or $column=null
                    [$relation, $operator] = array_reverse(explode(':', $relation, 2)) + [1 => 'has'];
                    // $relation='products', $operator='has'
                    if (!in_array($operator, IndexRequest::ALLOWED_RELATION_OPERATORS)) {
                          $relation = $e[0];
                          $operator = 'has';
                      }

                    // If only the relation is checked and not a relations column, when $value is falsy (not empty) flip 'has' with 'doesntHave'.
                    /** Also @see ValidatesAttributes::validateDeclined() */
                    if ($column === null && in_array($value, [0, '0', 'no', 'off', false, 'false'], true)) {
                        $operator = $operator === 'has' ? 'doesntHave' : 'has';
                    }

                    $relation = \Str::studly($relation); // First letter Cap does not matter.
                    /** @see IndexRequest::ALLOWED_RELATION_OPERATORS */
                    return match($operator) {
                        'doesntHave' => $query->whereDoesntHave($relation, function(EBuilder $query) use($value, $column, &$f) {
                            $column === null or $f($query, $column, $value);
                        }),
                        'has' => $query->whereHas($relation, function(EBuilder $query) use($value, $column, &$f) {
                            $column === null or $f($query, $column, $value);
                        }),
                        default => throw ValidationException::withMessages([$filter => "Invalid operator '$operator'."]),
                    };
                }

                /**
                 * Filters by columns on current model/table.
                 */

                // $column='gt:sku' or $column='sku'
                [$column, $operator] = array_reverse(explode(':', $column, 2)) + [1 => 'eq'];
                // $column='sku', $operator='gt' or $operator='eq'
                if (!in_array($operator, IndexRequest::ALLOWED_WHERE_OPERATORS)) {
                    $column = $e[0];
                    $operator = 'eq';
                }

                if (in_array($column, $request->nullableFields())) {
                    if ($value === 'null') {
                        $value = null;
                        $operator = 'null';
                    } else if ($value === 'notNull') {
                        $value = null;
                        $operator = 'notNull';
                    }
                }

                $studly = \Str::studly(str_replace(':', ' ', $column) . " $operator");
                if (method_exists($this, $filterByMethod = "filterBy$studly")) {
                    return $this->$filterByMethod($query, $value, $request);
                }
                if ($query->hasNamedScope($studly)) {
                    return $query->scopes([$studly => [$value]]);
                }
                $studly = \Str::studly(str_replace(':', ' ', $column)); // Without operator
                if (method_exists($this, $filterByMethod = "filterBy$studly")) {
                    return $operator === 'neq'
                        ? $query->whereNot(fn(EBuilder $q) => $this->$filterByMethod($q, $value, $request, $operator))
                        : $this->$filterByMethod($query, $value, $request, $operator);
                }
                if ($query->hasNamedScope($studly)) {
                    return $operator === 'neq'
                        ? $query->whereNot(fn(EBuilder $q) => $q->scopes([$studly => [$value, $operator]]))
                        : $query->scopes([$studly => [$value, $operator]]);
                }

                $isDynamicColumn = false;
                $model = $query->getModel();
                // Make sure your Model implements `use ModelBase;`.
                if (static::modelHasColumns($model, $column) || $isDynamicColumn = static::queryHasDynamicColumn($query, $column)) {
                    if ($isDynamicColumn) {
                        /** Because dots are replaced with underscores in query params {@see Request::create()}
                          * we can use ':' in filter definitions (e.g. 'products:id') to pass the table name.
                          * Make sure to add select like 'products.id as products:id'. */
                        $column = str_replace(':', '.', $column);
                    } else {
                        $table = $model->getTable();
                        $column = "$table.$column";
                    }

                    /** @see IndexRequest::ALLOWED_WHERE_OPERATORS */
                    return match($operator) {
                        'eq'        => is_iterable($value)
                                     ? $query->where(fn(EBuilder $q) => $q->whereIn($column, $value))
                                     : $query->where(fn(EBuilder $q) => $q->where($column, $value)),
                        'neq'       => is_iterable($value)
                                     ? $query->where(fn(EBuilder $q) => $q->whereNull($column)->orWhereNotIn($column, $value))
                                     : $query->where(fn(EBuilder $q) => $q->whereNot($column, $value)),
                        'in'        => $query->where(fn(EBuilder $q) => $q->whereIn($column, $value)),
                        'notIn'     => $query->where(fn(EBuilder $q) => $q->whereNull($column)->orWhereNotIn($column, $value)),
                        'null'      => $query->where(fn(EBuilder $q) => $q->whereNull($column)),
                        'notNull'   => $query->where(fn(EBuilder $q) => $q->whereNotNull($column)),
                        'gt'        => $query->where(fn(EBuilder $q) => $q->where($column, '>', $value)),
                        'gte'       => $query->where(fn(EBuilder $q) => $q->where($column, '>=', $value)),
                        'lt'        => $query->where(fn(EBuilder $q) => $q->where($column, '<', $value)),
                        'lte'       => $query->where(fn(EBuilder $q) => $q->where($column, '<=', $value)),

                        // Laravel does not support `HAVING IN` even though MySQL does.
                        // 'heq'       => is_iterable($value)
                        //              ? $query->having(fn(QBuilder $q) => $q->havingIn($column, $value))
                        //              : $query->having(fn(QBuilder $q) => $q->having($column, $value)),
                        // 'hneq'      => is_iterable($value)
                        //              ? $query->having(fn(QBuilder $q) => $q->havingNull($column)->orHavingNotIn($column, $value))
                        //              : $query->having(fn(QBuilder $q) => $q->havingNot($column, $value)),
                        // 'hin'       => $query->having(fn(QBuilder $q) => $q->havingIn($column, $value)),
                        // 'hnotIn'    => $query->having(fn(QBuilder $q) => $q->havingNull($column)->orHavingNotIn($column, $value)),
                        'heq'       => $query->having(fn(QBuilder $q) => $q->having($column, $value)),
                        'hneq'      => $query->having(fn(QBuilder $q) => $q->having($column, '<>', $value)),
                        'hnull'     => $query->having(fn(QBuilder $q) => $q->havingNull($column)),
                        'hnotNull'  => $query->having(fn(QBuilder $q) => $q->havingNotNull($column)),
                        'hgt'       => $query->having(fn(QBuilder $q) => $q->having($column, '>', $value)),
                        'hgte'      => $query->having(fn(QBuilder $q) => $q->having($column, '>=', $value)),
                        'hlt'       => $query->having(fn(QBuilder $q) => $q->having($column, '<', $value)),
                        'hlte'      => $query->having(fn(QBuilder $q) => $q->having($column, '<=', $value)),

                        default => throw ValidationException::withMessages([$filter => "Invalid operator '$operator'."]),
                    };
                }

            })($query, $filter, $value);
        }

        return $query;
    }

    protected function addSearchToQuery(EBuilder $query, IndexRequest $request): EBuilder
    {
        $search = $request->getSearchedValue();

        if ($search === null) {
            return $query;
        }

        $model = $this->model();
        $searchableColumns = $request->searchableColumns();

        // We have to double-check the columns actually exists in table because we cannot prepare & bind columns in SQL.
        // Make sure your model implements `use ModelBase;`.
        //TODO allow queryHasDynamicColumn() and don't use "table." in wheres when is dynamic.
        if (!static::modelHasColumns($model, ...$searchableColumns)) {
            /** Show message when in production @see Handler::prepareJsonResponse() */
            abort(500, sprintf('Invalid column added as searchable for %s.', $model::class));
        }

        // Group the columns together so the array looks like:
        // `[ 'lines' => [ 'appointments' => [ 'address' => [ 'street' => [], 'city' => [] ] ] ] ]`.
        // With which we can build one single `where exists` query using grouped `or`,
        // instead of a separate `where exists` for each searchable related column.
        $groupedColumns = ($g = function(array $columns) use(&$g) {
            $rtn = [];
            foreach ($columns as $column) {
                $explodedColumn = explode('.', $column, 2);
                $main = array_shift($explodedColumn);
                $rtn[$main] = array_merge_recursive(
                    $rtn[$main] ?? [],
                    $g($explodedColumn)
                );
            }
            return $rtn;
        })($searchableColumns);

        $hasSorts = !empty($request->getSortedValues());
        foreach (explode(' ', $search) as $searchSegment) {
            $query->where(function(EBuilder $subQuery) use($query, $groupedColumns, $hasSorts, $searchSegment) {

                // Recursively build the sub-query so `where` looks like:
                // where (
                //     exists ( select * from `addresses`
                //         where `offer_lines`.`id` = `addresses`.`offer_line_id`
                //         and (`street` like ? or `postal_code` like ? or `city` like ?)
                //     )
                //     or `priority` like ?
                // )
                $f = function(int $depth, array $columns) use($query, $hasSorts, $searchSegment, &$f) {
                    return function(EBuilder $b) use($query, $hasSorts, $searchSegment, &$f, $depth, $columns) {
                        foreach ($columns as $column => $subColumns) {
                            if (!empty($subColumns)) {
                                $b->orWhereHas($column, function(EBuilder $c) use(&$f, $depth, $subColumns) {
                                    $c->where($f($depth + 1, $subColumns));
                                });
                            } else {
                                // Gets either the main model or the related model from above if statement.
                                $model = $b->getModel();
                                $table = $model->getTable();
                                $b->orWhere("$table.$column", 'like', "%$searchSegment%");

                                 /** Also @see addSortsToQuery() */
                                //TODO add sort to related search... somehow...
                                if ($depth === 0 && !$hasSorts) {
                                    // Sort by best match if no sort parameter was given.
                                    $query->orderByRaw("coalesce(nullif(instr(`$table`.`$column`, ?), 0), 999999) asc", [$searchSegment]);
                                }
                            }
                        }
                    };
                };

                $f(0, $groupedColumns)($subQuery);
            });
        }

        return $query;
    }

    protected function addSortsToQuery(EBuilder $query, IndexRequest $request): EBuilder
    {
        $model = $this->model();
        $table = $model->getTable();

        /** Also @see addSearchToQuery() */
        foreach ($request->getSortedValues() as $column => $direction) {
            /** Based on @see Model::hasGetMutator() */
            $studlyColumn = \Str::studly($column);
            $sortByMethod = "sortBy$studlyColumn";
            if (method_exists($this, $sortByMethod)) {
                $this->$sortByMethod($query, $direction, $request);
                continue;
            }

            if ($query->hasNamedScope($sortByMethod)) {
                $query->scopes([$sortByMethod => $direction]);
                continue;
            }

            // Make sure your model implements `use ModelBase;`.
            if (static::modelHasColumns($model, $column)) {
                $query->orderBy("$table.$column", $direction);
                continue;
            }

            if (static::queryHasDynamicColumn($query, $column)) {
                $query->orderBy($column, $direction);
                continue;
            }
        }

        return $query;
    }

    /*
     *
     * With
     *
     */

    protected function withSoftDeletes(EBuilder $query, Request $request): EBuilder
    {
        if (in_array(SoftDeletes::class, class_uses_recursive($this->model))) {
            $query->withTrashed();
        }

        return $query;
    }

    protected function withDeletedBy(EBuilder $query, Request $request): EBuilder
    {
        if (in_array(SoftDeletedBy::class, class_uses_recursive($this->model))) {
            /** @see SoftDeletedBy::deletedBy() */
            $query->with('deletedBy');
        }

        return $query;
    }

    /*
     *
     * Statics
     *
     */

    /**
     * @param class-string<Model>|Model $model
     * @return string[]
     */
    public static function getModelColumns(string|Model $model): array
    {
        if (is_string($model)) {
            $model = new $model;
        }

        // Fixed in https://wiki.php.net/rfc/static_variable_inheritance
        static $modelColumnsDict = [];
        return $modelColumnsDict[$model::class] ??= (fn() =>
            // \Schema::getColumnListing($model->getTable())
            $model->getConnection()->getSchemaBuilder()->getColumnListing($model->getTable())
        )();
    }

    /** Based on @see Builder::hasColumns() */
    public static function modelHasColumns(string|Model $model, string...$columns): bool
    {
        if (is_string($model)) {
            $model = new $model;
        }

        $tableColumns = static::getModelColumns($model);

        foreach ($columns as $column) {
            if (in_array($column, $tableColumns)) {
                continue;
            }

            /** Also @see ModelRepository::addFiltersToQuery() */
            $e = explode('.', $column, 2);

            if (!isset($e[1])) {
                return false;
            }

            try {
                $relation = $model->{$e[0]}();
            } catch (\BadMethodCallException $e) {
                return false;
            }

            // Allow to error here if method did not return relation (Model instance).
            $relatedColumnExists = static::modelHasColumns($relation->getRelated(), $e[1]);

            if (!$relatedColumnExists) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a query contains a custom selected column
     * E.g. "select *, (select something from comments where comments.post_id = posts.id) as dynamic_column from posts"
     */
    public static function queryHasDynamicColumn(EBuilder|QBuilder $builder, string $column): bool
    {
        if ($builder instanceof EBuilder) {
            $builder = $builder->getQuery();
        }

        $column = preg_quote($column, '/');

        foreach ($builder->columns as $select) {
            /** Based on @see EBuilder::addSelect() */
            if ($select instanceof Expression) {
                $select = $select->getValue($builder->getGrammar());
            }

            if (preg_match("/\s+as\s+`?$column`?\b/", $select)) {
                return true;
            }
        }

        return false;
    }
}