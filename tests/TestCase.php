<?php


namespace VATSIMUK\Support\Auth\Tests;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Collection;
use VATSIMUK\Support\Auth\Models\RemoteUser;
use VATSIMUK\Support\Auth\UKAuthServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            UKAuthServiceProvider::class
        ];
    }

    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $defaultConfig = include(dirname(__FILE__).'/../config/ukauth.php');

        foreach ($defaultConfig as $key => $value){
            $app['config']->set('ukauth.'.$key, $value);
        }

        $app['config']->set('ukauth.auth_user_model', RemoteUser::class);
        $app['config']->set('ukauth.client_id', 1);
    }

    public function mockGuzzleClientResponse($responses)
    {
        $this->mock(Client::class, function ($mock) use ($responses) {
            if(!is_array($responses)){
                $mock->shouldReceive('request')
                ->andReturn($responses);
            }else{
                $mock->shouldReceive('request')
                    ->andReturnValues($responses);
            }
        })->makePartial();
    }

    public function mockGuzzleClientThrowRequestException()
    {
        $this->mock(Client::class, function ($mock) {
            $mock->shouldReceive('request')
                ->andThrow(new RequestException(null, new Request('post', 'a/call')));
        })->makePartial();
    }

    public function assertCollectionSubset($subset, $collection)
    {
        $this->assertEquals($subset, $subset->intersect($collection));
    }
}