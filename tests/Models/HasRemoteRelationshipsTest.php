<?php


namespace VATSIMUK\Support\Auth\Tests\Models;


use Mockery\MockInterface;
use VATSIMUK\Support\Auth\Models\RemoteUser;
use VATSIMUK\Support\Auth\Tests\Fixtures\TestModel;
use VATSIMUK\Support\Auth\Tests\Fixtures\TestRemoteModel;
use VATSIMUK\Support\Auth\Tests\TestCase;

class HasRemoteRelationshipsTest extends TestCase
{
    /**
     * @var TestRemoteModel
     */
    private $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->model = new TestRemoteModel();
    }

    public function testCustomInstanceCreation()
    {
        $this->assertInstanceOf(RemoteUser::class, $this->model->createNewRelatedInstance(RemoteUser::class));

        $mockedUser = $this->spy(RemoteUser::class, function (MockInterface $mock){
            $mock->shouldReceive('setRelationshipBuilder')->once();
        });

        $this->assertInstanceOf(RemoteUser::class, $this->model->createNewRelatedInstance($mockedUser));

        $mockedNormalModel = $this->spy(TestModel::class, function (MockInterface $mock){
            $mock->shouldNotReceive('setRelationshipBuilder');
        });

        $this->model->createNewRelatedInstance($mockedNormalModel);
    }


}