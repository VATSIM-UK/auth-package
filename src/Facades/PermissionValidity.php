<?php


namespace VATSIMUK\Support\Auth\Facades;


use Illuminate\Support\Facades\Facade;

class PermissionValidity extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'permissionvalidity'; }
}