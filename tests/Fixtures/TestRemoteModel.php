<?php


namespace VATSIMUK\Support\Auth\Tests\Fixtures;


use VATSIMUK\Support\Auth\Models\RemoteModel;

class TestRemoteModel extends RemoteModel
{
    protected static $singleMethod = "single";
    protected static $manyMethod = "many";
}