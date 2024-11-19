<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Test\Unit\Model\Provider\Engine;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\User\Api\Data\UserInterface;
use Magento\TwoFactorAuth\Model\Provider\Engine\DuoSecurity;
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
}
