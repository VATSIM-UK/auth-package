<?php

namespace VATSIMUK\Support\Auth\Test\GraphQL;

use Illuminate\Database\Eloquent\Collection;
use Orchestra\Testbench\TestCase;
use VATSIMUK\Support\Auth\GraphQL\Builder as PackageBuilder;
use VATSIMUK\Support\Auth\GraphQL\Response as PackageResponse;
use VATSIMUK\Support\Auth\Models\RemoteUser;
use VATSIMUK\Support\Auth\Tests\Fixtures\MockJsonResponse;

class ResponseTest extends TestCase
{
    protected $builder;
    protected $normalResponse;
    protected $normalMultipleResponse;
    protected $erroredResponse;
    protected $emptyDataResponse;
    protected $unauthenticatedResponse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new PackageBuilder('user', ["name_first", "name_last",]);
        $this->erroredResponse = new PackageResponse(MockJsonResponse::erroredResponse(), $this->builder);
        $this->normalResponse = new PackageResponse(MockJsonResponse::successfulResponse(), $this->builder);
        $this->normalMultipleResponse = new PackageResponse(MockJsonResponse::successfulMultipleResponse(), $this->builder);
        $this->emptyDataResponse = new PackageResponse(MockJsonResponse::emptyResponse(), $this->builder);
        $this->unauthenticatedResponse = new PackageResponse(MockJsonResponse::unauthenticatedResponse(), $this->builder);
    }

    /** @test */
    public function itCorrectlyDetectsWhenErrorsAreInTheResponse()
    {
        $this->assertFalse($this->normalResponse->hasErrors());
        $this->assertTrue($this->erroredResponse->hasErrors());
    }

    /** @test */
    public function itCorrectlyDetectsWhenTheResponseIsEmpty()
    {
        $this->assertFalse($this->normalResponse->isEmpty());
        $this->assertFalse($this->erroredResponse->isEmpty());
        $this->assertTrue($this->emptyDataResponse->isEmpty());
    }

    /** @test */
    public function itHandlesEmptyResponseInGetResults()
    {
        $this->assertNull($this->emptyDataResponse->getResults());
        $this->assertNull($this->erroredResponse->getResults());
    }

    /** @test */
    public function itRetrievesFieldsCorrectlyFromNormalResponse()
    {
        $expected = [
            'id' => 1300005,
            'name_first' => '5th',
            'name_last' => 'Test',
            'email' => 'joe.bloggs@example.org'
        ];

        $this->assertInstanceOf(\stdClass::class, $this->normalResponse->getResults());
        $this->assertEquals((array)$this->normalResponse->getResults(), $expected);
    }

    /** @test */
    public function itExtractsTheErrorsFromBadResponse()
    {
        $firstErrorIndex = 0;
        $expected = ['message' => "There was an error"];

        $this->assertEquals((array)$this->erroredResponse->getErrors()[$firstErrorIndex], $expected);
        $this->assertNull($this->normalResponse->getErrors());
    }

    /** @test */
    public function itCanHydrateAModelFromResults()
    {
        $this->assertNull($this->emptyDataResponse->getHydratedResults(RemoteUser::class));

        $this->assertInstanceOf(RemoteUser::class, $this->normalResponse->getHydratedResults(RemoteUser::class));
        $this->assertInstanceOf(RemoteUser::class, $this->normalResponse->getHydratedResults(new RemoteUser()));

        $this->assertInstanceOf(Collection::class, $this->normalMultipleResponse->getHydratedResults(new RemoteUser()));
        $this->assertInstanceOf(RemoteUser::class, $this->normalMultipleResponse->getHydratedResults(new RemoteUser())->first());
    }

    /** @test */
    public function itCanDetermineIfUnauthenticated()
    {
        $this->assertTrue($this->unauthenticatedResponse->hasUnauthenticatedError());
        $this->assertFalse($this->erroredResponse->hasUnauthenticatedError());
    }
}
