<?php


namespace VATSIMUK\Auth\Remote\Tests;


use VATSIMUK\Auth\Remote\Models\RemoteUser;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('ukauth.auth_user_model', RemoteUser::class);
    }
}