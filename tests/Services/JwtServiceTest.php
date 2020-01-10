<?php


namespace VATSIMUK\Support\Auth\Tests\Services;

use Carbon\Carbon;
use VATSIMUK\Support\Auth\Services\JWTService;
use VATSIMUK\Support\Auth\Models\RemoteUser;
use VATSIMUK\Support\Auth\Tests\TestCase;

class JwtServiceTest extends TestCase
{
    public function testItCanGenerateAndReadTokenWithNoSecondaryPassword()
    {
        $token = JWTService::createToken(RemoteUser::initModelWithData([
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

        $this->assertInstanceOf(RemoteUser::class, JWTService::validateTokenAndGetUser($token));
        $this->assertFalse(JWTService::validateTokenAndGetUser($token.'1'));
    }

    public function testItCanGenerateAndReadTokenWithSecondaryPassword()
    {
        $token = JWTService::createToken(RemoteUser::initModelWithData([
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

        $this->assertFalse(JWTService::validateTokenAndGetUser($token));
        $this->assertFalse(JWTService::validateTokenAndGetUser($token.'1'));
    }
}
