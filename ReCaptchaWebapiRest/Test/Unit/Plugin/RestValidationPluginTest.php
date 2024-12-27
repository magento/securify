<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaWebapiRest\Test\Unit\Plugin;

use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Webapi\Exception;
use Magento\Framework\Webapi\Rest\Request;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigInterface;
use Magento\ReCaptchaValidationApi\Api\ValidatorInterface;
use Magento\ReCaptchaWebapiApi\Api\WebapiValidationConfigProviderInterface;
use Magento\ReCaptchaWebapiApi\Model\Data\Endpoint;
use Magento\ReCaptchaWebapiApi\Model\Data\EndpointFactory;
use Magento\ReCaptchaWebapiRest\Plugin\RestValidationPlugin;
use Magento\Webapi\Controller\Rest\RequestValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Webapi\Controller\Rest\Router;

class RestValidationPluginTest extends TestCase
{
    /**
     * @var RestValidationPlugin
     */
    private $model;

    /**
     * @var ValidatorInterface|MockObject
     */
    private $validatorMock;

    /**
     * @var WebapiValidationConfigProviderInterface|MockObject
     */
    private $configProvider;

    /**
     * @var Router|MockObject
     */
    private $router;

    /**
     * @var EndpointFactory|MockObject
     */
    private $endpointFactory;

    /**
     * @var RequestValidator|MockObject
     */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validatorMock = $this->getMockForAbstractClass(ValidatorInterface::class);
        $this->configProvider = $this->getMockForAbstractClass(WebapiValidationConfigProviderInterface::class);
        $this->router = $this->createMock(Router::class);
        $this->endpointFactory = $this->createMock(EndpointFactory::class);
        $this->subject = $this->createMock(RequestValidator::class);
        $this->model = new RestValidationPlugin(
            $this->validatorMock,
            $this->configProvider,
            $this->createMock(Request::class),
            $this->router,
            $this->endpointFactory
        );
    }

    public static function getPluginCases(): array
    {
        return [
            'unprotected-endpoint' => [false, false, false],
            'protected-endpoint-no-token' => [true, false, true],
            'protected-endpoint-valid-token' => [true, true, false]
        ];
    }

    /**
     * Test the plugin in different scenarios.
     *
     * @param bool $configFound Will config be found.
     * @param bool $isValid Will validator validate the value.
     * @param bool $expectException Whether a webapi exception is expected.
     * @return void
     * @dataProvider getPluginCases
     */
    public function testPlugin(
        bool $configFound,
        bool $isValid,
        bool $expectException
    ): void {
        //Mocking route found
        $mockRoute = $this->createMock(Router\Route::class);
        $mockRoute->method('getServiceClass')->willReturn($class = 'class');
        $mockRoute->method('getServiceMethod')->willReturn($method = 'method');
        $mockRoute->method('getRoutePath')->willReturn($path = 'path');
        $this->router->method('match')->willReturn($mockRoute);
        //Verifying that a correct endpoint data extracted from the route.
        $this->endpointFactory->expects($this->atLeastOnce())
            ->method('create')
            ->with(['class' => $class, 'method' => $method, 'name' => $path])
            ->willReturn($this->createMock(Endpoint::class));
        //Emulating config
        $this->configProvider->method('getConfigFor')
            ->willReturn(
                $configFound ? $this->getMockForAbstractClass(ValidationConfigInterface::class) : null
            );
        //Validation
        $validatedMock = $this->createMock(ValidationResult::class);
        $validatedMock->method('isValid')->willReturn($isValid);
        $this->validatorMock->method('isValid')->willReturn($validatedMock);

        if ($expectException) {
            //ReCaptcha validation is supposed to fail
            $this->expectException(Exception::class);
        }

        $proceed = function () : void {
            //dummy function for callable argument
        };

        $this->model->aroundValidate($this->subject, $proceed);
    }
}
