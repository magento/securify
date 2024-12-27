<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaPaypal\Test\Unit\Plugin;

use Magento\Framework\Webapi\Rest\Request;
use Magento\Paypal\Model\Config;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\ReCaptchaCheckout\Model\WebapiConfigProvider;
use Magento\ReCaptchaPaypal\Model\ReCaptchaSession;
use Magento\ReCaptchaPaypal\Plugin\ReplayPayflowReCaptchaForPlaceOrder;
use Magento\ReCaptchaUi\Model\IsCaptchaEnabledInterface;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigInterface;
use Magento\ReCaptchaWebapiApi\Api\Data\EndpointInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReplayPayflowReCaptchaForPlaceOrderTest extends TestCase
{
    /**
     * @var IsCaptchaEnabledInterface|MockObject
     */
    private $isCaptchaEnabled;

    /**
     * @var Request|MockObject
     */
    private $request;

    /**
     * @var ReCaptchaSession|MockObject
     */
    private $reCaptchaSession;

    /**
     * @var QuoteIdMaskFactory|MockObject
     */
    private $quoteIdMaskFactory;

    /**
     * @var QuoteIdMask|MockObject
     */
    private $quoteIdMask;

    /**
     * @var ReplayPayflowReCaptchaForPlaceOrder
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->isCaptchaEnabled = $this->getMockForAbstractClass(IsCaptchaEnabledInterface::class);
        $this->request = $this->createMock(Request::class);
        $this->reCaptchaSession = $this->createMock(ReCaptchaSession::class);
        $this->quoteIdMaskFactory = $this->createMock(QuoteIdMaskFactory::class);
        $this->quoteIdMask = $this->getMockBuilder(QuoteIdMask::class)
            ->onlyMethods(['load'])
            ->addMethods(['getQuoteId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = new ReplayPayflowReCaptchaForPlaceOrder(
            $this->isCaptchaEnabled,
            $this->request,
            $this->reCaptchaSession,
            $this->quoteIdMaskFactory
        );
    }

    /**
     * @param array $mocks
     * @param bool $isResultNull
     * @param bool $isReturnNull
     * @dataProvider afterGetConfigForDataProvider
     */
    public function testAfterGetConfigFor(array $mocks, bool $isResultNull, bool $isReturnNull): void
    {
        $this->configureMock($mocks);
        $subject = $this->createMock(WebapiConfigProvider::class);
        $result = $this->getMockForAbstractClass(ValidationConfigInterface::class);
        $endpoint = $this->getMockForAbstractClass(EndpointInterface::class);
        $this->assertSame(
            $isReturnNull ? null : $result,
            $this->model->afterGetConfigFor($subject, $isResultNull ? null : $result, $endpoint)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function afterGetConfigForDataProvider(): array
    {
        return [
            [
                [
                    'reCaptchaSession' => [
                        ['method' => 'isValid', 'expects' => self::never()]
                    ]
                ],
                true,
                true
            ],
            [
                [
                    'isCaptchaEnabled' => [
                        ['method' => 'isCaptchaEnabledFor', 'with' => 'paypal_payflowpro', 'willReturn' => false]
                    ],
                    'reCaptchaSession' => [
                        ['method' => 'isValid', 'expects' => self::never(),]
                    ]
                ],
                false,
                false
            ],
            [
                [
                    'isCaptchaEnabled' => [
                        ['method' => 'isCaptchaEnabledFor', 'with' => 'paypal_payflowpro', 'willReturn' => true]
                    ],
                    'request' => [
                        ['method' => 'getBodyParams', 'expects' => self::once(), 'willReturn' => []]
                    ],
                    'reCaptchaSession' => [
                        ['method' => 'isValid', 'expects' => self::never(),]
                    ]
                ],
                false,
                false
            ],
            [
                [
                    'isCaptchaEnabled' => [
                        ['method' => 'isCaptchaEnabledFor', 'with' => 'paypal_payflowpro', 'willReturn' => true]
                    ],
                    'request' => [
                        [
                            'method' => 'getBodyParams',
                            'expects' => self::once(),
                            'willReturn' => ['cartId' => 1, 'paymentMethod' => ['method' => 'checkmo']]
                        ]
                    ],
                    'reCaptchaSession' => [
                        ['method' => 'isValid', 'expects' => self::never(), 'willReturn' => false]
                    ]
                ],
                false,
                false
            ],
            [
                [
                    'isCaptchaEnabled' => [
                        ['method' => 'isCaptchaEnabledFor', 'with' => 'paypal_payflowpro', 'willReturn' => true]
                    ],
                    'request' => [
                        [
                            'method' => 'getBodyParams',
                            'expects' => self::once(),
                            'willReturn' => ['cartId' => 1, 'paymentMethod' => ['method' => Config::METHOD_PAYFLOWPRO]]
                        ]
                    ],
                    'reCaptchaSession' => [
                        ['method' => 'isValid', 'expects' => self::once(), 'with' => 1, 'willReturn' => false]
                    ]
                ],
                false,
                false
            ],
            [
                [
                    'isCaptchaEnabled' => [
                        ['method' => 'isCaptchaEnabledFor', 'with' => 'paypal_payflowpro', 'willReturn' => true]
                    ],
                    'request' => [
                        [
                            'method' => 'getBodyParams',
                            'expects' => self::once(),
                            'willReturn' => ['cartId' => 1, 'paymentMethod' => ['method' => Config::METHOD_PAYFLOWPRO]]
                        ]
                    ],
                    'reCaptchaSession' => [
                        ['method' => 'isValid', 'expects' => self::once(), 'with' => 1, 'willReturn' => true]
                    ]
                ],
                false,
                true
            ],
            [
                [
                    'isCaptchaEnabled' => [
                        ['method' => 'isCaptchaEnabledFor', 'with' => 'paypal_payflowpro', 'willReturn' => true]
                    ],
                    'request' => [
                        [
                            'method' => 'getBodyParams',
                            'expects' => self::once(),
                            'willReturn' => [
                                'cart_id' => 1,
                                'payment_method' => ['method' => Config::METHOD_PAYFLOWPRO]
                            ]
                        ]
                    ],
                    'reCaptchaSession' => [
                        ['method' => 'isValid', 'expects' => self::once(), 'with' => 1, 'willReturn' => true]
                    ]
                ],
                false,
                true
            ],
            [
                [
                    'isCaptchaEnabled' => [
                        ['method' => 'isCaptchaEnabledFor', 'with' => 'paypal_payflowpro', 'willReturn' => true]
                    ],
                    'request' => [
                        [
                            'method' => 'getBodyParams',
                            'expects' => self::once(),
                            'willReturn' => [
                                'cartId' => '17uc43rge98nc92',
                                'paymentMethod' => ['method' => Config::METHOD_PAYFLOWPRO]
                            ]
                        ]
                    ],
                    'quoteIdMaskFactory' => [
                        [
                            'method' => 'create',
                            'expects' => self::once(),
                            'willReturnProperty' => 'quoteIdMask'
                        ]
                    ],
                    'quoteIdMask' => [
                        [
                            'method' => 'load',
                            'expects' => self::once(),
                            'willReturnSelf' => null
                        ],
                        [
                            'method' => 'getQuoteId',
                            'expects' => self::once(),
                            'willReturn' => 2
                        ]
                    ],
                    'reCaptchaSession' => [
                        ['method' => 'isValid', 'expects' => self::once(), 'with' => 2, 'willReturn' => true]
                    ]
                ],
                false,
                true
            ],
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
