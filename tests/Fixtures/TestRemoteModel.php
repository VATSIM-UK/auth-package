<?php


namespace VATSIMUK\Support\Auth\Tests\Fixtures;


use VATSIMUK\Support\Auth\Models\Concerns\HasRemoteRelationships;
use VATSIMUK\Support\Auth\Models\RemoteModel;
use VATSIMUK\Support\Auth\Models\RemoteUser;

class TestRemoteModel extends RemoteModel
{
    use HasRemoteRelationships;

    protected static $singleMethod = "single";
    protected static $manyMethod = "many";

    public function belongsToManyRelation()
    {
        return $this->belongsToMany(RemoteUser::class);
    }

    public function hasManyRelation()
    {
        return $this->hasMany(RemoteUser::class);
    }

    public function createNewRelatedInstance($model)
    {
        return $this->newRelatedInstance($model);
    }

}