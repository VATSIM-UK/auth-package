<?php


namespace VATSIMUK\Support\Auth\Models\Relationships;


use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use VATSIMUK\Support\Auth\Exceptions\APITokenInvalidException;
use VATSIMUK\Support\Auth\Models\RemoteBuilder;
use VATSIMUK\Support\Auth\Models\RemoteModel;

class RemoteRelationshipBuilder extends RemoteBuilder
{
    /**
     * Find's the related model by ID
     *
     * @param mixed $id
     * @param array $columns
     * @param string|null $token
     * @param bool $checkAPI
     * @return Collection|RemoteModel|null
     * @throws APITokenInvalidException
     * @throws BindingResolutionException
     */
    public function find($id, $columns = [], string $token = null, bool $checkAPI = false)
    {
        // If we get null back from a relationship we are pretty sure exists (i.e. is in database), we will just return
        // the primary key
        return $this->model->find($id, $columns, $token) ?? (! $checkAPI ? $this->model::initModelWithData([
                $this->model->getKeyName() => $id
            ]) : null);
    }

    /**
     * Find's many of the related model
     *
     * @param array|Arrayable $ids
     * @param array $columns
     * @param string|null $token
     * @return Collection
     * @throws APITokenInvalidException
     * @throws BindingResolutionException
     */
    public function findMany($ids, $columns = [], string $token = null): Collection
    {
        $result = $this->model->findMany($ids, $columns, $token);

        if (count($ids) < 1) {
            return collect();
        }

        // If we get no models back from a relationship where we are expecting multiple, we will return with models
        // with just the primary key
        return $result->isNotEmpty() ? $result : collect($ids)->transform(function ($id) {
            return $this->model::initModelWithData([
                $this->model->getKeyName() => $id
            ]);
        });
    }

    /**
     * Overrides Default Function. Retrieves models via pivot (For BelongsToMany, etc.).
     *
     * @param array $columns
     * @return array|\Illuminate\Database\Eloquent\Collection|RemoteModel[]|RemoteBuilder[]
     * @throws BindingResolutionException
     */
    public function getModels($columns = ['*'])
    {
        $details = $this->findRelationshipDetails();

        // Get ID's via pivot
        $results = DB::table($details->table)->where($details->foreignPivotKey, $details->parentKey)->get();

        // Query related models
        if ($results->isEmpty()) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        $builder = $this->model->newQueryWithoutScopes();


        try {
            $models = $builder->findMany($results->pluck($details->relatedPivotKey)->all(), $columns);
        } catch (APITokenInvalidException $e) {
            //TODO: Log to BugSnag
            $models = collect();
        }

        // If the returned models from the API is empty, we will assume the API is down, and create users with just ID's
        if ($models->isEmpty()) {
            $models = collect();
            foreach ($results->pluck($details->relatedPivotKey)->all() as $relatedID) {
                $models->push($this->related::initModelWithData([
                    'id' => $relatedID
                ]));
            }
        }

        //Post-humorously add in the pivot table attributes
        $models->transform(function ($model) use ($results, $details) {
            $pivotAttributes = collect($results->firstWhere($details->relatedPivotKey, $model->getKey()))->mapWithKeys(function ($value, $key) {
                return ['pivot_' . $key => $value];
            });
            return $model->setRawAttributes($pivotAttributes->all());
        });

        return $models->all();
    }

    /**
     * Determine, for a remote model, if the given remote model exists on the local model
     *
     * @param bool $checkAPI If false, will check assert that the model's ID is assign to the local model via pivot,
     *                          etc. If true, will actually call the API and check the model also exists on that end.
     *
     * @return bool
     * @throws APITokenInvalidException
     * @throws BindingResolutionException
     */
    public function exists($checkAPI = false)
    {
        $details = $this->findRelationshipDetails();

        if (! $details->table) {
            // Likely a one-to-one relationship. As long as an id is given, it is true
            if (($key = $this->query->wheres[0]['value']) == null) {
                return false;
            }
            return $checkAPI ? $this->find($key, ['id'], null, true) != null : true;
        }

        if ($details) {
            $this->query->joins = null;
            $this->query->from = $details->table;

            $existsInPivot = $this->query->count() > 0;


            if (! $existsInPivot) {
                return false;
            }

            if (! $checkAPI) {
                return true;
            }

            //TODO: If the API is down, find will return null. We should give benefit of the doubt in this case.

            return $this->find($this->getValueForWhereColumn("{$details->table}.{$details->relatedPivotKey}"), ['id'], null, true) != null;
        }

        return parent::exists();
    }


    /**
     * Finds the database key for the model with the relationship, and loads table and foreignKeys
     *
     * @return \stdClass|null
     */
    private function findRelationshipDetails(): ?\stdClass
    {
        if (empty($this->query->wheres)) {
            return null;
        }

        if (count($this->query->wheres) > 0) {
            return (object)[
                'table' => $this->determinePivotTable(),
                'foreignPivotKey' => $this->determineForeignPivotKeyName(),
                'parentKey' => $this->determineParentModelKey(),
                'relatedPivotKey' => $this->determineRelatedPivotKeyName()
            ];
        }

        return null;
    }

    /**
     * Removes the pivot table name from a column
     *
     * @param $columnWithTable
     * @return string
     */
    private function columnWithoutTable($columnWithTable): string
    {
        $exploded = explode('.', $columnWithTable);

        if ($exploded[0] == $this->determinePivotTable()) {
            unset($exploded[0]);
        }

        return implode(".", $exploded);
    }

    private function getValueForWhereColumn($columnWithTable)
    {
        return collect($this->query->wheres)->firstWhere('column', $columnWithTable)['value'];
    }

    /**
     * Deduce the pivot table for relationship
     *
     * @return string|null
     */
    private function determinePivotTable(): ?string
    {
        return isset($this->query->joins[0]->table) ? $this->query->joins[0]->table : null;
    }

    /**
     * Deduce the foreign pivot key name for the relationship
     *
     * @return string|null
     */
    private function determineForeignPivotKeyName(): ?string
    {
        return explode('.', $this->query->wheres[0]['column'])[1];
    }

    /**
     * Deduce the related pivot key name for the relationship
     *
     * @return string|null
     */
    private function determineRelatedPivotKeyName(): ?string
    {
        if (! isset($this->query->joins[0]->wheres[0]['second'])) {
            return null;
        }

        return $this->columnWithoutTable($this->query->joins[0]->wheres[0]['second']);
    }

    /**
     * Deduce the parent model's key for the relationship
     *
     * @return string|null
     */
    private function determineParentModelKey(): ?string
    {
        return isset($this->query->wheres[0]['value']) ? $this->query->wheres[0]['value'] : $this->query->wheres[0]['values'][0];
    }
}
