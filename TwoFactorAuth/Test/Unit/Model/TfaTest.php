<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\TwoFactorAuth\Api\ProviderInterface;
use Magento\TwoFactorAuth\Api\ProviderPoolInterface;
use Magento\TwoFactorAuth\Model\Tfa;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TfaTest extends TestCase
{
    /**
     * @var Tfa
     */
    private $model;

    /**
     * @var ProviderPoolInterface|MockObject
     */
    private $pool;

    /**
     * @var ProviderInterface[]|MockObject[]
     */
    private $providersMockList;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $configMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->pool = $this->getMockForAbstractClass(ProviderPoolInterface::class);
        $this->pool->method('getProviders')
            ->willReturnCallback(
                function (): array {
                    return $this->providersMockList;
                }
            );
        $this->pool->method('getProviderByCode')
            ->willReturnCallback(
                function (string $code): ProviderInterface {
                    if (array_key_exists($code, $this->providersMockList)) {
                        return $this->providersMockList[$code];
                    }
                    throw new NoSuchEntityException();
                }
            );
        $this->configMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);

        $this->model = $objectManager->getObject(
            Tfa::class,
            ['providerPool' => $this->pool, 'scopeConfig' => $this->configMock]
        );
    }

    /**
     * Define existing providers.
     *
     * @param array $providersData Keys - codes, values - {enabled: bool}.
     * @return void
     */
    private function defineProvidersList(array $providersData): void
    {
        $providers = [];
        foreach ($providersData as $code => $providerData) {
            $provider = $this->getMockForAbstractClass(ProviderInterface::class);
            $provider->method('getCode')->willReturn($code);
            $provider->method('isEnabled')->willReturn($providerData['enabled']);
            $providers[$code] = $provider;
        }

        $this->providersMockList = $providers;
    }

    /**
     * Extract codes from a providers list.
     *
     * @param ProviderInterface[] $providers
     * @return string[]
     */
    private function extractProviderCodes(array $providers): array
    {
        return array_map(
            function (ProviderInterface $provider): string {
                return $provider->getCode();
            },
            $providers
        );
    }

    /**
     * Check that enabled providers list updates when the pool or providers update.
     *
     * @return void
     */
    public function testAllEnabledProvidersUpdates(): void
    {
        $providersData = ['provider1' => ['enabled' => true], 'provider2' => ['enabled' => true]];
        $this->defineProvidersList($providersData);
        $this->assertEquals(
            ['provider1', 'provider2'],
            $this->extractProviderCodes($this->model->getAllEnabledProviders())
        );

        $providersData = ['provider1' => ['enabled' => true], 'provider2' => ['enabled' => false]];
        $this->defineProvidersList($providersData);
        $this->assertEquals(
            ['provider1'],
            $this->extractProviderCodes($this->model->getAllEnabledProviders())
        );

        $providersData = [
            'provider1' => ['enabled' => true],
            'provider2' => ['enabled' => true],
            'provider3' => ['enabled' => true]
        ];
        $this->defineProvidersList($providersData);
        $this->assertEquals(
            ['provider1', 'provider2', 'provider3'],
            $this->extractProviderCodes($this->model->getAllEnabledProviders())
        );
    }

    /**
     * Data set for the forcedProviders test.
     *
     * @return array
     */
    public static function getForcedProvidersDataSet(): array
    {
        return [
            'not defined' => [
                null,
                ['provider1' => ['enabled' => true]],
                []
            ],
            'not defined string' => [
                '',
                ['provider1' => ['enabled' => true]],
                []
            ],
            'not defined array' => [
                [],
                ['provider1' => ['enabled' => true]],
                []
            ],
            'valid array' => [
                ['provider1'],
                ['provider1' => ['enabled' => true], 'provider2' => ['enabled' => true]],
                ['provider1']
            ],
            'valid string' => [
                'provider1, provider2',
                ['provider1' => ['enabled' => true], 'provider2' => ['enabled' => true]],
                ['provider1', 'provider2']
            ],
            'invalid code' => [
                'nonExistingProvider',
                ['provider1' => ['enabled' => true], 'provider2' => ['enabled' => true]],
                []
            ],
            'disabledProvider' => [
                'provider1',
                ['provider1' => ['enabled' => false], 'provider2' => ['enabled' => true]],
                []
            ]
        ];
    }

    /**
     * Test getForcedProviders method.
     *
     * @param string|null|array $configValue
     * @param array $providersList
     * @param array $expected
     * @return void
     * @dataProvider getForcedProvidersDataSet
     */
    public function testGetForcedProviders($configValue, array $providersList, $expected): void
    {
        $this->configMock->method('getValue')->willReturn($configValue);
        $this->defineProvidersList($providersList);

        $result = $this->model->getForcedProviders();

        $this->assertEquals($expected, $this->extractProviderCodes($result));
    }

    /**
     * Check that user providers = forced providers.
     *
     * @return void
     */
    public function testGetUserProviders(): void
    {
        $this->configMock->method('getValue')->willReturnReference($configValue);
        $this->defineProvidersList(['provider1' => ['enabled' => true]]);

        $configValue = 'provider1';
        $this->assertEquals(['provider1'], $this->extractProviderCodes($this->model->getUserProviders(1)));

        $configValue = '';
        $this->assertEmpty($this->model->getUserProviders(1));
    }

    /**
     * Verify that 2FA is always enabled
     *
     * @return void
     */
    public function testIsEnabled(): void
    {
        $this->assertTrue($this->model->isEnabled());
    }
}
