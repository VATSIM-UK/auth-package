<?php


namespace VATSIMUK\Support\Auth\Tests\Models;


use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use VATSIMUK\Support\Auth\GraphQL\Response;
use VATSIMUK\Support\Auth\Models\RemoteBuilder;
use VATSIMUK\Support\Auth\Tests\Fixtures\MockJsonResponse;
use VATSIMUK\Support\Auth\Tests\Fixtures\TestModel;
use VATSIMUK\Support\Auth\Tests\Fixtures\TestRemoteModel;
use VATSIMUK\Support\Auth\Tests\TestCase;

class RemoteRelationshipBuilderTest extends TestCase
{
    /**
     * @var RemoteBuilder
     */
    private $builder;
    /**
     * @var TestModel
     */
    private $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->class = new TestModel([
            'id' => 123,
            'foreign_key' => 456,
            'local_key' => 789
        ]);

        $this->builder = $this->class->remoteModel();
        $this->mockGuzzleClientThrowRequestException();
    }

    public function testItCanFindTheMatchingModelByResolvingRelationship()
    {
        $this->assertInstanceOf(TestRemoteModel::class, $this->class->remoteModel);
        $this->assertInstanceOf(TestRemoteModel::class, $this->class->remoteModelHasOne);
        $this->assertEquals($this->class->remoteModel->id, 456);
        $this->assertEquals($this->class->remoteModelHasOne->id, 789);
    }
}