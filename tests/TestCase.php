<?php


namespace VATSIMUK\Support\Auth\Tests;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use VATSIMUK\Support\Auth\Models\RemoteUser;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('ukauth.auth_user_model', RemoteUser::class);
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
}