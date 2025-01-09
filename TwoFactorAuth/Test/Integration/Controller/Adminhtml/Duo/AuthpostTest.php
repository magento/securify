<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Test\Integration\Controller\Adminhtml\Duo;

use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\TwoFactorAuth\TestFramework\TestCase\AbstractConfigureBackendController;

/**
 * Test for the DuoSecurity form processor.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class AuthpostTest extends AbstractConfigureBackendController
{
    /**
     * @var string
     */
    protected $uri = 'backend/tfa/duo/authpost';

    /**
     * @var string
     */
    protected $httpMethod = Request::METHOD_GET;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->expectedNoAccessResponseCode = 302;
    }

    /**
     * @inheritDoc
     * @magentoConfigFixture default/twofactorauth/general/force_providers duo_security
     * @magentoConfigFixture default/twofactorauth/duo/client_id ABCDEFGHIJKLMNOPQRST
     * @magentoConfigFixture default/twofactorauth/duo/client_secret abcdefghijklmnopqrstuvwxyz0123456789abcd
     * @magentoConfigFixture default/twofactorauth/duo/integration_key abc123
     * @magentoConfigFixture default/twofactorauth/duo/api_hostname test.duosecurity.com
     * @magentoConfigFixture default/twofactorauth/duo/secret_key abc123
     */
    public function testTokenAccess(): void
    {
        parent::testTokenAccess();
        //Redirect when isAllowed returns false
        $this->assertRedirect($this->stringContains('auth/login'));
    }

    /**
     * @inheritDoc
     * @magentoConfigFixture default/twofactorauth/general/force_providers duo_security
     * @magentoConfigFixture default/twofactorauth/duo/client_id ABCDEFGHIJKLMNOPQRST
     * @magentoConfigFixture default/twofactorauth/duo/client_secret abcdefghijklmnopqrstuvwxyz0123456789abcd
     * @magentoConfigFixture default/twofactorauth/duo/integration_key abc123
     * @magentoConfigFixture default/twofactorauth/duo/api_hostname test.duosecurity.com
     * @magentoConfigFixture default/twofactorauth/duo/secret_key abc123
     */
    public function testAclHasAccess()
    {
        $this->expectedNoAccessResponseCode = 200;
        parent::testAclHasAccess();
        //Redirect that Authpost supplies when signatures is not provided in a request.
        $this->assertRedirect($this->stringContains('duo/auth'));
    }

    /**
     * @inheritDoc
     * @magentoConfigFixture default/twofactorauth/general/force_providers duo_security
     * @magentoConfigFixture default/twofactorauth/duo/client_id ABCDEFGHIJKLMNOPQRST
     * @magentoConfigFixture default/twofactorauth/duo/client_secret abcdefghijklmnopqrstuvwxyz0123456789abcd
     * @magentoConfigFixture default/twofactorauth/duo/integration_key abc123
     * @magentoConfigFixture default/twofactorauth/duo/api_hostname test.duosecurity.com
     * @magentoConfigFixture default/twofactorauth/duo/secret_key abc123
     */
    public function testAclNoAccess()
    {
        parent::testAclNoAccess();
        //Redirect when isAllowed returns false
        $this->assertRedirect($this->stringContains('auth/login'));
    }
}
