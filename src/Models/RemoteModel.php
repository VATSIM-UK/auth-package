<?php

namespace VATSIMUK\Support\Auth\Models;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use VATSIMUK\Support\Auth\Exceptions\APITokenInvalidException;
use VATSIMUK\Support\Auth\Models\Relationships\RemoteRelationshipBuilder;

abstract class RemoteModel extends Model
{
    protected static $unguarded = true;
    protected static $singleMethod;
    protected static $manyMethod;
    protected static $defaultFields = [];
    public $exists = true;
    protected $relationshipBuilder = false;

    public function __construct(array $attributes = [])
    {
        if (! isset(static::$singleMethod)) {
            throw new Exception('RemoteModels must define the $singleMethod property');
        }
        if (! isset(static::$manyMethod)) {
            throw new Exception('RemoteModels must define the $manyMethod property');
        }

        parent::__construct($attributes);
    }

    /**
     * Like doing "loadMissing" for eloquent relationships, but eager loads missing attributes on the model
     * Should be a 1D array. For relationships, use "dot" notation (e.g. relationship.name).
     *
     * @param string[] $attributes
     * @param string|null $token Optional API token, otherwise machine-machine will be used
     * @return RemoteModel
     * @throws APITokenInvalidException
     * @throws BindingResolutionException
     */
    public function loadMissingAttributes(array $attributes, string $token = null, $nullAsArray = false): self
    {
        // Convert all arrays into dot notation
        $attributes = collect($attributes)->map(function ($attribute, $key) {
            if (is_array($attribute)) {
                return array_to_dot([$key => $attribute]);
            }

            return $attribute;
        })->flatten();

        // Remove attributes that have already been loaded
        $attributes = $attributes->reject(function ($attribute) {
            if (data_has($this->attributes, $attribute)) {
                return true;
            }

            return false;
        })->each(function ($attribute) use ($nullAsArray) {
            // Preset the value to ensure no infinite loop if API unavailable
            if ($nullAsArray) {
                data_set($this->attributes, explode('.', $attribute)[0], null);

                return;
            }
            data_set($this->attributes, $attribute, null);
        });

        $fetchedModel = $this->newQueryWithoutScopes()->find($this->getKey(), $attributes->all(), $token);

        if (! $fetchedModel) {
            return $this;
        }

        $this->attributes = array_merge($this->attributes, $fetchedModel->attributes);

        return $this;
    }

    /**
     * Returns, or fetches if not set, the attribute for the model.
     *
     * @param string $attribute
     * @param string|null $token Optional API token, otherwise machine-machine will be used
     * @return mixed
     * @throws APITokenInvalidException
     * @throws BindingResolutionException
     */
    public function attribute(string $attribute, string $token = null)
    {
        if (! data_get($this, $attribute)) {
            $this->loadMissingAttributes([$attribute], $token);
        }

        return data_get($this, $attribute);
    }

    /**
     * Retrieve an updated model instance from the Auth API.
     *
     * @param array|null $columns
     * @param string|null $token Optional Auth API token for the request
     * @return RemoteModel|null
     * @throws BindingResolutionException
     * @throws APITokenInvalidException
     */
    public function fresh($columns = [], string $token = null)
    {
        return static::newQueryWithoutScopes()
                ->find($this->getKey(), $columns, $token) ?? $this;
    }

    /**
     * @return RemoteBuilder|Model
     */
    public function newQueryWithoutScopes()
    {
        return parent::newQueryWithoutScopes();
    }

    /**
     * Sets the Builder class to use.
     *
     * @param Builder $query
     * @return RemoteBuilder
     */
    public function newEloquentBuilder($query): RemoteBuilder
    {
        return $this->relationshipBuilder ? new RemoteRelationshipBuilder($query) : new RemoteBuilder($query);
    }

    /**
     * Creates an instance of the model with the given data filled.
     *
     * @param $data
     * @return self
     */
    public static function initModelWithData($data)
    {
        return resolve(static::class)->newInstance((array) $data);
    }

    /**
     * Get the API method for fetching a single model.
     *
     * @return string
     */
    public function getSingleAPIMethod(): string
    {
        return static::$singleMethod;
    }

    /**
     * Get the API method for fetching multiple of the model.
     *
     * @return string
     */
    public function getMultipleAPIMethod(): string
    {
        return static::$manyMethod;
    }

    /**
     * Get the default fields array.
     *
     * @return array
     */
    public function getDefaultFields(): array
    {
        return static::$defaultFields;
    }

    /**
     * Sets the Relationship Builder boolean.
     *
     * @param bool $bool
     */
    public function setRelationshipBuilder(bool $bool)
    {
        $this->relationshipBuilder = $bool;
    }
}
