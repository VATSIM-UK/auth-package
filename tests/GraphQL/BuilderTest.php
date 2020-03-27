<?php

namespace VATSIMUK\Support\Auth\Tests\GraphQL;

use Illuminate\Support\Facades\Http;
use VATSIMUK\Support\Auth\Exceptions\APITokenInvalidException;
use VATSIMUK\Support\Auth\GraphQL\Builder;
use VATSIMUK\Support\Auth\Tests\Fixtures\MockJsonResponse;
use VATSIMUK\Support\Auth\Tests\TestCase;

class BuilderTest extends TestCase
{
    /* @var Builder */
    private $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new Builder('user', ['id', 'id', 'name_first', 'ratings' => ['id', 'name'], 'bans.body']);
    }

    public function testItCanBeExecuted()
    {
        Http::fakeSequence()
            ->pushResponse(Http::response(null, 500))
            ->pushResponse(Http::response(MockJsonResponse::successfulResponse(), 200))
            ->pushResponse(Http::response(null, 500));

        // Test token retrieving
        $this->assertFalse($this->builder->execute()->isOk());

        $this->assertInstanceOf(\VATSIMUK\Support\Auth\GraphQL\Response::class, $response = $this->builder->execute('eyTokenHere'));
        $this->assertFalse($response->hasErrors());
        $this->assertEquals($response->getResults()->name_first, '5th');

        // With Failed Token Retrieval
        $this->assertTrue(($response = $this->builder->execute())->hasErrors());
        $this->assertFalse($response->isOk());
    }

    public function testItHandlesIncorrectAPIResponse()
    {
        $responses = [
            Http::response([
                'type' => 'Bearer',
                'access_token' => 'eyTokenHere',
            ], 200),
            Http::response('just a string, not json', 200),
            Http::response(MockJsonResponse::unauthenticatedResponse(), 200),
        ];

        $this->mockGuzzleClientResponse($responses);
        $this->assertFalse(($response = $this->builder->execute())->isOk());
        $this->assertEquals('Unable to parse API response', $response->getErrors()[0]['message']);

        $this->expectException(APITokenInvalidException::class);
        $this->builder->execute();
    }

    public function testItCanCheckAPIPulse()
    {
        $responses = [
            Http::response('{"alive":true}', 200),
            Http::response(null, 500),
        ];

        $this->mockGuzzleClientResponse($responses);

        $this->assertTrue($this->builder::checkAlive());
        $this->assertFalse($this->builder::checkAlive());
    }

    public function testItCanComposeAGraphQLQuery()
    {
        $this->assertEquals(trim(preg_replace('/ {4}|\r/', '',
            'query {
                    user {
                        id
                        name_first
                        ratings {
                            id
                            name
                        }
                        bans {
                            body
                        }
                    }
                }')), $this->builder->getGraphQLQuery());
    }

    public function testItReturnsTheMethod()
    {
        $this->assertEquals('user', $this->builder->getMethod());
    }
}
