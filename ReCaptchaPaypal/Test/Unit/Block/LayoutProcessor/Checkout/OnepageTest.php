<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaPaypal\Test\Unit\Block\LayoutProcessor\Checkout;

use Magento\Framework\DataObject;
use Magento\Paypal\Model\Config;
use Magento\ReCaptchaPaypal\Block\LayoutProcessor\Checkout\Onepage;
use Magento\ReCaptchaUi\Model\IsCaptchaEnabledInterface;
use Magento\ReCaptchaUi\Model\UiConfigResolverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OnepageTest extends TestCase
{
    /**
     * @var UiConfigResolverInterface|MockObject
     */
    private $uiConfigResolver;

    /**
     * @var IsCaptchaEnabledInterface|MockObject
     */
    private $isCaptchEnabled;

    /**
     * @var Onepage
     */
    private $model;

    /**
     * @var array
     */
    private $jsLayout = [
        'components' => [
            'checkout' => [
                'children' => [
                    'steps' => [
                        'children' => [
                            'billing-step' => [
                                'children' => [
                                    'payment' => [
                                        'children' => [
                                            'payments-list' => [
                                                'children' => [
                                                    'before-place-order' => [
                                                        'children' => [
                                                            'place-order-recaptcha' => [
                                                                'skipPayments' => []
                                                            ]
                                                        ]
                                                    ],
                                                    'paypal-captcha' => [
                                                        'children' => [
                                                            'recaptcha' => []
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->uiConfigResolver = $this->getMockForAbstractClass(UiConfigResolverInterface::class);
        $this->isCaptchEnabled = $this->getMockForAbstractClass(IsCaptchaEnabledInterface::class);
        $this->model = new Onepage(
            $this->uiConfigResolver,
            $this->isCaptchEnabled
        );
    }

    /**
     * @dataProvider processDataProvider
     */
    public function testProcess(array $mocks, array $expected): void
    {
        $this->uiConfigResolver->method('get')
            ->willReturnMap($mocks['uiConfigResolver']);
        $this->isCaptchEnabled->method('isCaptchaEnabledFor')
            ->willReturnMap($mocks['isCaptchaEnabled']);
        $prefix = 'components/checkout/children/';
        $config = new DataObject($this->model->process($this->jsLayout));
        $actual = [];
        foreach (array_keys($expected) as $path) {
            $actual[$path] = $config->getDataByPath($prefix . $path);
        }
        $this->assertSame($expected, $actual);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function processDataProvider(): array
    {
        return [
            [
                [
                    'isCaptchaEnabled' => [
                        ['paypal_payflowpro', false],
                        ['place_order', false],
                    ],
                    'uiConfigResolver' => [
                        ['paypal_payflowpro', ['type' => 'invisible']],
                        ['place_order', ['type' => 'robot']],
                    ],
                ],
                [
                    'steps/children/billing-step/children/payment/children/payments-list/children/paypal-captcha/' .
                    'children' => [],
                    'steps/children/billing-step/children/payment/children/payments-list/children/before-place-order/' .
                    'children' => [
                        'place-order-recaptcha' => [
                            'skipPayments' => []
                        ]
                    ],
                ]
            ],
            [
                [
                    'isCaptchaEnabled' => [
                        ['paypal_payflowpro', false],
                        ['place_order', true],
                    ],
                    'uiConfigResolver' => [
                        ['paypal_payflowpro', ['type' => 'invisible']],
                        ['place_order', ['type' => 'robot']],
                    ],
                ],
                [
                    'steps/children/billing-step/children/payment/children/payments-list/children/paypal-captcha/' .
                    'children' => [],
                    'steps/children/billing-step/children/payment/children/payments-list/children/before-place-order/' .
                    'children' => [
                        'place-order-recaptcha' => [
                            'skipPayments' => [
                                Config::METHOD_EXPRESS => true,
                                Config::METHOD_WPP_PE_EXPRESS => true,
                                Config::METHOD_WPP_PE_BML => true,
                            ]
                        ]
                    ],
                ]
            ],
            [
                [
                    'isCaptchaEnabled' => [
                        ['paypal_payflowpro', true],
                        ['place_order', false],
                    ],
                    'uiConfigResolver' => [
                        ['paypal_payflowpro', ['type' => 'invisible']],
                        ['place_order', ['type' => 'robot']],
                    ],
                ],
                [
                    'steps/children/billing-step/children/payment/children/payments-list/children/paypal-captcha/' .
                    'children' => ['recaptcha' => ['settings' => ['type' => 'invisible']]],
                    'steps/children/billing-step/children/payment/children/payments-list/children/before-place-order/' .
                    'children' => [
                        'place-order-recaptcha' => [
                            'skipPayments' => []
                        ]
                    ],
                ]
            ],
            [
                [
                    'isCaptchaEnabled' => [
                        ['paypal_payflowpro', true],
                        ['place_order', true],
                    ],
                    'uiConfigResolver' => [
                        ['paypal_payflowpro', ['type' => 'invisible']],
                        ['place_order', ['type' => 'robot']],
                    ],
                ],
                [
                    'steps/children/billing-step/children/payment/children/payments-list/children/paypal-captcha/' .
                    'children' => ['recaptcha' => ['settings' => ['type' => 'invisible']]],
                    'steps/children/billing-step/children/payment/children/payments-list/children/before-place-order/' .
                    'children' => [
                        'place-order-recaptcha' => [
                            'skipPayments' => [
                                Config::METHOD_EXPRESS => true,
                                Config::METHOD_WPP_PE_EXPRESS => true,
                                Config::METHOD_WPP_PE_BML => true,
                                Config::METHOD_PAYFLOWPRO => true
                            ]
                        ]
                    ],
                ]
            ]
        ];
    }
}
