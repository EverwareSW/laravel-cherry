<?php

namespace Everware\LaravelCherry\Http\Controllers\Traits;

trait ActionPermissionProtection
{
    /**
     * Protect all public action methods using permissions named 'prefix.action' (e.g. 'orders.show').
     * Call this method from Controller constructor, e.g. `$this->protectActionsUsingPermissions(PermissionEnum::ordersIndex)`.
     * Works with https://spatie.be/docs/laravel-permission
     */
    protected function protectActionsUsingPermissions(string|\StringBackedEnum $namePrefix, array $exclude = []): void
    {
        if ($namePrefix instanceof \StringBackedEnum) {
            $namePrefix = $namePrefix->value;
        }

        $namePrefix = \Str::beforeLast($namePrefix, '.');

        foreach ($this->getActionMethods() as $method) {
            $kebab = \Str::kebab($method);
            if (!in_array($kebab, $exclude)) {
                $this->middleware("can:$namePrefix.$kebab")->only($method);
            }
        }
    }

    /**
     * Return all public methods defined on the actual (child) Controller without any of the parent methods.
     *
     * @return string[]
     */
    protected function getActionMethods(): array
    {
        $return = [];

        $c = new \ReflectionClass(static::class);
        foreach ($c->getMethods() as $m) {
            if ($m->isPublic() && $m->class === static::class && !\Str::startsWith($m->name, '__')) {
                $return[] = $m->name;
            }
        }

        return $return;
    }
}
