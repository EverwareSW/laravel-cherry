<?php

namespace Workbench\App\Http\Controllers;

use Everware\LaravelCherry\Http\Controllers\Traits\ActionPermissionProtection;
use Everware\LaravelCherry\Models\Repositories\ModelRepository;
use Illuminate\Routing\Controller;
use Spatie\LaravelData\PaginatedDataCollection;
use Workbench\App\Data\BlogResponseData;
use Workbench\App\Enums\PermissionEnum;
use Workbench\App\Http\Requests\BlogIndexRequest;
use Workbench\App\Models\Blog;

class BlogController extends Controller
{
    use ActionPermissionProtection;

    public function __construct()
    {
        $this->protectActionsUsingPermissions(PermissionEnum::BlogsIndex);
    }

    public function index(BlogIndexRequest $request)
    {
        $repo = app(ModelRepository::class, ['model' => Blog::class]);
        $data = $repo->paginate($request);
        return BlogResponseData::collect($data, PaginatedDataCollection::class);
    }
}