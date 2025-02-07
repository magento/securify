<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TwoFactorAuth\Test\Api;

use Magento\Framework\HTTP\ClientInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Integration\Model\ResourceModel\Oauth\Token as TokenResource;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;
use Magento\TwoFactorAuth\Api\TfaInterface;
use Magento\TwoFactorAuth\Model\Provider\Engine\Google;
use Magento\User\Model\UserFactory;
use OTPHP\TOTP;

/**
 * Class checks google authentication behaviour
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GoogleAuthenticateTest extends WebapiAbstract
{
    public const SERVICE_VERSION = 'V1';
    public const SERVICE_NAME = 'twoFactorAuthGoogleAuthenticateV1';
    public const OPERATION = 'CreateAdminAccessToken';
    public const RESOURCE_PATH = '/V1/tfa/provider/google/authenticate';

    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var Google
     */
    private $google;

    /**
     * @var TfaInterface
     */
    private $tfa;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var SerializerInterface
     */
    private $json;

    /**
     * @var TokenResource
     */
    private $tokenResource;

    /**
     * @var TokenFactory
     */
    private $tokenFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->userFactory = $objectManager->get(UserFactory::class);
        $this->google = $objectManager->get(Google::class);
        $this->tfa = $objectManager->get(TfaInterface::class);
        $this->client = $objectManager->get(ClientInterface::class);
        $this->url = $objectManager->get(UrlInterface::class);
        $this->json = $objectManager->get(SerializerInterface::class);
        $this->tokenResource = $objectManager->get(TokenResource::class);
        $this->tokenFactory = $objectManager->get(TokenFactory::class);
    }

    /**
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role.php
     *
     * @return void
     */
    public function testInvalidCredentials(): void
    {
        $serviceInfo = $this->buildServiceInfo();

        try {
            $this->_webApiCall(
                $serviceInfo,
                [
                    'username' => 'customRoleUser',
                    'password' => 'bad',
                    'otp' => 'foo'
                ]
            );
            self::fail('Endpoint should have thrown an exception');
        } catch (\Throwable $exception) {
            $response = json_decode($exception->getMessage(), true);
            if (json_last_error()) {
                $message = $exception->getMessage();
            } else {
                $message = $response['message'];
            }
            self::assertSame(
                'The account sign-in was incorrect or your account is disabled temporarily. '
                . 'Please wait and try again later.',
                $message
            );
        }
    }

    /**
     * @magentoConfigFixture twofactorauth/general/force_providers duo_security
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role.php
     *
     * @return void
     */
    public function testUnavailableProvider(): void
    {
        $serviceInfo = $this->buildServiceInfo();

        try {
            $this->_webApiCall(
                $serviceInfo,
                [
                    'username' => 'customRoleUser',
                    'password' => \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD,
                    'otp' => 'foo'
                ]
            );
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
     *
     * @return void
     */
    public function testInvalidToken(): void
    {
        $userId = $this->getUserId();
        $serviceInfo = $this->buildServiceInfo();
        $this->tfa->getProviderByCode(Google::CODE)
            ->activate($userId);

        try {
            $this->_webApiCall(
                $serviceInfo,
                [
                    'username' => 'customRoleUser',
                    'password' => \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD,
                    'otp' => 'bad'
                ]
            );
            self::fail('Endpoint should have thrown an exception');
        } catch (\Throwable $exception) {
            $response = json_decode($exception->getMessage(), true);
            if (json_last_error()) {
                $message = $exception->getMessage();
            } else {
                $message = $response['message'];
            }
            self::assertSame('Invalid code.', $message);
        }
    }

    /**
     * @magentoConfigFixture twofactorauth/general/force_providers google
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role.php
     *
     * @return void
     */
    public function testNotConfiguredProvider(): void
    {
        $userId = $this->getUserId();
        $serviceInfo = $this->buildServiceInfo();
        $this->tfa->getProviderByCode(Google::CODE)
            ->resetConfiguration($userId);

        try {
            $this->_webApiCall(
                $serviceInfo,
                [
                    'username' => 'customRoleUser',
                    'password' => \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD,
                    'otp' => 'foo'
                ]
            );
            self::fail('Endpoint should have thrown an exception');
        } catch (\Throwable $exception) {
            $response = json_decode($exception->getMessage(), true);
            if (json_last_error()) {
                $message = $exception->getMessage();
            } else {
                $message = $response['message'];
            }
            self::assertSame('Provider is not configured.', $message);
        }
    }

    /**
     * @magentoConfigFixture twofactorauth/general/force_providers google
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role.php
     * @magentoConfigFixture twofactorauth/google/otp_window 29
     *
     * @return void
     */
    public function testValidToken(): void
    {
        $userId = $this->getUserId();
        $otp = $this->getUserOtp();
        $serviceInfo = $this->buildServiceInfo();
        $this->tfa->getProviderByCode(Google::CODE)
            ->activate($userId);

        $response = $this->_webApiCall(
            $serviceInfo,
            [
                'username' => 'customRoleUser',
                'password' => \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD,
                'otp' => $otp
            ]
        );
        self::assertNotEmpty($response);
        self::assertMatchesRegularExpression('/^[a-z0-9]{32}$/', $response);
    }

    /**
     * @magentoConfigFixture default/oauth/access_token_lifetime/admin 1
     * @magentoConfigFixture twofactorauth/general/force_providers google
     *
     * @magentoApiDataFixture Magento/Webapi/_files/webapi_user.php
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testAdminTokenLifetime(): void
    {
        $this->_markTestAsRestOnly();
        $this->tfa->getProviderByCode(Google::CODE)->activate($this->getUserId('webapi_user'));
        $otp = $this->getUserOtp('webapi_user');
        $serviceInfo = $this->buildServiceInfo();
        $requestData = [
            'otp' => $otp,
            'username' => 'webapi_user',
            'password' => \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD,
        ];
        $accessToken = $this->_webApiCall($serviceInfo, $requestData);
        $result = $this->doCustomerRequest($accessToken, 1);
        $this->assertContains('customer@example.com', $this->json->unserialize($result));
        $this->updateTokenCreatedTime($accessToken);
        $result = $this->doCustomerRequest($accessToken, 1);
        $this->assertContains(
            'The consumer isn\'t authorized to access %resources.',
            $this->json->unserialize($result)
        );
    }

    /**
     * @return array
     */
    private function buildServiceInfo(): array
    {
        return [
            'rest' => [
                // Ensure the default auth is invalidated
                'token' => 'invalid',
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST
            ],
            'soap' => [
                // Ensure the default auth is invalidated
                'token' => 'invalid',
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . self::OPERATION
            ]
        ];
    }

    /**
     * Get user id
     *
     * @param string $userName
     * @return int
     */
    private function getUserId($userName = 'customRoleUser'): int
    {
        $user = $this->userFactory->create();
        $user->loadByUsername($userName);

        return (int)$user->getId();
    }

    /**
     * Get user otp
     *
     * @param string $userName
     * @return string
     */
    private function getUserOtp($userName = 'customRoleUser'): string
    {
        $user = $this->userFactory->create();
        $user->loadByUsername($userName);
        $totp = TOTP::create($this->google->getSecretCode($user));

        return $totp->now();
    }

    /**
     * Perform request to customers endpoint
     *
     * @param string $accessToken
     * @return string
     */
    private function doCustomerRequest(string $accessToken, $customerId): string
    {
        $this->client->addHeader('Authorization', 'Bearer ' . $accessToken);
        $this->client->get($this->url->getBaseUrl() . 'rest/V1/customers/' . $customerId);

        return $this->client->getBody();
    }

    /**
     * Update token created time
     *
     * @param string $accessToken
     * @return void
     */
    private function updateTokenCreatedTime(string $accessToken): void
    {
        $token = $this->tokenFactory->create();
        $token->loadByToken($accessToken);
        $createdAt = (new \DateTime('-1 day'))->format('Y-m-d H:i:s');
        $token->setCreatedAt($createdAt);
        $this->tokenResource->save($token);
    }
}
