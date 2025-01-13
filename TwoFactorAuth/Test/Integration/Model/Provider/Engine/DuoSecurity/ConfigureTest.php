<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Test\Integration\Model\Provider\Engine\DuoSecurity;

use Magento\Framework\App\ObjectManager;
use Magento\TwoFactorAuth\Api\TfaInterface;
use Magento\TwoFactorAuth\Api\UserConfigTokenManagerInterface;
use Magento\TwoFactorAuth\Model\Provider\Engine\DuoSecurity;
use Magento\TwoFactorAuth\Model\Provider\Engine\DuoSecurity\Authenticate;
use Magento\TwoFactorAuth\Model\Provider\Engine\DuoSecurity\Configure;
use Magento\User\Model\UserFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 */
class ConfigureTest extends TestCase
{
    /**
     * @var Configure
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

    /**
     * @var Authenticate|MockObject
     */
    private $authenticate;

    protected function setUp(): void
    {
        $objectManager = ObjectManager::getInstance();
        $this->tokenManager = $objectManager->get(UserConfigTokenManagerInterface::class);
        $this->tfa = $objectManager->get(TfaInterface::class);
        $this->duo = $this->createMock(DuoSecurity::class);
        $this->userFactory = $objectManager->get(UserFactory::class);
        $this->authenticate = $this->createMock(Authenticate::class);
        $this->model = $objectManager->create(
            Configure::class,
            [
                'duo' => $this->duo,
                'authenticate' => $this->authenticate
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
    public function testGetDuoConfigurationDataInvalidTfat()
    {
        $this->expectException(\Magento\Framework\Exception\AuthorizationException::class);
        $this->expectExceptionMessage('Invalid two-factor authorization token');
        $this->duo
            ->expects($this->never())
            ->method('enrollNewUser');
        $this->model->getDuoConfigurationData(
            'abc'
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
    public function testGetDuoConfigurationDataAlreadyConfiguredProvider()
    {
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('Provider is already configured.');
        $userId = $this->getUserId();
        $this->tfa->getProviderByCode(DuoSecurity::CODE)
            ->activate($userId);

        $this->duo
            ->expects($this->never())
            ->method('enrollNewUser');
        $this->model->getDuoConfigurationData(
            $this->tokenManager->issueFor($userId)
        );
    }

    /**
     * @magentoConfigFixture default/twofactorauth/general/force_providers authy
     * @magentoDataFixture Magento/User/_files/user_with_role.php
     */
    public function testGetDuoConfigurationDataUnavailableProvider()
    {
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('Provider is not allowed.');
        $this->duo
            ->expects($this->never())
            ->method('enrollNewUser');
        $this->model->getDuoConfigurationData(
            $this->tokenManager->issueFor($this->getUserId())
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
    public function testDuoActivateInvalidTfat()
    {
        $this->expectException(\Magento\Framework\Exception\AuthorizationException::class);
        $this->expectExceptionMessage('Invalid two-factor authorization token');
        $this->duo
            ->expects($this->never())
            ->method('assertUserIsValid');
        $this->model->duoActivate(
            'abc',
            'something'
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
    public function testDuoActivateAlreadyConfiguredProvider()
    {
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('Provider is already configured.');
        $userId = $this->getUserId();
        $this->tfa->getProviderByCode(DuoSecurity::CODE)
            ->activate($userId);
        $this->duo
            ->expects($this->never())
            ->method('assertUserIsValid');
        $this->model->duoActivate(
            $this->tokenManager->issueFor($userId),
            'something'
        );
    }

    /**
     * @magentoConfigFixture default/twofactorauth/general/force_providers authy
     * @magentoDataFixture Magento/User/_files/user_with_role.php
     */
    public function testDuoActivateUnavailableProvider()
    {
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('Provider is not allowed.');
        $userId = $this->getUserId();
        $this->duo
            ->expects($this->never())
            ->method('assertUserIsValid');
        $this->model->duoActivate(
            $this->tokenManager->issueFor($userId),
            'something'
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
    public function testGetDuoConfigurationDataValidRequest()
    {
        $userId = $this->getUserId();
        $userName = 'adminUser';

        $this->duo
            ->expects($this->once())
            ->method('enrollNewUser')
            ->with($userName, 60)
            ->willReturn('enrollment_data');

        $result = $this->model->getDuoConfigurationData(
            $this->tokenManager->issueFor($userId)
        );

        self::assertSame('enrollment_data', $result);
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
    public function testDuoActivateValidRequest()
    {
        $userId = $this->getUserId();
        $userName = 'adminUser';

        $this->duo
            ->expects($this->once())
            ->method('assertUserIsValid')
            ->with($userName)
            ->willReturn('auth');

        $this->model->duoActivate(
            $this->tokenManager->issueFor($userId)
        );

        self::assertTrue($this->tfa->getProviderByCode(DuoSecurity::CODE)->isActive($userId));
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
    public function testDuoActivateInvalidDataThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Something');

        $userId = $this->getUserId();
        $tfat = $this->tokenManager->issueFor($userId);

        $userName = 'adminUser';

        $this->duo
            ->expects($this->once())
            ->method('assertUserIsValid')
            ->with($userName)
            ->willThrowException(new \InvalidArgumentException('Something'));

        // Call activate without a signature, as per your updated logic
        $result = $this->model->duoActivate($tfat);

        self::assertEmpty($result);
    }

    private function getUserId(): int
    {
        $user = $this->userFactory->create();
        $user->loadByUsername('adminUser');

        return (int)$user->getId();
    }
}
