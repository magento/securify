<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Test\Unit\Model\Provider\Engine;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\DataObject;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\UrlInterface;
use Magento\TwoFactorAuth\Model\Provider\Engine\DuoSecurity;
use Magento\User\Api\Data\UserInterface;
use Duo\DuoUniversal\Client;
use DuoAPI\Auth as DuoAuth;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DuoSecurityTest extends TestCase
{
    /** @var MockObject|ScopeConfigInterface */
    private $configMock;

    /** @var MockObject|EncryptorInterface */
    private $encryptorMock;

    /** @var MockObject|UrlInterface */
    private $urlMock;

    /** @var MockObject|FormKey */
    private $formKeyMock;

    /** @var MockObject|Client */
    private $clientMock;

    /**
     * @var DuoAuth|MockObject
     */
    private $duoAuthMock;

    /** @var DuoSecurity */
    private $model;

    protected function setUp(): void
    {
        $this->configMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->encryptorMock = $this->getMockBuilder(EncryptorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlMock = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->formKeyMock = $this->getMockBuilder(FormKey::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->clientMock = $this->createMock(Client::class);
        $this->duoAuthMock = $this->createMock(DuoAuth::class);

        $this->model = new DuoSecurity(
            $this->configMock,
            $this->encryptorMock,
            $this->urlMock,
            $this->formKeyMock,
            $this->clientMock,
            $this->duoAuthMock
        );
    }

    /**
     * Enabled test dataset.
     *
     * @return array
     */
    public static function getIsEnabledTestDataSet(): array
    {
        return [
            [
                'test.duosecurity.com',
                'ABCDEFGHIJKLMNOPQRST',
                'abcdefghijklmnopqrstuvwxyz0123456789abcd',
                '0:3:pE7QRAv43bvos7oeve+ULjQ1QCoZw0NMXXtHZtYdmlBR4Nb18IpauosSz1jKFYjo1nPCsOwHk1mOlFpGObrzpSb3zF0=',
                true
            ]
        ];
    }

    /**
     * Check that the provider is available based on configuration.
     *
     * @param string|null $apiHostname
     * @param string|null $clientId
     * @param string|null $clientSecret
     * @param bool $expected
     * @return void
     * @dataProvider getIsEnabledTestDataSet
     */
    public function testIsEnabled(
        ?string $apiHostname,
        ?string $clientId,
        ?string $encryptedClientSecret,
        ?string $decryptedClientSecret,
        bool $expected
    ): void {
        $this->configMock->method('getValue')->willReturnMap(
            [
                [DuoSecurity::XML_PATH_API_HOSTNAME, 'default', null, $apiHostname],
                [DuoSecurity::XML_PATH_CLIENT_ID, 'default', null, $clientId],
                [DuoSecurity::XML_PATH_CLIENT_SECRET, 'default', null, $encryptedClientSecret]
            ]
        );

        // Mocking EncryptorInterface
        $this->encryptorMock->expects($this->any())
            ->method('decrypt')
            ->with($encryptedClientSecret)
            ->willReturn($decryptedClientSecret);

        $this->assertEquals($expected, $this->model->isEnabled());
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testVerify()
    {
        $this->clientMock->expects($this->once())
            ->method('exchangeAuthorizationCodeFor2FAResult')
            ->with('duo-code', 'username')
            ->willReturn(['result' => 'valid-token']);

        $this->formKeyMock->method('getFormKey')
            ->willReturn('valid-form-key');

        $user = $this->createMock(UserInterface::class);
        $user->method('getUserName')->willReturn('username');

        $request = new DataObject([
            'state' => 'valid-form-keyDUOAUTH',
            'duo_code' => 'duo-code'
        ]);

        $result = $this->model->verify($user, $request);

        $this->assertTrue($result, 'Verification should return true for valid input.');
    }
}
