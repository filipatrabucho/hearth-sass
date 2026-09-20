<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

/**
 * Generic create/update/destroy for simple Domain models. A model opts
 * in by exposing activeFields() (which request() keys are writable) and
 * validationRules() (checked by the controller before calling here).
 */
class ModelServiceApi
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public function create(string $modelClass, array $data): Model
    {
        return $modelClass::create($this->onlyActiveFields($modelClass, $data));
    }

    public function update(Model $model, array $data): Model
    {
        $model->update($this->onlyActiveFields($model::class, $data));

        return $model->refresh();
    }

    public function destroy(Model $model): bool
    {
        return (bool) $model->delete();
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function onlyActiveFields(string $modelClass, array $data): array
    {
        return array_intersect_key($data, array_flip($modelClass::activeFields()));
    }
}
