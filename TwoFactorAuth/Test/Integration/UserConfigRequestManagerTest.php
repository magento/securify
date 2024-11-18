<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Test\Integration;

use Magento\Framework\Acl\Builder;
use Magento\Framework\Exception\AuthorizationException;
use Magento\TestFramework\Bootstrap;
use Magento\TestFramework\Helper\Bootstrap as BootstrapHelper;
use Magento\TestFramework\Mail\Template\TransportBuilderMock;
use Magento\User\Model\User;
use Magento\TwoFactorAuth\Api\TfaInterface;
use Magento\TwoFactorAuth\Api\UserConfigRequestManagerInterface;
use Magento\TwoFactorAuth\Api\UserConfigTokenManagerInterface;
use Magento\TwoFactorAuth\Model\Provider\Engine\Google;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * @magentoDbIsolation enabled
 */
class UserConfigRequestManagerTest extends TestCase
{
    /**
     * @var UserConfigRequestManagerInterface
     */
    private $manager;

    /**
     * @var User
     */
    private $user;

    /**
     * @var TfaInterface
     */
    private $tfa;

    /**
     * @var TransportBuilderMock
     */
    private $transportBuilderMock;

    /**
     * @var UserConfigTokenManagerInterface
     */
    private $tokenManager;
    /**
     * @var Builder
     */
    private $aclBuilder;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        /** @var User $user */
        $user = BootstrapHelper::getObjectManager()->create(User::class);
        $user->loadByUsername(Bootstrap::ADMIN_NAME);
        $this->user = $user;
        $this->tfa = BootstrapHelper::getObjectManager()->get(TfaInterface::class);
        $this->transportBuilderMock = BootstrapHelper::getObjectManager()->get(TransportBuilderMock::class);
        $this->tokenManager = BootstrapHelper::getObjectManager()->get(UserConfigTokenManagerInterface::class);
        $this->aclBuilder = BootstrapHelper::getObjectManager()->get(Builder::class);

        $this->manager = BootstrapHelper::getObjectManager()->get(UserConfigRequestManagerInterface::class);
    }

    /**
     * Check that config is required when no providers are enabled for the app.
     *
     * @return void
     */
    public function testIsRequiredWithoutAppProviders(): void
    {
        $this->assertTrue($this->manager->isConfigurationRequiredFor((int)$this->user->getId()));
    }

    /**
     * Check that config is required when personal provider config is empty.
     *
     * @return void
     * @magentoConfigFixture default/twofactorauth/general/force_providers google
     */
    public function testIsRequiredWithoutUserProviders(): void
    {
        $this->assertTrue($this->manager->isConfigurationRequiredFor((int)$this->user->getId()));
    }

    /**
     * Check that config is not required when both app and personal provider config is present.
     *
     * @return void
     * @magentoConfigFixture default/twofactorauth/general/force_providers google
     * @magentoDbIsolation enabled
     */
    public function testIsRequiredWithConfig(): void
    {
        $this->tfa->getProvider(Google::CODE)->activate((int)$this->user->getId());
        $this->assertFalse($this->manager->isConfigurationRequiredFor((int)$this->user->getId()));
    }

    /**
     * Check that app config request E-mail is NOT sent for a user that does not possess proper rights.
     *
     * @return void
     * @throws Throwable
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     */
    public function testFailAppConfigRequest(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->aclBuilder->getAcl()->deny(
            Bootstrap::ADMIN_ROLE_ID,
            'Magento_TwoFactorAuth::config'
        );
        $this->manager->sendConfigRequestTo($this->user);
    }

    /**
     * Check that app config request E-mail is sent for a user that posseses proper rights.
     *
     * @return void
     * @throws Throwable
     * @magentoAppArea adminhtml
     */
    public function testSendAppConfigRequest(): void
    {
        $this->manager->sendConfigRequestTo($this->user);

        $this->assertNotEmpty($message = $this->transportBuilderMock->getSentMessage());
        $messageHtml = quoted_printable_decode($message->getBody()->bodyToString());
        $this->assertStringContainsString(
            'You are required to configure website-wide and personal Two-Factor Authorization in order to login to',
            $messageHtml
        );
        $this->assertThat(
            $messageHtml,
            $this->matchesRegularExpression(
                '/\<a\s+href\=[\'\"].+\/tfat\/[A-Za-z0-9+\/=]+.+[\'\"]\>/s'
            )
        );
        preg_match('/\/tfat\/([^\/]+)/s', $messageHtml, $tokenMatches);
        $this->assertNotEmpty($tokenMatches[1]);
        $token = urldecode($tokenMatches[1]);
        $this->assertTrue($this->tokenManager->isValidFor((int)$this->user->getId(), $token));
    }

    /**
     * Check that personal 2FA config request E-mail is sent for users.
     *
     * @return void
     * @throws Throwable
     * @magentoAppArea adminhtml
     * @magentoConfigFixture default/twofactorauth/general/force_providers google
     */
    public function testSendUserConfigRequest(): void
    {
        $this->manager->sendConfigRequestTo($this->user);

        $this->assertNotEmpty($message = $this->transportBuilderMock->getSentMessage());
        $messageHtml = quoted_printable_decode($message->getBody()->bodyToString());
        $this->assertStringContainsString(
            'You are required to configure personal Two-Factor Authorization in order to login to',
            $messageHtml
        );
        $this->assertThat(
            $messageHtml,
            $this->matchesRegularExpression(
                '/\<a\s+href\=[\'\"].+\/tfat\/[A-Za-z0-9+\/=]+.+[\'\"]\>/s'
            )
        );
        preg_match('/\/tfat\/([^\/]+)/s', $messageHtml, $tokenMatches);
        $this->assertNotEmpty($tokenMatches[1]);
        $token = urldecode($tokenMatches[1]);
        $this->assertTrue($this->tokenManager->isValidFor((int)$this->user->getId(), $token));
    }
}
