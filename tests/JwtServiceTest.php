<?php


namespace VATSIMUK\Auth\Remote\Tests;

use Carbon\Carbon;
use VATSIMUK\Auth\Remote\Auth\UKAuthJwtService;
use VATSIMUK\Auth\Remote\Models\RemoteUser;

class JwtServiceTest extends TestCase
{
    public function testItCanGenerateAndReadTokenWithNoSecondaryPassword()
    {
        $token = UKAuthJwtService::createToken(RemoteUser::initModelWithData([
            'name_first' => 'First',
            'name_last' => 'Last',
            'has_password' => false,
            'roles' => [
                ['name' => 'Role 1']
            ],
            'all_permissions' => [
                'ukts.test.permission'
            ]
        ]), Carbon::now()->addDay()->timestamp, "eyAccessToken");

        $this->assertNotNull($token);
        $this->assertEquals('Last', $token->getClaim('name_last'));

        $this->assertInstanceOf(RemoteUser::class, UKAuthJwtService::validateTokenAndGetUser($token));
        $this->assertFalse(UKAuthJwtService::validateTokenAndGetUser($token.'1'));
    }

    public function testItCanGenerateAndReadTokenWithSecondaryPassword()
    {
        $token = UKAuthJwtService::createToken(RemoteUser::initModelWithData([
            'name_first' => 'First',
            'name_last' => 'Last',
            'has_password' => true,
            'roles' => [
                ['name' => 'Role 1']
            ],
            'all_permissions' => [
                'ukts.test.permission'
            ]
        ]), Carbon::now()->addDay()->timestamp, "eyAccessToken");

        $this->assertNotNull($token);
        $this->assertEquals('Last', $token->getClaim('name_last'));

        $this->assertFalse(UKAuthJwtService::validateTokenAndGetUser($token));
        $this->assertFalse(UKAuthJwtService::validateTokenAndGetUser($token.'1'));
    }
}
