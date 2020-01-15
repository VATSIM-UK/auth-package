<?php

namespace VATSIMUK\Support\Auth\Tests\Services;

use VATSIMUK\Support\Auth\Facades\PermissionValidity;
use VATSIMUK\Support\Auth\Services\PermissionValidityService;
use VATSIMUK\Support\Auth\Tests\TestCase;

class PermissionValidityServiceTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(PermissionValidityService::class, function ($mock) {
            $mock->shouldReceive('loadJsonPermissions')
                ->andReturn([
                    'auth' => [
                        'permissions' => [
                            'assign',
                            'view',
                        ],
                        'users' => [
                            'create',
                            'update',
                            'delete',
                            'modify' => [
                                'name',
                                'age',
                            ],
                        ],
                    ],
                ]);
        })->makePartial();
    }


    /** @test */
    public function itIdentifiesIfPermissionIsValid()
    {
        $this->assertTrue(PermissionValidity::isValidPermission('*'));
        $this->assertTrue(PermissionValidity::isValidPermission('auth.users.create'));
        $this->assertTrue(PermissionValidity::isValidPermission('auth.users.modify.age'));
        $this->assertTrue(PermissionValidity::isValidPermission('auth.permissions.*'));
        $this->assertTrue(PermissionValidity::isValidPermission('auth.permissions'));
        $this->assertTrue(PermissionValidity::isValidPermission('auth.users.modify.*'));
        $this->assertFalse(PermissionValidity::isValidPermission('auth.permissions*'));
        $this->assertFalse(PermissionValidity::isValidPermission('auth.users.mutate'));
        $this->assertFalse(PermissionValidity::isValidPermission('example.doesnt.exist'));
    }


    /** @test */
    public function itReportsIfPermissionIsGrantedFromListOfHeldPermissions()
    {
        $validPermissions = [
            'auth.permissions.view',
            'auth.users.*',
        ];

        $invalidPermissions = [
            'auth.permissions.view',
            'auth.users.edit',
        ];
        // Array Input
        $this->assertTrue(PermissionValidity::permissionSatisfiedByPermissions('auth.users.create', $validPermissions));
        $this->assertFalse(PermissionValidity::permissionSatisfiedByPermissions('auth.users.create', $invalidPermissions));

        // Collection Input
        $this->assertTrue(PermissionValidity::permissionSatisfiedByPermissions('auth.users.create', collect($validPermissions)));
        $this->assertFalse(PermissionValidity::permissionSatisfiedByPermissions('auth.users.create', collect($invalidPermissions)));

        $this->assertFalse(PermissionValidity::permissionSatisfiedByPermissions('auth.users.create', []));
    }

    /** @test */
    public function itCanDetermineIfPermissionFulfilledByWildcard()
    {
        $permissions = [
            'auth.user.*',
            'auth.permission.modify.*',
        ];

        $this->assertTrue(PermissionValidity::permissionSatisfiedByPermissions('auth.users', $permissions));
        $this->assertTrue(PermissionValidity::permissionSatisfiedByPermissions('auth.users.create', $permissions));
        $this->assertTrue(PermissionValidity::permissionSatisfiedByPermissions('auth.users.create.destroy', $permissions));

        $this->assertTrue(PermissionValidity::permissionSatisfiedByPermissions('auth.permission.modify', $permissions));
        $this->assertTrue(PermissionValidity::permissionSatisfiedByPermissions('auth.permission.modify.alter', $permissions));
        $this->assertFalse(PermissionValidity::permissionSatisfiedByPermissions('auth.permission.create', $permissions));

        $this->assertTrue(PermissionValidity::permissionSatisfiedByPermissions('can.do.anything', ['*']));
    }
}
