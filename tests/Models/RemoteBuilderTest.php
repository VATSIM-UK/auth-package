<?php

namespace VATSIMUK\Support\Auth\Tests\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use VATSIMUK\Support\Auth\GraphQL\Response;
use VATSIMUK\Support\Auth\Models\RemoteBuilder;
use VATSIMUK\Support\Auth\Tests\Fixtures\MockJsonResponse;
use VATSIMUK\Support\Auth\Tests\Fixtures\TestRemoteModel;
use VATSIMUK\Support\Auth\Tests\TestCase;

class RemoteBuilderTest extends TestCase
{
    /**
     * @var RemoteBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $class = new TestRemoteModel();

        $this->builder = $class->newQueryWithoutScopes();
    }

    public function testItCanReturnResponse()
    {
        $this->mockGuzzleClientResponse(Http::response(MockJsonResponse::successfulResponse('single'), 200));
        $response = $this->builder->returnResponse()->find(1300005, [], 'eyFakeToken');

        $this->assertInstanceOf(Response::class, $response);

        $this->mockGuzzleClientResponse(Http::response(MockJsonResponse::successfulMultipleResponse('many'), 200));
        $response = $this->builder->returnResponse()->findMany(1300005, [], 'eyFakeToken');

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testItCanReturnHydratedModel()
    {
        $this->mockGuzzleClientResponse(Http::response(MockJsonResponse::successfulResponse('single'), 200));

        $response = $this->builder->find(1300005, [], 'eyFakeToken');
        $this->assertInstanceOf(TestRemoteModel::class, $response);
    }

    public function testItCanReturnHydratedModels()
    {
        $this->mockGuzzleClientResponse(Http::response(MockJsonResponse::successfulMultipleResponse('many'), 200));

        $response = $this->builder->findMany([1300001, 1300005], [], 'eyFakeToken');

        $this->assertInstanceOf(Collection::class, $response);
        $this->assertInstanceOf(TestRemoteModel::class, $response->first());
    }

    public function testItCanAddInColumnsToQuery()
    {
        $this->builder->withColumns(['relationship.name']);
        $this->assertEquals(['id', 'name_first', 'relationship.name'], $this->builder->generateParams(['name_first']));
    }

    public function testItCanGetManyByID()
    {
        $this->mockGuzzleClientResponse(Http::response(MockJsonResponse::successfulMultipleResponse('many'), 200));
        $this->builder->setToken('eyFakeToken');
        $response = (clone $this->builder)->whereIn('id', [1300001, 1300005])->get();
        $this->assertEquals(2, count($response));

        $this->mockGuzzleClientResponse(Http::response(MockJsonResponse::successfulMultipleResponse('many'), 200));
        $response = (clone $this->builder)->where('id', 1300001)->get();
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $response);
        $this->assertFalse($response->isEmpty());

        $this->mockGuzzleClientResponse(Http::response(MockJsonResponse::successfulMultipleResponse('many'), 200));
        $response = (clone $this->builder)->whereIn('name', ['Joe', 'Jeff'])->get();
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $response);
        $this->assertTrue($response->isEmpty());
    }

    public function testItReturnsFindManyIfArrayGiven()
    {
        $this->mockGuzzleClientResponse(Http::response(MockJsonResponse::successfulMultipleResponse('many'), 200));
        $this->builder->setToken('eyFakeToken');
        $response = $this->builder->find([1300001, 1300005]);
        $this->assertEquals(2, count($response));
    }

    public function testItCanFindFirstById()
    {
        $this->mockGuzzleClientResponse(Http::response(MockJsonResponse::successfulResponse('single'), 200));
        $this->builder->setToken('eyFakeToken');
        $response = (clone $this->builder)->where('id', 1300001)->first();
        $this->assertInstanceOf(TestRemoteModel::class, $response);

        $response = $this->builder->where('name', 'Bob')->first();
        $this->assertNull($response);
    }
}
