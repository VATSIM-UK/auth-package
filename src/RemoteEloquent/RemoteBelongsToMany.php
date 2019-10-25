<?php


namespace VATSIMUK\Auth\Remote\RemoteEloquent;


use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class RemoteBelongsToMany extends BelongsToMany
{
    /**
     * Execute the query as a "select" statement.
     *
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get($columns = ['*'])
    {
        // First we'll add the proper select columns onto the query so it is run with
        // the proper columns. Then, we will get the results and hydrate out pivot
        // models with the result of those columns as a separate model relation.
        $builder = $this->query->applyScopes();

        $columns = $builder->getQuery()->columns ? [] : $columns;

        // Get ID's via pivot
        $results = DB::table($this->table)->where($this->foreignPivotKey, $this->findParentKey())->get();

        // Query related models
        if ($results->isEmpty()) {
            return new Collection();
        }

        $models = $this->related::findMany($results->pluck($this->relatedPivotKey)->all(), $columns)->all();

        // Add in the pivot attributes
        foreach ($models as $key => $model) {
            $pivotRaw = $results->where($this->relatedPivotKey, $model->getKey())->first();
            $model->setRelation($this->accessor, $this->newExistingPivot(get_object_vars($pivotRaw)));
        }

        // If we actually found models we will also eager load any relationships that
        // have been specified as needing to be eager loaded. This will solve the
        // n + 1 query problem for the developer and also increase performance.
        if (count($models) > 0) {
            $models = $builder->eagerLoadRelations($models);
        }

        return $this->related->newCollection($models);
    }

    public function hasByID($id)
    {
        if(is_object($id)){
            $id = $id->id;
        }
        return DB::table($this->table)
            ->where($this->foreignPivotKey, $this->findParentKey())
            ->where($this->relatedPivotKey, $id)
            ->count() > 0;
    }

    private function findParentKey()
    {
        if ($this->parent->id) {
            return $this->parent->id;
        }

        if (empty($this->query->getQuery()->wheres)) {
            return null;
        }

        foreach ($this->query->getQuery()->wheres as $where) {
            if ($where['column'] == "$this->table.$this->foreignPivotKey") {
                return $where['values'][0];
            }
        }
        return null;
    }
}
