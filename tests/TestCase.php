<?php


namespace VATSIMUK\Support\Auth\Tests;


use Illuminate\Support\Facades\Http;
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

        $defaultConfig = include(dirname(__FILE__) . '/../config/ukauth.php');

        foreach ($defaultConfig as $key => $value) {
            $app['config']->set('ukauth.' . $key, $value);
        }

        $app['config']->set('ukauth.auth_user_model', RemoteUser::class);
        $app['config']->set('ukauth.client_id', 1);
    }

    public function mockGuzzleClientResponse($responses)
    {
        if (!is_array($responses)) {
            Http::fakeSequence()
                ->whenEmpty($responses);
        } else {

            $sequence = Http::fakeSequence();
            foreach ($responses as $response) {
                $sequence->pushResponse($response);
            }

            return $sequence;
        }
    }

    public function mockGuzzleClientThrowRequestException()
    {
        Http::fake(function ($request) {
            return Http::response(null, 500);
        });
    }

    public function assertCollectionSubset($subset, $collection)
    {
        $this->assertEquals($subset, $subset->intersect($collection));
    }
}