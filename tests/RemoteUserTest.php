<?php


namespace VATSIMUK\Support\Auth\Tests;

use Illuminate\Database\Query\Builder;
use VATSIMUK\Support\Auth\Models\RemoteBuilder;
use VATSIMUK\Support\Auth\Models\RemoteModel;
use VATSIMUK\Support\Auth\Models\RemoteUser;

class RemoteUserTest extends TestCase
{
    public function testItCanBeConstructed()
    {
        $user = new RemoteUser(['id' => 1300001]);

        $this->assertEquals($user->id, 1300001);
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

    public function testItReturnsCustomBuilderClass(){
        $builder = RemoteUser::where('id', 1300002);
        $this->assertInstanceOf(RemoteBuilder::class, $builder);
    }

    public function testItGeneratesParamsCorrectly()
    {
        $builder = (new RemoteUser())->newQueryWithoutScopes();
        $this->assertEquals(['id', 'name_first', 'name_last'], $builder->generateParams(null));

        $this->assertEquals(['id', 'name_first', 'name_last'], $builder->generateParams(["*"]));

        $this->assertEquals(['id', 'name_first', 'name_last'], $builder->generateParams([""]));

        $this->assertEquals(['id', 'email'], $builder->generateParams(["", "email"]));

        $this->assertEquals(['id'], $builder->generateParams(["id"]));
    }
}
