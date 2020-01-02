<?php


namespace VATSIMUK\Support\Auth\Tests\GraphQL;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use VATSIMUK\Support\Auth\Exceptions\APITokenInvalidException;
use VATSIMUK\Support\Auth\GraphQL\Builder;
use VATSIMUK\Support\Auth\Tests\TestCase;

class BuilderTest extends TestCase
{
    /* @var Builder */
    private $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new Builder("user", ['id', 'name_first', 'ratings' => ['id', 'name']]);
    }

    public function testItCanBeExecuted()
    {
        $responses = [
            new Response(200, [], json_encode([
                    'type' => 'Bearer',
                    'access_token' => 'eyTokenHere'
                ])),
            new Response(200, [], json_encode([
                'data' => [
                    'user' => [
                        'id' => 1,
                        'name_first' => 'First',
                        'ratings' => [
                            ['id' => 1, 'name' => 'S3'],
                            ['id' => 5, 'name' => 'P1'],
                        ]
                    ]
                ]
            ])),
            new Response(500, [])
        ];

        $this->mock(Client::class, function ($mock) use ($responses) {
            $mock->shouldReceive('request')
                ->andReturnValues($responses);
        })->makePartial();

        $this->assertInstanceOf(\VATSIMUK\Support\Auth\GraphQL\Response::class, $response = $this->builder->execute());
        $this->assertFalse($response->hasErrors());
        $this->assertEquals($response->getResults()->name_first, 'First');

        // With Failed Token Retrieval
        $this->assertTrue($this->builder->execute()->hasErrors());
    }


    public function testItCanCheckAPIPulse()
    {
        $responses = [
            new Response(200, [], '{"alive":true}'),
            new Response(500, [])
        ];

        $this->mock(Client::class, function ($mock) use ($responses) {
            $mock->shouldReceive('request')
                ->andReturnValues($responses);
        })->makePartial();

        $this->assertTrue($this->builder::checkAlive());
        $this->assertFalse($this->builder::checkAlive());

    }
    
    public function testItCanComposeAGraphQLQuery()
    {
        $this->assertEquals(trim(preg_replace('/ {4}|\r/', '',
            "query {
                    user {
                        id
                        name_first
                        ratings {
                            id
                            name
                        }
                    }
                }")), $this->builder->getGraphQLQuery());
    }

    public function testItReturnsTheColumns()
    {
        $this->assertEquals(preg_replace('/ {4}|\r/', '',
            "id
                    name_first
                    ratings {
                        id
                        name
                    }
                    "), $this->builder->getColumns());
    }

    public function testItReturnsTheMethod()
    {
        $this->assertEquals("user", $this->builder->getMethod());
    }
}
