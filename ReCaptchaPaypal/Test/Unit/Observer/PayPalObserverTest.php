<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaPaypal\Test\Unit\Observer;

use Magento\Framework\App\Action\AbstractAction;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Validation\ValidationResult;
use Magento\ReCaptchaPaypal\Model\ReCaptchaSession;
use Magento\ReCaptchaPaypal\Observer\PayPalObserver;
use Magento\ReCaptchaUi\Model\CaptchaResponseResolverInterface;
use Magento\ReCaptchaUi\Model\ErrorMessageConfigInterface;
use Magento\ReCaptchaUi\Model\IsCaptchaEnabledInterface;
use Magento\ReCaptchaUi\Model\ValidationConfigResolverInterface;
use Magento\ReCaptchaValidationApi\Api\ValidatorInterface;
use Magento\ReCaptchaValidationApi\Model\ValidationErrorMessagesProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PayPalObserverTest extends TestCase
{
    /**
     * @var ValidatorInterface|MockObject
     */
    private $captchaValidator;

    /**
     * @var IsCaptchaEnabledInterface|MockObject
     */
    private $isCaptchaEnabled;

    /**
     * @var ReCaptchaSession|MockObject
     */
    private $reCaptchaSession;

    /**
     * @var PayPalObserver
     */
    private $model;

    /**
     * @var Observer
     */
    private $observer;

    /**
     * @var ValidationResult|MockObject
     */
    private $validationResult;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $captchaResponseResolver = $this->getMockForAbstractClass(CaptchaResponseResolverInterface::class);
        $validationConfigResolver = $this->getMockForAbstractClass(ValidationConfigResolverInterface::class);
        $this->captchaValidator = $this->getMockForAbstractClass(ValidatorInterface::class);
        $actionFlag = $this->createMock(ActionFlag::class);
        $serializer = $this->getMockForAbstractClass(SerializerInterface::class);
        $this->isCaptchaEnabled = $this->getMockForAbstractClass(IsCaptchaEnabledInterface::class);
        $logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $errorMessageConfig = $this->getMockForAbstractClass(ErrorMessageConfigInterface::class);
        $validationErrorMessagesProvider = $this->createMock(ValidationErrorMessagesProvider::class);
        $this->reCaptchaSession = $this->createMock(ReCaptchaSession::class);
        $this->model = new PayPalObserver(
            $captchaResponseResolver,
            $validationConfigResolver,
            $this->captchaValidator,
            $actionFlag,
            $serializer,
            $this->isCaptchaEnabled,
            $logger,
            $errorMessageConfig,
            $validationErrorMessagesProvider,
            $this->reCaptchaSession
        );
        $controller = $this->getMockBuilder(AbstractAction::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRequest', 'getResponse'])
            ->getMockForAbstractClass();
        $request = $this->getMockForAbstractClass(RequestInterface::class);
        $response = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['representJson'])
            ->getMockForAbstractClass();
        $controller->method('getRequest')->willReturn($request);
        $controller->method('getResponse')->willReturn($response);
        $this->observer = new Observer(['controller_action' => $controller]);
        $this->validationResult = $this->createMock(ValidationResult::class);
    }

    /**
     * @param array $mocks
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $mocks): void
    {
        $this->configureMock($mocks);
        $this->model->execute($this->observer);
    }

    public static function executeDataProvider(): array
    {
        return [
            [
                [
                    'isCaptchaEnabled' => [
                        [
                            'method' => 'isCaptchaEnabledFor',
                            'willReturnMap' => [
                                ['paypal_payflowpro', false],
                                ['place_order', false],
                            ]
                        ]
                    ],
                    'reCaptchaSession' => [
                        [
                            'method' => 'save',
                            'expects' => self::never(),
                        ]
                    ]
                ]
            ],
            [
                [
                    'isCaptchaEnabled' => [
                        [
                            'method' => 'isCaptchaEnabledFor',
                            'willReturnMap' => [
                                ['paypal_payflowpro', true],
                                ['place_order', false],
                            ]
                        ]
                    ],
                    'reCaptchaSession' => [
                        [
                            'method' => 'save',
                            'expects' => self::never(),
                        ]
                    ],
                    'captchaValidator' => [
                        [
                            'method' => 'isValid',
                            'expects' => self::once(),
                            'willReturnProperty' => 'validationResult'
                        ]
                    ],
                    'validationResult' => [
                        [
                            'method' => 'isValid',
                            'expects' => self::once(),
                            'willReturn' => true,
                        ]
                    ]
                ]
            ],
            [
                [
                    'isCaptchaEnabled' => [
                        [
                            'method' => 'isCaptchaEnabledFor',
                            'willReturnMap' => [
                                ['paypal_payflowpro', true],
                                ['place_order', true],
                            ]
                        ]
                    ],
                    'reCaptchaSession' => [
                        [
                            'method' => 'save',
                            'expects' => self::once(),
                        ]
                    ],
                    'captchaValidator' => [
                        [
                            'method' => 'isValid',
                            'expects' => self::once(),
                            'willReturnProperty' => 'validationResult'
                        ]
                    ],
                    'validationResult' => [
                        [
                            'method' => 'isValid',
                            'expects' => self::once(),
                            'willReturn' => true,
                        ]
                    ]
                ]
            ]
        ];
    }

    private function configureMock(array $mocks): void
    {
        foreach ($mocks as $prop => $propMocks) {
            foreach ($propMocks as $mock) {
                $builder = $this->$prop->expects($mock['expects'] ?? $this->any());
                unset($mock['expects']);
                foreach ($mock as $method => $args) {
                    if ($method === 'willReturnProperty') {
                        $method = 'willReturn';
                        $args = $this->$args;
                    }
                    $builder->$method(...[$args]);
                }
            }
        }
    }
}
