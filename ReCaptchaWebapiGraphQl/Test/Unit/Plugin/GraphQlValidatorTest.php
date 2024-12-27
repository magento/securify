<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaWebapiGraphQl\Test\Unit\Plugin;

use GraphQL\Language\AST\OperationDefinitionNode;
use Magento\Framework\App\Request\Http;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\Validation\ValidationResult;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigInterface;
use Magento\ReCaptchaValidationApi\Api\ValidatorInterface;
use Magento\ReCaptchaWebapiApi\Api\WebapiValidationConfigProviderInterface;
use Magento\ReCaptchaWebapiApi\Model\Data\Endpoint;
use Magento\ReCaptchaWebapiApi\Model\Data\EndpointFactory;
use Magento\ReCaptchaWebapiGraphQl\Plugin\GraphQlValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GraphQlValidatorTest extends TestCase
{
    /**
     * @var GraphQlValidator
     */
    private $model;

    /**
     * @var WebapiValidationConfigProviderInterface|MockObject
     */
    private $configProviderMock;

    /**
     * @var ValidatorInterface|MockObject
     */
    private $validatorMock;

    /**
     * @var EndpointFactory|MockObject
     */
    private $endpointFactoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configProviderMock = $this->getMockForAbstractClass(WebapiValidationConfigProviderInterface::class);
        $this->validatorMock = $this->getMockForAbstractClass(ValidatorInterface::class);
        $this->endpointFactoryMock = $this->createMock(EndpointFactory::class);
        $this->model = new GraphQlValidator(
            $this->createMock(Http::class),
            $this->configProviderMock,
            $this->validatorMock,
            $this->endpointFactoryMock
        );
    }

    public static function getPluginCases(): array
    {
        return [
            'not-mutation' => [false, true, false, false],
            'not-protected' => [true, false, false, false],
            'invalid' => [true, true, false, true],
            'valid' => [true, true, true, false]
        ];
    }

    /**
     * Verify that plugin protects GraphQL endpoints.
     *
     * @param bool $isMutation Emulate request type.
     * @param bool $configFound Emulate existing endpoint config.
     * @param bool $isValid Emulate isValid() result.
     * @param bool $expectException Whether to expect an exception.
     * @throws GraphQlInputException
     * @return void
     * @dataProvider getPluginCases
     */
    public function testPlugin(bool $isMutation, bool $configFound, bool $isValid, bool $expectException): void
    {
        //Emulating query type
        $infoMock = $this->createMock(ResolveInfo::class);
        $infoMock->operation = $this->createMock(OperationDefinitionNode::class);
        $infoMock->operation->operation = $isMutation ?  'mutation' : 'query';
        //Emulating endpoint info
        $fieldMock = $this->createMock(Field::class);
        $fieldMock->method('getResolver')->willReturn('\\' . ($class = 'Class'));
        $fieldMock->method('getName')->willReturn($name = 'name');
        $this->endpointFactoryMock->method('create')
            ->with(['class' => $class, 'method' => 'resolve', 'name' => $name])
            ->willReturn($this->createMock(Endpoint::class));
        //Emulating config found
        $this->configProviderMock->method('getConfigFor')
            ->willReturn(
                $configFound ? $this->getMockForAbstractClass(ValidationConfigInterface::class) : null
            );
        //Emulating validation result
        $this->validatorMock->method('isValid')
            ->willReturn(new ValidationResult($isValid ? [] : ['error' => 'error']));

        if ($expectException) {
            //ReCaptcha verification must fail
            $this->expectException(GraphQlInputException::class);
        }

        $this->model->beforeResolve(
            $this->getMockForAbstractClass(ResolverInterface::class),
            $fieldMock,
            null,
            $infoMock
        );
    }
}
