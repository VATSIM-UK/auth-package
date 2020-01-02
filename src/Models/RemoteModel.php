<?php


namespace VATSIMUK\Support\Auth\Models;


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
    protected $relationshipBuilder = false;
    public $exists = true;

    /**
     * Retrieve an updated model instance from the Auth API
     *
     * @param array|null $columns
     * @param string|null $token Optional Auth API token for the request
     * @return static|null
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
     * Sets the Builder class to use
     *
     * @param Builder $query
     * @return RemoteBuilder
     */
    public function newEloquentBuilder($query): RemoteBuilder
    {
        if ($this->access_token) {
            return $this->relationshipBuilder ? new RemoteRelationshipBuilder($query, $this->access_token) : new RemoteBuilder($query, $this->access_token);
        }

        return $this->relationshipBuilder ? new RemoteRelationshipBuilder($query) : new RemoteBuilder($query);
    }

    /**
     * Creates an instance of the model with the given data filled
     *
     * @param $data
     * @return self
     */
    public static function initModelWithData($data): self
    {
        $model = new static();

        return $model->fill((array)$data);
    }


    /**
     * Get the API method for fetching a single model
     *
     * @return string
     */
    public function getSingleAPIMethod(): string
    {
        return static::$singleMethod;
    }

    /**
     * Get the API method for fetching multiple of the model
     *
     * @return string
     */
    public function getMultipleAPIMethod(): string
    {
        return static::$manyMethod;
    }

    /**
     * Get the default fields array
     *
     * @return array
     */
    public function getDefaultFields(): array
    {
        return static::$defaultFields;
    }

    /**
     * Sets the Relationship Builder boolean
     *
     * @param bool $bool
     */
    public function setRelationshipBuilder(bool $bool)
    {
        $this->relationshipBuilder = $bool;
    }
}
