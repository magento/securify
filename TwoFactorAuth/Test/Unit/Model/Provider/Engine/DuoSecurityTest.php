<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Test\Unit\Model\Provider\Engine;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\UrlInterface;
use Magento\TwoFactorAuth\Model\Provider\Engine\DuoSecurity;
use Magento\User\Api\Data\UserInterface;
use Duo\DuoUniversal\Client;
use DuoAPI\Auth as DuoAuth;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DuoSecurityTest extends TestCase
{
    /** @var MockObject|ScopeConfigInterface */
    private $configMock;

    /** @var MockObject|UrlInterface */
    private $urlMock;

    /** @var MockObject|Client */
    private $clientMock;

    /**
     * @var DuoAuth|MockObject
     */
    private $duoAuthMock;

    /** @var DuoSecurity */
    private $model;

    protected function setUp(): void
    {
        $this->configMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlMock = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->clientMock = $this->createMock(Client::class);
        $this->duoAuthMock = $this->createMock(DuoAuth::class);

        $this->model = new DuoSecurity(
            $this->configMock,
            $this->urlMock,
            $this->clientMock,
            $this->duoAuthMock
        );
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
                'test.duosecurity.com',
                'ABCDEFGHIJKLMNOPQRST',
                'abcdefghijklmnopqrstuvwxyz0123456789abcd',
                'google,duo_security,authy',
                true
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
        string $forceProviders,
        bool $expected
    ): void {
        $this->configMock->method('getValue')->willReturnMap(
            [
                [DuoSecurity::XML_PATH_API_HOSTNAME, 'default', null, $apiHostname],
                [DuoSecurity::XML_PATH_CLIENT_ID, 'default', null, $clientId],
                [DuoSecurity::XML_PATH_CLIENT_SECRET, 'default', null, $clientSecret],
                ['twofactorauth/general/force_providers', 'default', null, $forceProviders]
            ]
        );

        $this->assertEquals($expected, $this->model->isEnabled());
    }
}
