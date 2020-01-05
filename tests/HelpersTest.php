<?php


namespace VATSIMUK\Support\Auth\Tests;


class HelpersTest extends TestCase
{

    private $data;

    protected function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'tree1' => [
                'subtree1' => 'a subtree 1 value'
            ],
            'tree2' => 'tree 2 value',
            'tree1' => [
                'subtree1' => [
                    'subsubtree1' => 'a subsubtree 1 value'
                ],
            ],
        ];
    }

    public function testDataHas()
    {

        $this->assertTrue(data_has($this->data, 'tree1.subtree1'));
        $this->assertFalse(data_has($this->data, 'tree1.subtree1.0'));

        $this->assertTrue(data_has($this->data, 'tree2'));
        $this->assertTrue(data_has($this->data, '*'));
        $this->assertTrue(data_has($this->data, 'tree1.subtree1.subsubtree1'));
    }

    public function testArrayToDot()
    {
        $this->assertEquals([
            'user.bans.body',
            'user.bans.reason',
            'user.name'
        ], array_to_dot([
            'user' => [
                'name',
                'bans' => [
                    'body',
                    'reason'
                ]
            ]
        ])->all());
    }
}