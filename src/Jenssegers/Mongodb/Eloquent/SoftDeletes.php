<?php

namespace Jenssegers\Mongodb\Eloquent;

trait SoftDeletes
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    public static function bootSoftDeletes()
    {
        static::addGlobalScope(new SoftDeletingScope);

        static::creating(function (Model $model) {
            $model->deleted_flag = false;
        });
    }

    public function initializeSoftDeletes()
    {
        if (! isset($this->casts[$this->getDeletedAtColumn()])) {
            $this->casts[$this->getDeletedAtColumn()] = 'datetime';
            $this->casts[$this->getDeletedFlagColumn()] = 'boolean';
        }
    }

    public function trashed()
    {
        return ($this->{$this->getDeletedFlagColumn()}) === true;
    }

    public function restore()
    {
        // If the restoring event does not return false, we will proceed with this
        // restore operation. Otherwise, we bail out so the developer will stop
        // the restore totally. We will clear the deleted timestamp and save.
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }

        $this->{$this->getDeletedFlagColumn()} = false;

        // Once we have saved the model, we will fire the "restored" event so this
        // developer will do anything they need to after a restore operation is
        // totally finished. Then we will return the result of the save call.
        $this->exists = true;

        $result = $this->save();

        $this->fireModelEvent('restored', false);

        return $result;
    }

    protected function runSoftDelete()
    {
        $query = $this->setKeysForSaveQuery($this->newModelQuery());

        $time = $this->freshTimestamp();

        $columns = [
            $this->getDeletedAtColumn() => $this->fromDateTime($time),
            $this->getDeletedFlagColumn() => true
        ];

        $this->{$this->getDeletedAtColumn()} = $time;
        $this->{$this->getDeletedFlagColumn()} = true;

        if ($this->timestamps && !is_null($this->getUpdatedAtColumn())) {
            $this->{$this->getUpdatedAtColumn()} = $time;

            $columns[$this->getUpdatedAtColumn()] = $this->fromDateTime($time);
        }

        $query->update($columns);

        $this->syncOriginalAttributes(array_keys($columns));
    }

    /**
     * Get the fully qualified "deleted flag" column.
     *
     * @return string
     */
    public function getQualifiedDeletedFlagColumn()
    {
        return $this->getDeletedFlagColumn();
    }

    /**
     * Get the name of the "deleted flag" column.
     *
     * @return string
     */
    public function getDeletedFlagColumn()
    {
        return defined('static::DELETED_FLAG') ? static::DELETED_FLAG : 'deleted_flag';
    }
}
