<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaWebapiRest\Test\Unit\Plugin;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Webapi\Exception;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigInterface;
use Magento\ReCaptchaWebapiApi\Api\WebapiValidationConfigProviderInterface;
use Magento\ReCaptchaWebapiApi\Model\Data\Endpoint;
use Magento\ReCaptchaWebapiApi\Model\Data\EndpointFactory;
use Magento\ReCaptchaWebapiRest\Plugin\SoapValidationPlugin;
use Magento\Webapi\Controller\Soap\Request\Handler;
use Magento\Webapi\Model\Soap\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Webapi\Request;

class SoapValidationPluginTest extends TestCase
{
    /**
     * @var SoapValidationPlugin
     */
    private $model;

    /**
     * @var Config|MockObject
     */
    private $configMock;

    /**
     * @var UserContextInterface|MockObject
     */
    private $userContextMock;

    /**
     * @var WebapiValidationConfigProviderInterface|MockObject
     */
    private $configProviderMock;

    /**
     * @var EndpointFactory|MockObject
     */
    private $endpointFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configMock = $this->createMock(Config::class);
        $this->userContextMock = $this->getMockForAbstractClass(UserContextInterface::class);
        $this->configProviderMock = $this->getMockForAbstractClass(WebapiValidationConfigProviderInterface::class);
        $this->endpointFactory = $this->createMock(EndpointFactory::class);
        $this->model = new SoapValidationPlugin(
            $this->createMock(Request::class),
            $this->configMock,
            $this->userContextMock,
            $this->configProviderMock,
            $this->endpointFactory
        );
    }

    public static function getPluginCases(): array
    {
        return [
            'not-protected' => [false, UserContextInterface::USER_TYPE_GUEST, null, false],
            'protected-guest' => [true, UserContextInterface::USER_TYPE_GUEST, null, true],
            'protected-customer' => [true, UserContextInterface::USER_TYPE_CUSTOMER, 1, true],
            'protected-admin' => [true, UserContextInterface::USER_TYPE_ADMIN, 1, true],
            'protected-integration' => [true, UserContextInterface::USER_TYPE_INTEGRATION, 1, false],
        ];
    }

    /**
     * Verify that plugin protects SOAP with recaptcha.
     *
     * @param bool $configFound ReCaptcha config will be found.
     * @param int $userType Emulated user type.
     * @param int|null $userId Emulated user ID.
     * @param bool $expectException To expect an exception.
     * @return void
     * @throws Exception
     * @dataProvider getPluginCases
     */
    public function testPlugin(bool $configFound, int $userType, ?int $userId, bool $expectException):void
    {
        $operation = 'operation';
        $this->configMock->method('getServiceMethodInfo')
            ->willReturn(['class' => $class = 'class', 'method' => $method = 'method']);
        //Verifying that correct data will be extracted from service config.
        $this->endpointFactory->expects($this->atLeastOnce())
            ->method('create')
            ->with(['class' => $class, 'method' => $method, 'name' => $operation])
            ->willReturn($this->createMock(Endpoint::class));
        //Setting config found
        $this->configProviderMock->method('getConfigFor')
            ->willReturn(
                $configFound ? $this->getMockForAbstractClass(ValidationConfigInterface::class) : null
            );
        //Emulate user context
        $this->userContextMock->method('getUserType')->willReturn($userType);
        $this->userContextMock->method('getUserId')->willReturn($userId);

        if ($expectException) {
            //Should throw an exception
            $this->expectException(Exception::class);
        }

        $this->model->before__call($this->createMock(Handler::class), $operation, []);
    }
}
