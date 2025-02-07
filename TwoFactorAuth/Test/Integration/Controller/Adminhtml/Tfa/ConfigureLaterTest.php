<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Test\Integration\Controller\Adminhtml\Tfa;

use Magento\TwoFactorAuth\Api\TfaInterface;
use Magento\TwoFactorAuth\Api\TfaSessionInterface;
use Magento\TwoFactorAuth\Model\Provider\Engine\DuoSecurity;
use Magento\TwoFactorAuth\TestFramework\TestCase\AbstractBackendController;
use Magento\TwoFactorAuth\Api\UserConfigTokenManagerInterface;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class ConfigureLaterTest extends AbstractBackendController
{
    /**
     * @var string
     */
    protected $uri = 'backend/tfa/tfa/configurelater';

    /**
     * @var string
     */
    protected $resource = 'Magento_TwoFactorAuth::tfa';

    /**
     * @var TfaInterface
     */
    private $tfa;

    /**
     * @var UserConfigTokenManagerInterface
     */
    private $tokenManager;

    /**
     * @var TfaSessionInterface
     */
    private $tfaSession;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tfa = $this->_objectManager->get(TfaInterface::class);
        $this->tfaSession = $this->_objectManager->get(TfaSessionInterface::class);
        $this->tokenManager = $this->_objectManager->get(UserConfigTokenManagerInterface::class);
    }

    /**
     * @magentoConfigFixture default/twofactorauth/general/force_providers duo_security
     * @magentoConfigFixture default/twofactorauth/duo/client_id ABCDEFGHIJKLMNOPQRST
     * @magentoConfigFixture default/twofactorauth/duo/client_secret abcdefghijklmnopqrstuvwxyz0123456789abcd
     * @magentoConfigFixture default/twofactorauth/duo/integration_key abc123
     * @magentoConfigFixture default/twofactorauth/duo/api_hostname test.duosecurity.com
     * @magentoConfigFixture default/twofactorauth/duo/secret_key abc123
     */
    public function testNotAllowedWhenProviderAlreadyActivated(): void
    {
        $userId = (int)$this->_session->getUser()->getId();
        $this->tfa->getProvider(DuoSecurity::CODE)->activate($userId);
        $this->getRequest()
            ->setMethod('POST')
            ->setQueryValue('tfat', $this->tokenManager->issueFor($userId))
            ->setPostValue('provider', 'duo_security');
        $this->dispatch($this->uri);
        $this->assertRedirect($this->stringContains('auth/login'));
    }

    /**
     * @magentoConfigFixture default/twofactorauth/general/force_providers duo_security
     * @magentoConfigFixture default/twofactorauth/duo/client_id ABCDEFGHIJKLMNOPQRST
     * @magentoConfigFixture default/twofactorauth/duo/client_secret abcdefghijklmnopqrstuvwxyz0123456789abcd
     * @magentoConfigFixture default/twofactorauth/duo/integration_key abc123
     * @magentoConfigFixture default/twofactorauth/duo/api_hostname test.duosecurity.com
     * @magentoConfigFixture default/twofactorauth/duo/secret_key abc123
     */
    public function testNotAllowedWhenProviderNotActivatedButIsTheOnlyProvider(): void
    {
        $userId = (int)$this->_session->getUser()->getId();
        $this->getRequest()
            ->setMethod('POST')
            ->setQueryValue('tfat', $this->tokenManager->issueFor($userId))
            ->setPostValue('provider', 'google');
        $this->dispatch($this->uri);
        $this->assertRedirect($this->stringContains('auth/login'));
    }

    /**
     * @magentoConfigFixture default/twofactorauth/general/force_providers google,duo_security,authy
     * @magentoConfigFixture default/twofactorauth/authy/api_key abc123
     * @magentoConfigFixture default/twofactorauth/duo/client_id ABCDEFGHIJKLMNOPQRST
     * @magentoConfigFixture default/twofactorauth/duo/client_secret abcdefghijklmnopqrstuvwxyz0123456789abcd
     * @magentoConfigFixture default/twofactorauth/duo/integration_key abc123
     * @magentoConfigFixture default/twofactorauth/duo/api_hostname test.duosecurity.com
     * @magentoConfigFixture default/twofactorauth/duo/secret_key abc123
     */
    public function testSkippingAProvider(): void
    {
        $userId = (int)$this->_session->getUser()->getId();
        $this->getRequest()
            ->setMethod('POST')
            ->setQueryValue('tfat', $this->tokenManager->issueFor($userId))
            ->setPostValue('provider', 'authy');
        $this->dispatch($this->uri);
        self::assertTrue($this->tfaSession->getSkippedProviderConfig()['authy']);
        $this->assertRedirect($this->stringContains('tfa/index'));
    }

    /**
     * @magentoConfigFixture default/twofactorauth/general/force_providers duo_security,authy
     * @magentoConfigFixture default/twofactorauth/authy/api_key abc123
     * @magentoConfigFixture default/twofactorauth/duo/client_id ABCDEFGHIJKLMNOPQRST
     * @magentoConfigFixture default/twofactorauth/duo/client_secret abcdefghijklmnopqrstuvwxyz0123456789abcd
     * @magentoConfigFixture default/twofactorauth/duo/integration_key abc123
     * @magentoConfigFixture default/twofactorauth/duo/api_hostname test.duosecurity.com
     * @magentoConfigFixture default/twofactorauth/duo/secret_key abc123
     */
    public function testSkippingAllProvidersWhenThereAreNoneConfigured(): void
    {
        $userId = (int)$this->_session->getUser()->getId();
        $this->tfaSession->setSkippedProviderConfig(
            [
                'duo_security' => true,
                'already disabled provider' => true,
                'authy' => true,
            ]
        );
        $this->getRequest()
            ->setMethod('POST')
            ->setQueryValue('tfat', $this->tokenManager->issueFor($userId))
            ->setPostValue('provider', 'authy');
        $this->dispatch($this->uri);
        $this->assertSessionMessages(
            $this->equalTo(['At least one two-factor authentication provider must be configured.'])
        );
        $this->assertRedirect($this->stringContains('tfa/index'));
    }

    /**
     * @inheritDoc
     */
    public function testAclHasAccess()
    {
        $this->markTestSkipped('Tested with the tests above.');
    }

    /**
     * @inheritDoc
     */
    public function testAclNoAccess()
    {
        $this->markTestSkipped('Tested with the tests above.');
    }
}
