<?php


namespace VATSIMUK\Support\Auth\Models;


use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use VATSIMUK\Support\Auth\Exceptions\APITokenInvalidException;
use VATSIMUK\Support\Auth\GraphQL\Builder as GraphQLBuilder;
use VATSIMUK\Support\Auth\GraphQL\Response;

/*
 * This builder replaces the eloquent query builder for remote models. It is called when doing RemoteModel::get(), etc.
 */

class RemoteBuilder extends Builder
{
    /**
     * Whether or not to return the model(s) or the response object
     *
     * @var bool
     */
    private $returnResponse = false;

    /**
     * The model being queried.
     *
     * @var RemoteModel
     */
    protected $model;

    /**
     * Optional API call token
     *
     * @var string|null
     */
    private $token;

    /**
     * Columns to add to the query (in addition to those specified in the find/get function)
     *
     * @var array
     */
    private $queryColumns = [];

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
     * Makes the outcome of the query a Response object instead of a model
     * Can be chained, e.g. RemoteModel::returnResponse->find(1)
     *
     * @return RemoteBuilder
     */
    public function returnResponse(): self
    {
        $this->returnResponse = true;
        return $this;
    }

    /**
     * Adds in columns to be retrieved in the API call in addition to manually specified ones.
     * Mostly used for scoping on the model.
     * Can be chained, e.g. RemoteModel::withColumns(["atcRating.code"])->find(1)
     *
     * @param array $columns
     * @return RemoteBuilder
     */
    public function withColumns(array $columns): self
    {
        $this->queryColumns = array_merge($this->queryColumns, $columns);
        return $this;
    }

    /**
     * Parses built query, and performs query
     *
     * @param array $columns
     * @return Collection
     * @throws APITokenInvalidException
     * @throws BindingResolutionException
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

        return $this->findMany($ids, $columns, $this->token);
    }


    /**
     * Find the remote model with the given ID
     *
     * @param mixed $id
     * @param array|null $columns
     * @param string|null $token Optional Auth API token for the request
     * @return RemoteModel|Collection|Response|null
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

        if($this->returnResponse){
            return $response;
        }

        return $response->getHydratedResults($this->model);
    }


    /**
     * Finds multiple remote model's for the given IDs
     *
     * @param Arrayable|array $ids
     * @param array $columns
     * @param string|null $token Optional Auth API token for the request
     * @return Collection|Response
     * @throws BindingResolutionException
     * @throws APITokenInvalidException
     */
    public function findMany($ids, $columns = [], string $token = null)
    {
        $argument = "ids:" . json_encode($ids);

        $query = new GraphQLBuilder(
            $this->model->getMultipleAPIMethod(),
            $this->generateParams($columns),
            $argument
        );

        $response = $query->execute($token);


        if($this->returnResponse){
            return $response;
        }

        return $response->getHydratedResults($this->model) ?? collect();
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

        if(count($this->queryColumns) > 0){
            $columns = $columns ? array_merge($columns, $this->queryColumns) : $this->queryColumns;
        }

        if ($columns) {
            $columns = array_filter($columns, function ($item) {
                return ! is_null($item) && $item != '';
            });
        }

        return array_unique(
            array_merge(
                ['id'], ! empty($columns) ? $columns : $this->model->getDefaultFields()
            ), SORT_REGULAR);
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
