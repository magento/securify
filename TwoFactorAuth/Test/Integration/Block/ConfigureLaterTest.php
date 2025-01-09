<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Test\Integration\Block;

use Magento\Authorization\Model\CompositeUserContext;
use Magento\Backend\Model\Auth;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\View\LayoutInterface;
use Magento\Security\Model\Plugin\Auth as AuthPlugin;
use Magento\TestFramework\Bootstrap as TestFrameworkBootstrap;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TwoFactorAuth\Api\TfaInterface;
use Magento\TwoFactorAuth\Block\ConfigureLater;
use Magento\TwoFactorAuth\Model\Provider\Engine\Authy;
use Magento\User\Model\Authorization\AdminSessionUserContext;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 */
class ConfigureLaterTest extends TestCase
{
    /**
     * @var ConfigureLater
     */
    private $block;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var TfaInterface
     */
    private $tfa;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $objectManager->configure([
            CompositeUserContext::class => [
                'arguments' => [
                    'userContexts' => [
                        'adminSessionUserContext' => [
                            'type' => ['instance' => AdminSessionUserContext::class],
                            'sortOrder' => 10
                        ]
                    ]
                ]
            ]
        ]);
        $auth = $objectManager->get(Auth::class);
        $auth->login(TestFrameworkBootstrap::ADMIN_NAME, TestFrameworkBootstrap::ADMIN_PASSWORD);
        $objectManager->get(AuthPlugin::class)
            ->afterLogin($auth);
        $this->session = $auth->getAuthStorage();
        $this->tfa = Bootstrap::getObjectManager()->get(TfaInterface::class);
        $this->block = $objectManager->get(LayoutInterface::class)
            ->createBlock(ConfigureLater::class);
        $this->block->setData('area', 'adminhtml');
        $this->block->setTemplate('Magento_TwoFactorAuth::tfa/configure_later.phtml');
    }

    public function testGetPostData(): void
    {
        $this->block->setData('provider', 'provider1');

        $data = json_decode($this->block->getPostData(), true);

        self::assertNotEmpty($data['action']);
        self::assertNotEmpty($data['data']);
        self::assertNotEmpty($data['data']['form_key']);
        self::assertSame('provider1', $data['data']['provider']);
    }

    /**
     * @magentoConfigFixture default/twofactorauth/general/force_providers authy,duo_security
     * @magentoConfigFixture default/twofactorauth/authy/api_key abc123
     * @magentoConfigFixture default/twofactorauth/duo/client_id ABCDEFGHIJKLMNOPQRST
     * @magentoConfigFixture default/twofactorauth/duo/client_secret abcdefghijklmnopqrstuvwxyz0123456789abcd
     * @magentoConfigFixture default/twofactorauth/duo/integration_key abc123
     * @magentoConfigFixture default/twofactorauth/duo/api_hostname test.duosecurity.com
     * @magentoConfigFixture default/twofactorauth/duo/secret_key abc123
     */
    public function testBlockRendersWithCurrentInactiveAndOneOtherActive(): void
    {
        $userId = (int)$this->session->getUser()->getId();
        $this->tfa->getProvider(Authy::CODE)->activate($userId);
        $this->block->setData('provider', 'duo_security');
        $html = $this->block->toHtml();

        self::assertStringContainsString('id="tfa', $html);
    }

    /**
     * @magentoConfigFixture default/twofactorauth/general/force_providers duo_security
     * @magentoConfigFixture default/twofactorauth/duo/client_id ABCDEFGHIJKLMNOPQRST
     * @magentoConfigFixture default/twofactorauth/duo/client_secret abcdefghijklmnopqrstuvwxyz0123456789abcd
     * @magentoConfigFixture default/twofactorauth/duo/integration_key abc123
     * @magentoConfigFixture default/twofactorauth/duo/api_hostname test.duosecurity.com
     * @magentoConfigFixture default/twofactorauth/duo/secret_key abc123
     */
    public function testBlockDoesntRenderWithCurrentInactiveAndNoOtherActive(): void
    {
        $this->block->setData('provider', 'duo_security');
        $html = $this->block->toHtml();

        self::assertSame('', $html);
    }
}
