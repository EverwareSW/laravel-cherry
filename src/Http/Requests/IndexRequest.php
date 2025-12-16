<?php

namespace Everware\LaravelCherry\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * Not abstract so can be used as dependency injectable class.
 */
class IndexRequest extends BaseRequest
{
    /*
     *
     * Overwriteables
     *
     */

    /**
     * Overwrite this method to add possible filters with their validation rules.
     *
     * @return array<string, mixed> E.g. `['filter_1' => 'rule1|rule2', 'doesntHave:someRelation.gte:some_column' => ['integer', Rule::in()]]`.
     *
     * {@see ModelRepository::addFiltersToQuery()} to see all possible comparators.
     */
    public function filters(): array
    {
        return [];
    }

    /**
     * Overwrite this method to set what columns the search query should search in.
     *
     * @return string[] E.g. `['name', 'description']`.
     */
    public function searchableColumns(): array
    {
        return [];
    }

    /**
     * Overwrite this method to change the default `?search=abc` query parameter name to something different.
     *
     * @return string
     */
    public function searchParamName(): string
    {
        return 'search';
    }

    /**
     * Overwrite this method to specify set of columns that can be sorted.
     *
     * @return string[] E.g. `['accorded_at', 'name', 'age']`.
     */
    public function sortableColumns(): array
    {
        return [];
    }

    /**
     * Overwrite this method to specify by what columns should be sorted if no sorts are given.
     *
     * @return string[] E.g. `['accorded_at,desc', 'age,asc']`.
     */
    public function defaultSortedColumns(): array
    {
        return [];
    }

    /**
     * Overwrite this method to change the default `?sorts[]=abc,desc` query parameter name to something different.
     *
     * @return string
     */
    public function sortsParamName(): string
    {
        return 'sorts';
    }

    /*
     *
     * Internals
     *
     */

    /**
     * To make sure filters are only executed with validated data.
     *
     * @internal
     * @return array<string, mixed>
     */
    public function getFilteredValues(): array
    {
        $validated = $this->validated();
        $validatedDotted = \Arr::dot($validated);

        $filters = $this->filters();

        $return = [];
        foreach ($filters as $filter => $rules) {
            if (\Arr::has($validatedDotted, $filter)) {
                $return[$filter] = \Arr::get($validatedDotted, $filter);
            } elseif (\Arr::has($validated, $filter)) {
                $return[$filter] = \Arr::get($validated, $filter);
            }
        }

        return $return;
    }

    /**
     * @internal
     * @return string|null
     */
    public function getSearchedValue(): ?string
    {
        $validated = $this->validated();
        $searchParamName = $this->searchParamName();
        return $validated[$searchParamName] ?? null;
    }

    /**
     * @internal
     * @return array<string, string>
     */
    public function getSortedValues(): array
    {
        $validated = $this->validated();
        $sortParamName = $this->sortsParamName();

        $return = [];
        $columns = empty($validated[$sortParamName])
            ? $this->defaultSortedColumns()
            : $validated[$sortParamName];
        foreach ($columns as $sort) {
            [$column, $direction] = explode(',', $sort);
            $return[$column] = $direction;
        }


        return $return;
    }

    /*
     *
     * Rules
     *
     */

    /**
     * These rules will be validated for query params when made using GET request.
     * @see BaseRequest::validateResolved()
     * @see BaseRequest::getValidatorInstance()
     * @see BaseRequest::createDefaultValidator()
     * @see Validator::passes()
     * @see Validator::validateAttribute()
     * @see Validator::getValue()
     *
     * @return array<string, string|array>
     */
    public function rules(): array
    {
        $searchParamName = $this->searchParamName();

        $rules = [
            'page' => 'nullable|integer',
            'pageSize' => 'nullable|integer|max:9999',
            $searchParamName => 'nullable|string',
        ];

        if ($sortableColumns = $this->sortableColumns()) {
            $sortParamName = $this->sortsParamName();
            $rules[$sortParamName] = 'nullable|array';
            $joinedSortableColumns = join('|', $sortableColumns);
            $rules[$sortParamName.'.*'] = ["regex:/^($joinedSortableColumns)\,(asc|desc)$/"];
        }

        $filters = $this->filters();
        $rules += $filters;

        return $rules;
    }
}
