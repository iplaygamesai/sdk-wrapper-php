<?php

namespace IPlayGames\Traits;

/**
 * Trait for converting SDK model objects to arrays
 */
trait ModelToArrayTrait
{
    /**
     * Convert a model object to array
     *
     * @param mixed $model The model to convert
     * @return array The array representation
     */
    protected function modelToArray(mixed $model): array
    {
        if ($model === null) {
            return [];
        }

        if (is_array($model)) {
            return $model;
        }

        if (method_exists($model, 'jsonSerialize')) {
            return (array) $model->jsonSerialize();
        }

        if (method_exists($model, 'toArray')) {
            return $model->toArray();
        }

        return (array) $model;
    }

    /**
     * Safely get a value from a model using getter method
     *
     * @param mixed $model The model object
     * @param string $method The getter method name (e.g., 'getStatus')
     * @param mixed $default Default value if method doesn't exist or returns null
     * @return mixed The value or default
     */
    protected function getModelValue(mixed $model, string $method, mixed $default = null): mixed
    {
        if ($model === null || !method_exists($model, $method)) {
            return $default;
        }

        $value = $model->{$method}();
        return $value ?? $default;
    }
}
