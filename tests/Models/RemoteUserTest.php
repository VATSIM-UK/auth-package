<?php

namespace VATSIMUK\Support\Auth\Tests\Models;

use Illuminate\Support\Facades\Http;
use VATSIMUK\Support\Auth\Models\RemoteBuilder;
use VATSIMUK\Support\Auth\Models\RemoteUser;
use VATSIMUK\Support\Auth\Tests\Fixtures\MockJsonResponse;
use VATSIMUK\Support\Auth\Tests\TestCase;

class RemoteUserTest extends TestCase
{
    public function testItCanBeConstructed()
    {
        $user = new RemoteUser(['id' => 1300001]);

        $this->assertEquals($user->getKey(), 1300001);
    }

    public function testItCanBeInitialisedWithData()
    {
        $data = [
            'id' => 13000001,
            'name_first' => 'Joe',
            'name_last' => 'Bloggs',
            'email' => 'joe.bloggs@example.org',
        ];

        $user = RemoteUser::initModelWithData($data);

        foreach ($data as $key => $data) {
            $this->assertEquals($user->{$key}, $data);
        }
    }

    public function testItCanRetrieveByAccessToken()
    {
        $this->mockGuzzleClientResponse(Http::response(MockJsonResponse::successfulAuthUserResponse(), 200));
        $user = RemoteUser::findWithAccessToken('eyMyAuthAccessToken');

        $this->assertEquals('5th', $user->name_first);
    }

    public function testItCanLoadMissingAttributes()
    {
        $model = new RemoteUser([
            'id' => 123,
        ]);

        $this->assertNull($model->name_first);

        $this->mockGuzzleClientResponse(Http::response([
            'data' => [
                'user' => [
                    'atcRating' => [
                        'code' => 'C1',
                    ],
                    'id' => 123,
                    'name_first' => 'Joe',
                ],
            ],
        ], 200));

        $response = $model->loadMissingAttributes([
            'atcRating' => ['code'],
            'id',
            'name_first',
        ], 'eyFakeToken');

        $this->assertInstanceOf(RemoteUser::class, $response);
        $this->assertEquals('Joe', $response->name_first);
    }

    public function testItCanLoadMissingAttributesHandlesDownAPI()
    {
        $model = new RemoteUser([
            'id' => 123,
        ]);

        $this->mockGuzzleClientThrowRequestException();

        $response = $model->loadMissingAttributes([
            'atcRating' => ['code'],
            'id',
            'email',
        ]);

        $this->assertInstanceOf(RemoteUser::class, $response);
        $this->assertEquals(123, $response->id);
        $this->assertNull($response->atcRating->code);
        $this->assertEquals(123, $response->name); // Test that name falls back to ID when first and last are null
        $this->assertNull($response->email);
    }

    public function testItCanReturnOrFetchAttribute()
    {
        $model = new RemoteUser([
            'id' => 123,
            'name_first' => 'Joe',
            'activeBan' => [
                'body' => 'Was Naughty',
            ],
        ]);

        $this->assertEquals('Joe', $model->attribute('name_first'));
        $this->assertEquals('Was Naughty', $model->attribute('activeBan.body'));

        $this->mockGuzzleClientThrowRequestException();
        $this->assertNull($model->attribute('activeBan.reason'));
    }

    public function testItCanRetrieveFreshInstance()
    {
        $model = new RemoteUser([
            'id' => 123,
            'name_first' => 'Joe',
        ]);

        $responses = [
            Http::response(null, 500),
            Http::response(MockJsonResponse::successfulResponse(), 200),
        ];

        $this->mockGuzzleClientResponse($responses);
        $newModel = $model->fresh(['name_first', 'name_last', 'email'], 'eyFakeToken');
        $this->assertEquals($model, $newModel);

        $model = $model->fresh(['name_first', 'name_last', 'email'], 'eyFakeToken');

        $this->assertEquals('5th Test', $model->name);
        $this->assertEquals('joe.bloggs@example.org', $model->email);
    }

    public function testItReturnsCustomBuilderClass()
    {
        $builder = RemoteUser::where('id', 1300002);
        $this->assertInstanceOf(RemoteBuilder::class, $builder);
    }

    public function testItGeneratesParamsCorrectly()
    {
        $builder = (new RemoteUser())->newQueryWithoutScopes();
        $this->assertEquals(['id', 'name_first', 'name_last'], $builder->generateParams(null));

        $this->assertEquals(['id', 'name_first', 'name_last'], $builder->generateParams(['*']));

        $this->assertEquals(['id', 'name_first', 'name_last'], $builder->generateParams(['']));

        $this->assertEquals(['id', 'email'], $builder->generateParams(['', 'email']));

        $this->assertEquals(['id'], $builder->generateParams(['id']));
    }

    public function testGetters()
    {
        $model = new RemoteUser();
        $this->assertEquals('user', $model->getSingleAPIMethod());
        $this->assertEquals('users', $model->getMultipleAPIMethod());
    }

    public function testWithRatingsScope()
    {
        $builder = (new RemoteUser())->withRatings();

        $this->assertEquals(RemoteUser::$RATINGS_SCHEMA, $builder->generateParams()[1]['atcRating']);
        $this->assertEquals(RemoteUser::$RATINGS_SCHEMA, $builder->generateParams()[2]['pilotRatings']);
    }

    public function testItCanRetrieveRatingsLazyLoaded()
    {
        $this->mockGuzzleClientThrowRequestException();
        $user = new RemoteUser();
        $this->assertEmpty($user->attributesToArray());

        $this->assertNull($user->pilotRatings);
        $this->assertNotEmpty($user->attributesToArray());

        $user = new RemoteUser();
        $this->assertNull($user->atcRating->code);
        $this->assertNotEmpty($user->attributesToArray());
    }

    public function testItCanHavePermissions()
    {
        $user = RemoteUser::initModelWithData([
            'all_permissions' => [
                'auth.user.create',
                'auth.bans.*',
            ],
        ]);

        $this->assertTrue($user->can('auth.user.create'));
        $this->assertTrue($user->can('auth.bans.modify.repeal'));
        $this->assertFalse($user->can('auth.permissions.add'));
    }
}
