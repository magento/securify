<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TwoFactorAuth\Test\Unit\Model\Provider\Engine;

use Magento\TwoFactorAuth\Model\Provider\Engine\Authy;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class AuthyTest extends TestCase
{
    /**
     * @var Authy
     */
    private $model;

    /**
     * @var MockObject|Authy\Service
     */
    private $serviceMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->serviceMock = $this->getMockBuilder(Authy\Service::class)->disableOriginalConstructor()->getMock();

        $this->model = $objectManager->getObject(Authy::class, ['service' => $this->serviceMock]);
    }

    /**
     * Enabled test dataset.
     *
     * @return array
     */
    public static function getIsEnabledTestDataSet(): array
    {
        return [
            'api key present' => [
                'api-key',
                true
            ],
            'api key not configured' => [
                null,
                false
            ]
        ];
    }

    /**
     * Check that the provider is available based on configuration.
     *
     * @param string|null $apiKey
     * @param bool $expected
     * @return void
     * @dataProvider getIsEnabledTestDataSet
     */
    public function testIsEnabled(?string $apiKey, bool $expected): void
    {
        $this->serviceMock->method('getApiKey')->willReturn((string)$apiKey);

        $this->assertEquals($expected, $this->model->isEnabled());
    }
}
