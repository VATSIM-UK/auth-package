<?php


namespace VATSIMUK\Support\Auth\Models;


use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use VATSIMUK\Support\Auth\Exceptions\APITokenInvalidException;
use VATSIMUK\Support\Auth\GraphQL\Builder as GraphQLBuilder;

/*
 * This builder replaces the eloquent query builder for remote models. It is called when doing RemoteModel::get(), etc.
 */

class RemoteBuilder extends Builder
{
    /**
     * The model being queried.
     *
     * @var RemoteModel
     */
    protected $model;
    private $token;

    /**
     * RemoteBuilder constructor.
     *
     * @param QueryBuilder $query
     * @param string|null $token Optional Auth API token
     */
    public function __construct(QueryBuilder $query, string $token = null)
    {
        $this->token = $token;
        parent::__construct($query);
    }

    /**
     * Parses built query, and performs query
     *
     * @param array $columns
     * @return Collection
     */
    public function get($columns = ['*']): Collection
    {
        // Only support IDs for now
        $ids = [];
        foreach ($this->query->wheres as $where) {
            if ($this->getColumnNameWithoutTable($where['column']) != "id") {
                continue;
            }

            if (isset($where['values'])) {
                $ids = array_merge($ids, $where['values']);
            } else {
                $ids[] = $where['value'];
            }
        }

        if (count($ids) < 1) {
            return new Collection();
        }

        if ($this->query->columns) {
            $columns = $this->query->columns;
        }

        return $this->model::findMany($ids, $columns, $this->token);
    }


    /**
     * Find the remote model with the given ID
     *
     * @param mixed $id
     * @param array|null $columns
     * @param string|null $token Optional Auth API token for the request
     * @return RemoteModel|Collection|null
     * @throws BindingResolutionException
     * @throws APITokenInvalidException
     */
    public function find($id, $columns = [], string $token = null)
    {
        if (is_array($id) || $id instanceof Arrayable) {
            return $this->findMany($id, $columns);
        }

        $query = new GraphQLBuilder(
            $this->model->getSingleAPIMethod(),
            $this->generateParams($columns),
            "id:$id"
        );

        $response = $query->execute($token);

        return ! $response->isEmpty() ? $this->model::initModelWithData($response->getResults()) : null;
    }


    /**
     * Finds multiple remote model's for the given IDs
     *
     * @param Arrayable|array $ids
     * @param array $columns
     * @param string|null $token Optional Auth API token for the request
     * @return Collection
     * @throws BindingResolutionException
     * @throws APITokenInvalidException
     */
    public function findMany($ids, $columns = [], string $token = null): Collection
    {
        $argument = "ids:" . json_encode($ids);

        $query = new GraphQLBuilder(
            $this->model->getMultipleAPIMethod(),
            static::generateParams($columns),
            $argument
        );

        $response = $query->execute($token);

        $collection = new Collection();
        if ($response->isEmpty()) {
            return $collection;
        }

        foreach ($response->getResults() as $model) {
            $collection->push($this->model::initModelWithData($model));
        }
        return $collection;
    }

    /**
     * Execute the query and get the first result.
     *
     * @param array $columns
     * @return RemoteModel|object|static|null
     * @throws APITokenInvalidException
     * @throws BindingResolutionException
     */
    public function first($columns = [])
    {
        // Only support IDs for now
        if ($this->getColumnNameWithoutTable($this->query->wheres[0]['column']) != "id") {
            return null;
        }
        return $this->find($this->query->wheres[0]['value'], $columns, $this->token);
    }


    /**
     * Generates a list of fields to get for the user model, using defaults or supplied list of fields
     *
     * @param array $columns
     * @return array
     */
    public function generateParams($columns = []): array
    {
        if ($columns == ['*']) {
            $columns = null;
        }

        if ($columns) {
            $columns = array_filter($columns, function ($item) {
                return ! is_null($item) && $item != '';
            });
        }

        return array_unique(array_merge(['id'], ! empty($columns) ? $columns : $this->model->getDefaultFields()), SORT_REGULAR);
    }


    /**
     * Removes the table name from an SQL column
     *
     * @param string column
     * @return string
     */
    private function getColumnNameWithoutTable(string $column): string
    {
        return explode('.', $column)[1];
    }
}
