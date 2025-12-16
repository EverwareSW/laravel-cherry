<?php

namespace Everware\LaravelCherry\Models\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ForeignKeyDefinition;

/**
 * @property int|null $deleted_by_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @property-read \App\Models\User|null $deletedBy
 */
trait SoftDeletedBy
{
    /**
     * Constructor
     * @see Model::bootTraits()
     * Also save who deleted the model
     */
    public static function bootSoftDeletedBy(): void
    {
        /**
         * Using 'deleted' with save() instead of solely 'deleting' because @see SoftDeletes::runSoftDelete()
         * does not update entire model, only the deleted_at column
         */
        static::deleted(function(self $model) {
            if (!$model->forceDeleting) {
                $model->{$model->getDeletedByColumn()} = \Auth::id();
                $model->save();
            }
        });

        static::registerModelEvent('restoring', function(self $model) {
            $model->{$model->getDeletedByColumn()} = null;
        });
    }

    /**
     * Based on @see SoftDeletes::getDeletedAtColumn()
     *
     * @return string
     */
    public function getDeletedByColumn(): string
    {
        return static::deletedByColumn();
    }

    /**
     * Based on @see SoftDeletes::getDeletedAtColumn()
     *
     * @return string
     */
    public function getUserModelClass(): string
    {
        return static::userModelClass();
    }

    /*
     *
     * Relations
     *
     */

    /**
     * @return BelongsTo
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo($this->getUserModelClass(), $this->getDeletedByColumn());
    }

    /*
     *
     * Migration helper
     *
     */

    /**
     * @return string
     */
    public static function deletedByColumn(): string
    {
        return 'deleted_by_id';
    }

    /**
     * The model used to return
     *
     * @return string
     */
    public static function userModelClass(): string
    {
        // Not imported (use) so when overwritten and does not exists will not cause conflicts.
        return \App\Models\User::class;
    }

    /**
     * Can be used in migrations, e.g. `SoftDeletedBy::addColumn($table);`.
     *
     * @param  Blueprint  $table
     * @param  string|null  $column
     * @return ForeignKeyDefinition
     */
    public static function addColumn(Blueprint $table, ?string $column = null): ForeignKeyDefinition
    {
        $column ??= static::deletedByColumn();

        $userClass = static::userModelClass();
        /** @var \App\Models\User $user */
        $user = (new $userClass);
        $userTable = $user->getTable();
        $userKey = $user->getKeyName();

        return $table->foreignId($column)->nullable()->constrained($userTable, $userKey)->nullOnDelete()->cascadeOnUpdate();
    }
}
