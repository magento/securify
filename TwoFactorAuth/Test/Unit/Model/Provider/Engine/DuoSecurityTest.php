<?php
declare(strict_types=1);

namespace Magento\TwoFactorAuth\Test\Unit\Model\Provider\Engine;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\User\Api\Data\UserInterface;
use Magento\TwoFactorAuth\Model\Provider\Engine\DuoSecurity;
use Duo\DuoUniversal\Client;
use DuoAPI\Auth as DuoAuth;
use PHPUnit\Framework\TestCase;

class DuoSecurityTest extends TestCase
{
    /**
     * @var DuoSecurity
     */
    private $model;

    /**
     * @var DuoSecurity
     */
    private $modelWithForcedDuoAuth;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $configMock;

    /**
     * @var UserInterface|MockObject
     */
    private $user;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->configMock = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();
        $this->user = $this->getMockBuilder(UserInterface::class)->disableOriginalConstructor()->getMock();

        $this->model = $objectManager->getObject(DuoSecurity::class, ['scopeConfig' => $this->configMock]);
        $this->modelWithForcedDuoAuth = new DuoSecurity($this->configMock, $this->model::DUO_PREFIX);
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
                'value',
                'value',
                'value',
                true
            ],
            [
                null,
                'value',
                null,
                false
            ],
            [
                'value',
                'value',
                null,
                false
            ],
            [
                null,
                'value',
                null,
                false
            ],
            [
                null,
                'value',
                'value',
                false
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
        ?string $clientSecret,
        bool $expected
    ): void {
        $this->configMock->method('getValue')->willReturnMap(
            [
                [DuoSecurity::XML_PATH_API_HOSTNAME, 'default', null, $apiHostname],
                [DuoSecurity::XML_PATH_CLIENT_ID, 'default', null, $clientId],
                [DuoSecurity::XML_PATH_CLIENT_SECRET, 'default', null, $clientSecret]
            ]
        );

        $this->assertEquals($expected, $this->model->isEnabled());
    }

    public function testGetApiHostname()
    {
        $this->scopeConfig->method('getValue')->willReturn('api.hostname');
        $this->assertEquals('api.hostname', $this->duoSecurity->getApiHostname());
    }

    public function testVerify()
    {
        $user = $this->createMock(UserInterface::class);
        $request = $this->createMock(DataObject::class);

        $user->method('getUserName')->willReturn('username');
        $request->method('getData')->willReturnMap([
            ['state', 'form_keyDUOAUTH'],
            ['duo_code', 'duo_code']
        ]);
        $this->formKey->method('getFormKey')->willReturn('form_key');
        $this->client->method('exchangeAuthorizationCodeFor2FAResult')->willReturn('token');

        $this->session->expects($this->once())->method('setData')->with('duo_token', 'token');

        $this->assertTrue($this->duoSecurity->verify($user, $request));
    }

    public function testInitiateAuth()
    {
        $this->client->method('createAuthUrl')->willReturn('auth_url');
        $this->assertEquals('auth_url', $this->duoSecurity->initiateAuth('username', 'state'));
    }

    public function testHealthCheck()
    {
        $this->client->expects($this->once())->method('healthCheck');
        $this->duoSecurity->healthCheck();
    }

    public function testEnrollNewUser()
    {
        $this->duoAuth->method('enroll')->willReturn('enroll_response');
        $this->assertEquals('enroll_response', $this->duoSecurity->enrollNewUser('username', 3600));
    }

    public function testAssertUserIsValid()
    {
        $this->duoAuth->method('preauth')->willReturn(['response' => ['response' => ['result' => 'valid']]]);
        $this->assertEquals('valid', $this->duoSecurity->assertUserIsValid('userIdentifier'));
    }

    public function testAuthorizeUser()
    {
        $this->duoAuth->method('auth')->willReturn(['response' => ['response' => ['status' => 'allow', 'status_msg' => 'success']]]);
        $this->assertEquals(['status' => 'allow', 'msg' => 'success'], $this->duoSecurity->authorizeUser('userIdentifier', 'factor', []));
    }
}
