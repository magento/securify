<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Test\Integration\Controller\Adminhtml\Tfa;

use Magento\TestFramework\Helper\Bootstrap;
use Magento\TwoFactorAuth\Api\TfaSessionInterface;
use Magento\TwoFactorAuth\Model\Provider\Engine\Authy;
use Magento\TwoFactorAuth\Model\Provider\Engine\DuoSecurity;
use Magento\TwoFactorAuth\TestFramework\TestCase\AbstractBackendController;
use Magento\TwoFactorAuth\Api\TfaInterface;
use Magento\TwoFactorAuth\Model\Provider\Engine\Google;
use Magento\TwoFactorAuth\Api\UserConfigManagerInterface;

/**
 * Testing the controller for the page that redirects user to proper pages depending on 2FA state.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class IndexTest extends AbstractBackendController
{
    /**
     * @var string
     */
    protected $uri = 'backend/tfa/tfa/index';

    /**
     * @var TfaInterface
     */
    private $tfa;

    /**
     * @var UserConfigManagerInterface
     */
    private $userManager;

    /**
     * @var TfaSessionInterface
     */
    private $tfaSession;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $objectManager = Bootstrap::getObjectManager();

        $this->tfaSession = $objectManager->get(TfaSessionInterface::class);
        $this->userManager = $objectManager->get(UserConfigManagerInterface::class);
        $this->tfa = $objectManager->get(TfaInterface::class);
    }

    /**
     * Verify that user is taken to Config Request page when 2FA is not configured.
     *
     * @return void
     */
    public function testNoneConfigured(): void
    {
        $this->dispatch($this->uri);
        $this->assertRedirect($this->stringContains('requestconfig'));
    }

    /**
     * Verify that user is taken to provider's configuration page when only personal 2FA is not configured.
     *
     * @return void
     * @magentoConfigFixture default/twofactorauth/general/force_providers google
     */
    public function testUserNotConfigured(): void
    {
        $this->dispatch($this->uri);
        $this->assertRedirect($this->stringContains('google/configure'));
    }

    /**
     * Verify that user is taken to configured provider's challenge page.
     *
     * @return void
     * @magentoConfigFixture default/twofactorauth/general/force_providers google
     * @magentoDbIsolation enabled
     */
    public function testConfigured(): void
    {
        //Activating a provider for the user.
        $this->tfa->getProvider(Google::CODE)->activate((int)$this->_session->getUser()->getId());

        $this->dispatch($this->uri);
        //Taken to the provider's challenge page.
        $this->assertRedirect($this->stringContains('google/auth'));
    }

    /**
     * @magentoConfigFixture default/twofactorauth/general/force_providers google,authy
     * @magentoConfigFixture default/twofactorauth/authy/api_key abc123
     * @magentoDbIsolation enabled
     */
    public function testNotConfiguredWithSkipped(): void
    {
        $this->tfaSession->setSkippedProviderConfig(['google' => true]);

        $this->dispatch($this->uri);
        $this->assertRedirect($this->stringContains('authy/configure'));
    }

    /**
     * @magentoConfigFixture default/twofactorauth/general/force_providers google,authy,duo_security
     * @magentoConfigFixture default/twofactorauth/authy/api_key abc123
     * @magentoConfigFixture default/twofactorauth/duo/client_id ABCDEFGHIJKLMNOPQRST
     * @magentoConfigFixture default/twofactorauth/duo/client_secret abcdefghijklmnopqrstuvwxyz0123456789abcd
     * @magentoConfigFixture default/twofactorauth/duo/integration_key abc123
     * @magentoConfigFixture default/twofactorauth/duo/api_hostname test.duosecurity.com
     * @magentoConfigFixture default/twofactorauth/duo/secret_key abc123
     * @magentoDbIsolation enabled
     */
    public function testDefaultProviderIsUsedForAuth(): void
    {
        $userId = (int)$this->_session->getUser()->getId();
        $this->tfa->getProvider(Google::CODE)->activate($userId);
        $this->tfa->getProvider(Authy::CODE)->activate($userId);
        $this->tfa->getProvider(DuoSecurity::CODE)->activate($userId);
        $this->userManager->setDefaultProvider($userId, Authy::CODE);
        $this->dispatch($this->uri);
        $this->assertRedirect($this->stringContains('authy/auth'));
    }

    /**
     * @magentoConfigFixture default/twofactorauth/general/force_providers google,authy,duo_security
     * @magentoConfigFixture default/twofactorauth/authy/api_key abc123
     * @magentoConfigFixture default/twofactorauth/duo/client_id ABCDEFGHIJKLMNOPQRST
     * @magentoConfigFixture default/twofactorauth/duo/client_secret abcdefghijklmnopqrstuvwxyz0123456789abcd
     * @magentoConfigFixture default/twofactorauth/duo/integration_key abc123
     * @magentoConfigFixture default/twofactorauth/duo/api_hostname test.duosecurity.com
     * @magentoConfigFixture default/twofactorauth/duo/secret_key abc123
     * @magentoDbIsolation enabled
     */
    public function testFirstProviderIsUsedForAuthWithoutADefault(): void
    {
        $userId = (int)$this->_session->getUser()->getId();
        $this->userManager->setDefaultProvider($userId, '');
        $this->tfa->getProvider(Google::CODE)->activate($userId);
        $this->tfa->getProvider(Authy::CODE)->activate($userId);
        $this->tfa->getProvider(DuoSecurity::CODE)->activate($userId);
        $this->dispatch($this->uri);
        $this->assertRedirect($this->stringContains('google/auth'));
    }

    /**
     * @magentoConfigFixture default/twofactorauth/general/force_providers google,authy,duo_security
     * @magentoConfigFixture default/twofactorauth/authy/api_key abc123
     * @magentoConfigFixture default/twofactorauth/duo/client_id ABCDEFGHIJKLMNOPQRST
     * @magentoConfigFixture default/twofactorauth/duo/client_secret abcdefghijklmnopqrstuvwxyz0123456789abcd
     * @magentoConfigFixture default/twofactorauth/duo/integration_key abc123
     * @magentoConfigFixture default/twofactorauth/duo/api_hostname test.duosecurity.com
     * @magentoConfigFixture default/twofactorauth/duo/secret_key abc123
     * @magentoDbIsolation enabled
     */
    public function testFirstProviderIsUsedForAuthWhenDefaultIsInvalid(): void
    {
        $userId = (int)$this->_session->getUser()->getId();
        $this->userManager->setDefaultProvider($userId, 'foobar');
        $this->tfa->getProvider(Google::CODE)->activate($userId);
        $this->tfa->getProvider(Authy::CODE)->activate($userId);
        $this->tfa->getProvider(DuoSecurity::CODE)->activate($userId);
        $this->dispatch($this->uri);
        $this->assertRedirect($this->stringContains('google/auth'));
    }
}
