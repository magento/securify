<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TwoFactorAuth\Test\Api;

use Magento\Framework\Webapi\Rest\Request;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;
use Magento\TwoFactorAuth\Api\TfaInterface;
use Magento\TwoFactorAuth\Api\UserConfigTokenManagerInterface;
use Magento\TwoFactorAuth\Model\Provider\Engine\Google;
use Magento\User\Model\UserFactory;
use OTPHP\TOTP;

class GoogleActivateTest extends WebapiAbstract
{
    public const SERVICE_VERSION = 'V1';
    public const SERVICE_NAME = 'twoFactorAuthGoogleConfigureV1';
    public const OPERATION = 'Activate';
    public const RESOURCE_PATH = '/V1/tfa/provider/google/activate';

    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var UserConfigTokenManagerInterface
     */
    private $tokenManager;

    /**
     * @var TfaInterface
     */
    private $tfa;

    /**
     * @var Google
     */
    private $google;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->userFactory = $objectManager->get(UserFactory::class);
        $this->tokenManager = $objectManager->get(UserConfigTokenManagerInterface::class);
        $this->tfa = $objectManager->get(TfaInterface::class);
        $this->userFactory = $objectManager->get(UserFactory::class);
        $this->google = $objectManager->get(Google::class);
    }

    /**
     * @magentoConfigFixture twofactorauth/general/force_providers google
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role.php
     */
    public function testInvalidTfat()
    {
        $serviceInfo = $this->buildServiceInfo();

        try {
            $this->_webApiCall($serviceInfo, ['tfaToken' => 'abc', 'otp' => 'invalid']);
            self::fail('Endpoint should have thrown an exception');
        } catch (\Throwable $exception) {
            $response = json_decode($exception->getMessage(), true);
            if (json_last_error()) {
                $message = $exception->getMessage();
            } else {
                $message = $response['message'];
            }
            self::assertSame('Invalid two-factor authorization token', $message);
        }
    }

    /**
     * @magentoConfigFixture twofactorauth/general/force_providers duo_security
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role.php
     */
    public function testUnavailableProvider()
    {
        $userId = $this->getUserId();
        $token = $this->tokenManager->issueFor($userId);
        $serviceInfo = $this->buildServiceInfo();

        try {
            $this->_webApiCall($serviceInfo, ['tfaToken' => $token, 'otp' => 'invalid']);
            self::fail('Endpoint should have thrown an exception');
        } catch (\Throwable $exception) {
            $response = json_decode($exception->getMessage(), true);
            if (json_last_error()) {
                $message = $exception->getMessage();
            } else {
                $message = $response['message'];
            }
            self::assertSame('Provider is not allowed.', $message);
        }
    }

    /**
     * @magentoConfigFixture twofactorauth/general/force_providers google
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role.php
     */
    public function testAlreadyActivatedProvider()
    {
        $userId = $this->getUserId();
        $token = $this->tokenManager->issueFor($userId);
        $serviceInfo = $this->buildServiceInfo();
        $otp = $this->getUserOtp();
        $this->tfa->getProviderByCode(Google::CODE)
            ->activate($userId);

        try {
            $this->_webApiCall($serviceInfo, ['tfaToken' => $token, 'otp' => $otp]);
            self::fail('Endpoint should have thrown an exception');
        } catch (\Throwable $exception) {
            $response = json_decode($exception->getMessage(), true);
            if (json_last_error()) {
                $message = $exception->getMessage();
            } else {
                $message = $response['message'];
            }
            self::assertSame('Provider is already configured.', $message);
        }
    }

    /**
     * @magentoConfigFixture twofactorauth/general/force_providers google
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role.php
     * @magentoConfigFixture twofactorauth/google/otp_window 29
     */
    public function testActivate()
    {
        $userId = $this->getUserId();
        $token = $this->tokenManager->issueFor($userId);
        $otp = $this->getUserOtp();
        $serviceInfo = $this->buildServiceInfo();

        $response = $this->_webApiCall(
            $serviceInfo,
            [
                'tfaToken' => $token,
                'otp' => $otp
            ]
        );
        self::assertEmpty($response);
    }

    private function getUserOtp(): string
    {
        $user = $this->userFactory->create();
        $user->loadByUsername('customRoleUser');
        $totp = TOTP::create($this->google->getSecretCode($user));

        return $totp->now();
    }

    /**
     * @return array
     */
    private function buildServiceInfo(): array
    {
        return [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . self::OPERATION
            ]
        ];
    }

    private function getUserId(): int
    {
        $user = $this->userFactory->create();
        $user->loadByUsername('customRoleUser');

        return (int)$user->getId();
    }
}
