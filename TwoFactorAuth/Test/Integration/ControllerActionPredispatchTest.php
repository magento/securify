<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Test\Integration;

use Magento\Framework\Stdlib\Cookie\CookieReaderInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TwoFactorAuth\TestFramework\TestCase\AbstractBackendController;
use Magento\TwoFactorAuth\Api\TfaInterface;
use Magento\TwoFactorAuth\Api\TfaSessionInterface;
use Magento\TwoFactorAuth\Api\UserConfigTokenManagerInterface;
use Magento\TwoFactorAuth\Model\Provider\Engine\Google;

/**
 * Test for 2FA enforcement.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class ControllerActionPredispatchTest extends AbstractBackendController
{
    /**
     * @var CookieReaderInterface
     */
    private $cookieReader;

    /**
     * @var UserConfigTokenManagerInterface
     */
    private $tokenManager;

    /**
     * @var TfaInterface
     */
    private $tfa;

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

        $this->cookieReader = Bootstrap::getObjectManager()->get(CookieReaderInterface::class);
        $this->tokenManager = Bootstrap::getObjectManager()->get(UserConfigTokenManagerInterface::class);
        $this->tfaSession = Bootstrap::getObjectManager()->get(TfaSessionInterface::class);
        $this->tfa = Bootstrap::getObjectManager()->get(TfaInterface::class);
    }

    /**
     * Verify that users with configured 2FA and 2FA completed can proceed to desired page.
     *
     * @return void
     * @magentoConfigFixture default/twofactorauth/general/force_providers google
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testTfaCompleted(): void
    {
        //Configuring 2FA for the user and completing 2FA.
        $this->tfa->getProvider(Google::CODE)->activate((int)$this->_session->getUser()->getId());
        $this->tfaSession->grantAccess();
        //Accessing a page in adminhtml area
        $this->dispatch('backend/admin/user/');
        //Authenticated user with 2FA configured and completed is taken to the Users page as requested.
        self::assertMatchesRegularExpression(
            '/' .$this->_session->getUser()->getUserName() .'/i',
            $this->getResponse()->getBody()
        );
    }

    /**
     * Verify that unauthenticated user is redirected to login page.
     *
     * @return void
     * @magentoAppIsolation enabled
     */
    public function testUnauthenticated(): void
    {
        $this->logout();
        $this->dispatch('backend/admin/index/index');
        //Login controller redirects to full start-up URL
        $this->assertRedirect($this->stringContains('index'));
        $properUrl = $this->getResponse()->getHeader('Location')->uri()->getPath();

        //Login page must be rendered without redirects
        $this->getRequest()->setDispatched(false);
        $this->getRequest()->setUri($properUrl);
        $this->dispatch($properUrl);
        $this->assertStringContainsString('Welcome, please sign in', $this->getResponse()->getBody());
    }

    /**
     * Verify that users would be redirected to "2FA Config Request" page when 2FA is not configured for the app.
     *
     * @magentoConfigFixture default/twofactorauth/general/force_providers google,duo_security
     * @magentoConfigFixture default/twofactorauth/duo/client_id ABCDEFGHIJKLMNOPQRST
     * @magentoConfigFixture default/twofactorauth/duo/client_secret abcdefghijklmnopqrstuvwxyz0123456789abcd
     * @magentoConfigFixture default/twofactorauth/duo/integration_key abc123
     * @magentoConfigFixture default/twofactorauth/duo/api_hostname test.duosecurity.com
     * @magentoConfigFixture default/twofactorauth/duo/secret_key abc123
     * @return void
     */
    public function testConfigRequested(): void
    {
        $this->tfa->getProvider(Google::CODE)->resetConfiguration((int)$this->_session->getUser()->getId());

        //Accessing a page in adminhtml area
        $this->dispatch('backend/admin/user/');
        //Authenticated user gets a redirect to 2FA configuration page since none is configured.
        $this->assertRedirect($this->stringContains('requestconfig'));
    }

    /**
     * Verify that users would be redirected to "2FA Config Request" page when 2FA is not configured for the user.
     *
     * @return void
     * @magentoConfigFixture default/twofactorauth/general/force_providers google
     */
    public function testUserConfigRequested(): void
    {
        //Accessing a page in adminhtml area
        $this->dispatch('backend/admin/user/');
        //Authenticated user gets a redirect to 2FA configuration page since none is configured for the user.
        $this->assertRedirect($this->stringContains('requestconfig'));
    }

    /**
     * Verify that users returning with a token from the E-mail get a new cookie with it.
     *
     * @magentoConfigFixture default/twofactorauth/general/force_providers google,duo_security
     * @magentoConfigFixture default/twofactorauth/duo/client_id ABCDEFGHIJKLMNOPQRST
     * @magentoConfigFixture default/twofactorauth/duo/client_secret abcdefghijklmnopqrstuvwxyz0123456789abcd
     * @magentoConfigFixture default/twofactorauth/duo/integration_key abc123
     * @magentoConfigFixture default/twofactorauth/duo/api_hostname test.duosecurity.com
     * @magentoConfigFixture default/twofactorauth/duo/secret_key abc123
     * @return void
     */
    public function testCookieSet(): void
    {
        $this->tfa->getProvider(Google::CODE)->resetConfiguration((int)$this->_session->getUser()->getId());

        //Accessing a page in adminhtml area with a valid token.
        $this->getRequest()
            ->setQueryValue('tfat', $token = $this->tokenManager->issueFor((int)$this->_session->getUser()->getId()));
        $this->dispatch('backend/admin/user/');
        //Authenticated user gets a redirect to 2FA configuration page since none is configured.
        $this->assertRedirect($this->stringContains('requestconfig'));
        $this->assertNotEmpty($cookie = $this->cookieReader->getCookie('tfa-ct'));
        $this->assertEquals($token, $cookie);
    }

    /**
     * Verify that users returning with a valid token from the E-mail and 2FA configured get redirected to 2FA form.
     *
     * @return void
     * @magentoConfigFixture default/twofactorauth/general/force_providers google
     * @magentoDbIsolation enabled
     */
    public function testTfaChallenged(): void
    {
        $this->tfa->getProvider(Google::CODE)->activate((int)$this->_session->getUser()->getId());
        //Accessing a page in adminhtml area
        $this->dispatch('backend/admin/user/');
        //Authenticated user with 2FA configured is redirected to 2FA code form.
        $this->assertRedirect($this->stringContains('tfa/tfa/index'));
    }
}
