<?php

namespace Jenssegers\Mongodb\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SoftDeletingScope extends \Illuminate\Database\Eloquent\SoftDeletingScope
{
    public function apply(Builder $builder, Model $model)
    {
        $builder->where($model->getQualifiedDeletedFlagColumn(), false);
    }

    public function extend(Builder $builder)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }

        $builder->onDelete(function (Builder $builder) {
            $column = $this->getDeletedAtColumn($builder);
            $columnDeletedFLag = $this->getDeletedFlagColumn($builder);

            return $builder->update([
                $column => $builder->getModel()->freshTimestampString(),
                $columnDeletedFLag => true,
            ]);
        });
    }

    protected function addRestore(Builder $builder)
    {
        $builder->macro('restore', function (Builder $builder) {
            $builder->withTrashed();

            return $builder->update([$builder->getModel()->getDeletedFlagColumn() => false]);
        });
    }

    protected function addWithoutTrashed(Builder $builder)
    {
        $builder->macro('withoutTrashed', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)->where(
                $model->getQualifiedDeletedFlagColumn(), false
            );

            return $builder;
        });
    }

    protected function addOnlyTrashed(Builder $builder)
    {
        $builder->macro('onlyTrashed', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)->where(
                $model->getQualifiedDeletedFlagColumn(), true
            );

            return $builder;
        });
    }

    /**
     * Get the "deleted flag" column for the builder.
     *
     * @param Builder $builder
     * @return string
     */
    protected function getDeletedFlagColumn(Builder $builder)
    {
        if (count((array)$builder->getQuery()->joins) > 0) {
            return $builder->getModel()->getQualifiedDeletedFlagColumn();
        }

        return $builder->getModel()->getDeletedFlagColumn();
    }
}
