<?php


namespace VATSIMUK\Support\Auth\Tests\Fixtures;


use Illuminate\Database\Eloquent\Model;
use VATSIMUK\Support\Auth\Models\Concerns\HasRemoteRelationships;

class TestModel extends Model
{
    use HasRemoteRelationships;

    protected $guarded = [];

    public function remoteModel()
    {
        return $this->belongsTo(TestRemoteModel::class, 'foreign_key', 'owner_key');
    }

    public function remoteModelHasOne()
    {
        return $this->hasOne(TestRemoteModel::class, 'foreign_key', 'local_key');
    }

    public function remoteModelHasMany()
    {
        return $this->hasMany(TestRemoteModel::class, 'foreign_key', 'local_key');
    }
}