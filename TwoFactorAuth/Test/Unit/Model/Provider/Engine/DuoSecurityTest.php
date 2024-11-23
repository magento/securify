<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Test\Unit\Model\Provider\Engine;

use Magento\User\Api\Data\UserInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\TwoFactorAuth\Model\Provider\Engine\DuoSecurity;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

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
                'value',
                true
            ],
            [
                null,
                null,
                null,
                null,
                false
            ],
            [
                'value',
                null,
                null,
                null,
                false
            ],
            [
                null,
                'value',
                null,
                null,
                false
            ],
            [
                null,
                null,
                'value',
                null,
                false
            ],
            [
                null,
                null,
                null,
                'value',
                false
            ]
        ];
    }

    /**
     * Check that the provider is available based on configuration.
     *
     * @param string|null $apiHostname
     * @param string|null $appKey
     * @param string|null $secretKey
     * @param string|null $integrationKey
     * @param bool $expected
     * @return void
     * @dataProvider getIsEnabledTestDataSet
     */
    public function testIsEnabled(
        ?string $apiHostname,
        ?string $appKey,
        ?string $secretKey,
        ?string $integrationKey,
        bool $expected
    ): void {
        $this->configMock->method('getValue')->willReturnMap(
            [
                [DuoSecurity::XML_PATH_API_HOSTNAME, 'default', null, $apiHostname],
                [DuoSecurity::XML_PATH_APPLICATION_KEY, 'default', null, $appKey],
                [DuoSecurity::XML_PATH_SECRET_KEY, 'default', null, $secretKey],
                [DuoSecurity::XML_PATH_INTEGRATION_KEY, 'default', null, $integrationKey]
            ]
        );

        $this->assertEquals($expected, $this->model->isEnabled());
    }

    public function testGetRequestSignature() : void
    {
        $this->user->expects($this->any())
            ->method('getUserName')
            ->willReturn('admin');
        $this->configMock->expects($this->any())
            ->method('getValue')
            ->willReturn('SECRET');

        $this->assertStringContainsString($this->model::AUTH_PREFIX, $this->model->getRequestSignature($this->user));
        $this->assertStringNotContainsString($this->model::DUO_PREFIX, $this->model->getRequestSignature($this->user));

        $this->assertStringContainsString(
            $this->model::DUO_PREFIX,
            $this->modelWithForcedDuoAuth->getRequestSignature($this->user)
        );
        $this->assertStringNotContainsString(
            $this->model::AUTH_PREFIX,
            $this->modelWithForcedDuoAuth->getRequestSignature($this->user)
        );
    }
}
