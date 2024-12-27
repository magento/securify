<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaCheckout\Test\Unit\Block\LayoutProcessor\Checkout;

use Magento\Framework\DataObject;
use Magento\ReCaptchaCheckout\Block\LayoutProcessor\Checkout\Onepage;
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
                            'shipping-step' => [
                                'children' => [
                                    'shippingAddress' => [
                                        'children' => [
                                            'customer-email' => [
                                                'children' => [
                                                    'recaptcha' => []
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            'billing-step' => [
                                'children' => [
                                    'payment' => [
                                        'children' => [
                                            'customer-email' => [
                                                'children' => [
                                                    'recaptcha' => []
                                                ]
                                            ],
                                            'payments-list' => [
                                                'children' => [
                                                    'before-place-order' => [
                                                        'children' => [
                                                            'place-order-recaptcha' => []
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'authentication' => [
                        'children' => [
                            'recaptcha' => []
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
            $actual[$path] = $config->getDataByPath($prefix.$path);
        }
        $this->assertSame($expected, $actual);
    }

    public static function processDataProvider(): array
    {
        return [
            [
                [
                    'isCaptchaEnabled' => [
                        ['customer_login', false],
                        ['place_order', false],
                    ],
                    'uiConfigResolver' => [
                        ['customer_login', ['type' => 'invisible']],
                        ['place_order', ['type' => 'robot']],
                    ],
                ],
                [
                    'steps/children/shipping-step/children/shippingAddress/children/customer-email/children' => [],
                    'steps/children/billing-step/children/payment/children/customer-email/children' => [],
                    'authentication/children' => [],
                    'steps/children/billing-step/children/payment/children/payments-list/children/before-place-order/' .
                    'children' => [],
                ]
            ],
            [
                [
                    'isCaptchaEnabled' => [
                        ['customer_login', true],
                        ['place_order', true],
                    ],
                    'uiConfigResolver' => [
                        ['customer_login', ['type' => 'invisible']],
                        ['place_order', ['type' => 'robot']],
                    ],
                ],
                [
                    'steps/children/shipping-step/children/shippingAddress/children/' .
                    'customer-email/children' => ['recaptcha' => ['settings' => ['type' => 'invisible']]],
                    'steps/children/billing-step/children/payment/children/' .
                    'customer-email/children' => ['recaptcha' => ['settings' => ['type' => 'invisible']]],
                    'authentication/children' => ['recaptcha' => ['settings' => ['type' => 'invisible']]],
                    'steps/children/billing-step/children/payment/children/payments-list/children/before-place-order/' .
                    'children' => ['place-order-recaptcha' => ['settings' => ['type' => 'robot']]],
                ]
            ]
        ];
    }
}
