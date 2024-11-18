<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Test\Integration\Model\Provider\Engine\DuoSecurity;

use Magento\Framework\App\ObjectManager;
use Magento\TestFramework\Bootstrap;
use Magento\TwoFactorAuth\Api\Data\DuoDataInterface;
use Magento\TwoFactorAuth\Api\TfaInterface;
use Magento\TwoFactorAuth\Api\UserConfigTokenManagerInterface;
use Magento\TwoFactorAuth\Model\Provider\Engine\DuoSecurity;
use Magento\TwoFactorAuth\Model\Provider\Engine\DuoSecurity\Authenticate;
use Magento\User\Model\UserFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 */
class AuthenticateTest extends TestCase
{
    /**
     * @var Authenticate
     */
    private $model;

    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var TfaInterface
     */
    private $tfa;

    /**
     * @var DuoSecurity|MockObject
     */
    private $duo;

    /**
     * @var UserConfigTokenManagerInterface
     */
    private $tokenManager;

    protected function setUp(): void
    {
        $objectManager = ObjectManager::getInstance();
        $this->userFactory = $objectManager->get(UserFactory::class);
        $this->tokenManager = $objectManager->get(UserConfigTokenManagerInterface::class);
        $this->tfa = $objectManager->get(TfaInterface::class);
        $this->duo = $this->createMock(DuoSecurity::class);
        $this->model = $objectManager->create(
            Authenticate::class,
            [
                'duo' => $this->duo,
            ]
        );
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testCreateAdminAccessTokenWithCredentials()
    {
        $username = 'admin';
        $password = 'password123';
        $passcode = '654321';

        // Mock admin token service
        $this->adminTokenService->expects($this->once())
            ->method('createAdminAccessToken')
            ->with($username, $password)
            ->willReturn('token');

        // Mock user retrieval
        $userMock = $this->createMock(UserInterface::class);
        $userMock->expects($this->any())
            ->method('getId')
            ->willReturn(123);

        $this->userFactory->expects($this->once())
            ->method('create')
            ->willReturn($userMock);

        $userMock->expects($this->once())
            ->method('loadByUsername')
            ->with($username);

        $this->userAuthenticator->expects($this->once())
            ->method('assertProviderIsValidForUser')
            ->with(123, DuoSecurity::CODE);

        // Test assertResponseIsValid (can be mocked or separately tested)
        $this->authenticate->expects($this->once())
            ->method('assertResponseIsValid')
            ->with($userMock, $username, $passcode);

        // Call the method
        $token = $this->authenticate->createAdminAccessTokenWithCredentials($username, $password, $passcode);
        $this->assertEquals('token', $token);
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testAssertResponseIsValid()
    {
        $userMock = $this->createMock(UserInterface::class);
        $username = 'admin';
        $passcode = '654321';

        $this->duo->expects($this->once())
            ->method('authorizeUser')
            ->with($username, 'passcode', ['passcode' => $passcode])
            ->willReturn(['status' => 'allow']);

        $this->alert->expects($this->never())
            ->method('event');

        // Call the method (no exception expected for valid response)
        $this->authenticate->assertResponseIsValid($userMock, $username, $passcode);
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testAssertResponseIsInvalid()
    {
        $userMock = $this->createMock(UserInterface::class);
        $userMock->expects($this->any())
            ->method('getUserName')
            ->willReturn('admin');

        $username = 'admin';
        $passcode = '123456';

        $this->duo->expects($this->once())
            ->method('authorizeUser')
            ->with($username, 'passcode', ['passcode' => $passcode])
            ->willReturn(['status' => 'deny', 'msg' => 'Invalid passcode']);

        $this->alert->expects($this->once())
            ->method('event')
            ->with(
                'Magento_TwoFactorAuth',
                'DuoSecurity invalid auth Invalid passcode',
                AlertInterface::LEVEL_WARNING,
                'admin'
            );

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Invalid response');

        // Call the method (exception expected for invalid response)
        $this->authenticate->assertResponseIsValid($userMock, $username, $passcode);
    }
}
