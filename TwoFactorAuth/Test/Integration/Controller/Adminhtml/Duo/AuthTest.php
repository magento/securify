<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Test\Integration\Controller\Adminhtml\Duo;

use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\TwoFactorAuth\TestFramework\TestCase\AbstractConfigureBackendController;

/**
 * Test for the DuoSecurity form.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class AuthTest extends AbstractConfigureBackendController
{
    /**
     * @inheritDoc
     */
    protected $uri = 'backend/tfa/duo/auth';

    /**
     * @inheritDoc
     */
    protected $httpMethod = Request::METHOD_GET;

    /**
     * @inheritDoc
     * @magentoConfigFixture default/twofactorauth/general/force_providers duo_security
     * @magentoConfigFixture default/twofactorauth/duo/integration_key duo_security
     * @magentoConfigFixture default/twofactorauth/duo/secret_key duo_security
     * @magentoConfigFixture default/twofactorauth/duo/api_hostname duo_security
     * phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod
     */
    public function testTokenAccess(): void
    {
        parent::testTokenAccess();
    }

    /**
     * @inheritDoc
     * @magentoConfigFixture default/twofactorauth/general/force_providers duo_security
     * @magentoConfigFixture default/twofactorauth/duo/integration_key duo_security
     * @magentoConfigFixture default/twofactorauth/duo/secret_key duo_security
     * @magentoConfigFixture default/twofactorauth/duo/api_hostname duo_security
     * phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod
     */
    public function testAclHasAccess()
    {
        parent::testAclHasAccess();
    }

    /**
     * @inheritDoc
     * @magentoConfigFixture default/twofactorauth/general/force_providers duo_security
     * @magentoConfigFixture default/twofactorauth/duo/integration_key duo_security
     * @magentoConfigFixture default/twofactorauth/duo/secret_key duo_security
     * @magentoConfigFixture default/twofactorauth/duo/api_hostname duo_security
     * phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod
     */
    public function testAclNoAccess()
    {
        parent::testAclNoAccess();
    }
}
