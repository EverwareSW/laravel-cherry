<?php

namespace Workbench\App\Http\Requests;

use Everware\LaravelCherry\Http\Requests\IndexRequest;

class BlogIndexRequest extends IndexRequest
{
    public function filters(): array
    {
        return [];
    }

    public function searchableColumns(): array
    {
        return [
            'title',
            'content',
        ];
    }

    public function sortableColumns(): array
    {
        return [
            'title',
            'created_at',
        ];
    }

    public function defaultSortedColumns(): array
    {
        return [
            'created_at,desc',
        ];
    }
}