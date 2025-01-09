<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Test\Integration\Model\Provider\Engine\DuoSecurity;

use Magento\Framework\App\ObjectManager;
use Magento\TestFramework\Bootstrap;
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
        $this->tokenManager = $objectManager->get(UserConfigTokenManagerInterface::class);
        $this->tfa = $objectManager->get(TfaInterface::class);
        $this->duo = $this->createMock(DuoSecurity::class);
        $this->userFactory = $objectManager->get(UserFactory::class);
        $this->model = $objectManager->create(
            Authenticate::class,
            [
                'duo' => $this->duo,
            ]
        );
    }

    /**
     * @magentoConfigFixture default/twofactorauth/general/force_providers duo_security
     * @magentoConfigFixture default/twofactorauth/duo/client_id ABCDEFGHIJKLMNOPQRST
     * @magentoConfigFixture default/twofactorauth/duo/client_secret abcdefghijklmnopqrstuvwxyz0123456789abcd
     * @magentoConfigFixture default/twofactorauth/duo/integration_key abc123
     * @magentoConfigFixture default/twofactorauth/duo/api_hostname test.duosecurity.com
     * @magentoConfigFixture default/twofactorauth/duo/secret_key abc123
     * @magentoDataFixture Magento/User/_files/user_with_role.php
     */
    public function testVerifyInvalidCredentials()
    {
        $this->expectException(\Magento\Framework\Exception\AuthenticationException::class);
        $this->tfa->getProviderByCode(DuoSecurity::CODE)
            ->activate($this->getUserId());
        $this->duo
            ->expects($this->never())
            ->method('authorizeUser');
        $this->model->createAdminAccessTokenWithCredentialsAndPasscode(
            'adminUser',
            'abc',
            '123456'
        );
    }

    /**
     * @magentoConfigFixture default/twofactorauth/general/force_providers duo_security
     * @magentoConfigFixture default/twofactorauth/duo/client_id ABCDEFGHIJKLMNOPQRST
     * @magentoConfigFixture default/twofactorauth/duo/client_secret abcdefghijklmnopqrstuvwxyz0123456789abcd
     * @magentoConfigFixture default/twofactorauth/duo/integration_key abc123
     * @magentoConfigFixture default/twofactorauth/duo/api_hostname test.duosecurity.com
     * @magentoConfigFixture default/twofactorauth/duo/secret_key abc123
     * @magentoDataFixture Magento/User/_files/user_with_role.php
     */
    public function testVerifyNotConfiguredProvider()
    {
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('Provider is not configured.');
        $userId = $this->getUserId();
        $this->tfa->getProviderByCode(DuoSecurity::CODE)
            ->resetConfiguration($userId);

        $this->duo
            ->expects($this->never())
            ->method('authorizeUser');
        $this->model->createAdminAccessTokenWithCredentialsAndPasscode(
            'adminUser',
            Bootstrap::ADMIN_PASSWORD,
            '123456'
        );
    }

    /**
     * @magentoConfigFixture default/twofactorauth/general/force_providers authy
     * @magentoDataFixture Magento/User/_files/user_with_role.php
     */
    public function testVerifyUnavailableProvider()
    {
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('Provider is not allowed.');
        $this->duo
            ->expects($this->never())
            ->method('authorizeUser');
        $this->model->createAdminAccessTokenWithCredentialsAndPasscode(
            'adminUser',
            Bootstrap::ADMIN_PASSWORD,
            '123456'
        );
    }

    /**
     * @magentoConfigFixture default/twofactorauth/general/force_providers duo_security
     * @magentoConfigFixture default/twofactorauth/duo/client_id ABCDEFGHIJKLMNOPQRST
     * @magentoConfigFixture default/twofactorauth/duo/client_secret abcdefghijklmnopqrstuvwxyz0123456789abcd
     * @magentoConfigFixture default/twofactorauth/duo/integration_key abc123
     * @magentoConfigFixture default/twofactorauth/duo/api_hostname test.duosecurity.com
     * @magentoConfigFixture default/twofactorauth/duo/secret_key abc123
     * @magentoDataFixture Magento/User/_files/user_with_role.php
     */
    public function testVerifyValidRequest()
    {
        $userId = $this->getUserId();

        // Activate the Two-Factor Authentication provider for DuoSecurity
        $this->tfa->getProviderByCode(DuoSecurity::CODE)
            ->activate($userId);

        $username = 'adminUser';
        $password = Bootstrap::ADMIN_PASSWORD;
        $passcode = '123456'; // Example passcode for the test

        // Mock the DuoSecurity `authorizeUser` method to simulate valid Duo response
        $this->duo->method('authorizeUser')
            ->with(
                $this->equalTo($username),
                $this->equalTo("passcode"),
                $this->equalTo(['passcode' => $passcode])
            )
            ->willReturn(['status' => 'allow']);

        // Attempt to create the access token
        $token = $this->model->createAdminAccessTokenWithCredentialsAndPasscode(
            $username,
            $password,
            $passcode
        );

        // Assert that a token was generated successfully
        self::assertNotEmpty($token);
    }

    /**
     * @magentoConfigFixture default/twofactorauth/general/force_providers duo_security
     * @magentoConfigFixture default/twofactorauth/duo/client_id ABCDEFGHIJKLMNOPQRST
     * @magentoConfigFixture default/twofactorauth/duo/client_secret abcdefghijklmnopqrstuvwxyz0123456789abcd
     * @magentoConfigFixture default/twofactorauth/duo/integration_key abc123
     * @magentoConfigFixture default/twofactorauth/duo/api_hostname test.duosecurity.com
     * @magentoConfigFixture default/twofactorauth/duo/secret_key abc123
     * @magentoDataFixture Magento/User/_files/user_with_role.php
     */
    public function testVerifyInvalidRequest()
    {
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('Invalid response');

        $userId = $this->getUserId();

        // Activate the Two-Factor Authentication provider for DuoSecurity
        $this->tfa->getProviderByCode(DuoSecurity::CODE)
            ->activate($userId);

        $username = 'adminUser';
        $password = Bootstrap::ADMIN_PASSWORD;
        $passcode = '123456'; // Example passcode used for testing

        // Mock the DuoSecurity `authorizeUser` method to simulate an invalid response
        $this->duo->method('authorizeUser')
            ->with(
                $this->equalTo($username),
                $this->equalTo("passcode"),
                $this->equalTo(['passcode' => $passcode])
            )
            ->willReturn(['status' => 'deny', 'msg' => 'Authentication denied']); // Simulate invalid response

        // Attempt to create the access token, expecting an exception due to the invalid response
        $this->model->createAdminAccessTokenWithCredentialsAndPasscode(
            $username,
            $password,
            $passcode
        );
    }

    private function getUserId(): int
    {
        $user = $this->userFactory->create();
        $user->loadByUsername('adminUser');

        return (int)$user->getId();
    }
}
