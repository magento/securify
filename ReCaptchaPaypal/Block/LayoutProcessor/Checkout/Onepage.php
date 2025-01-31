<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaPaypal\Block\LayoutProcessor\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Magento\Framework\Exception\InputException;
use Magento\Paypal\Model\Config;
use Magento\ReCaptchaUi\Model\IsCaptchaEnabledInterface;
use Magento\ReCaptchaUi\Model\UiConfigResolverInterface;

/**
 * Provides reCaptcha component configuration.
 */
class Onepage implements LayoutProcessorInterface
{
    /**
     * @var UiConfigResolverInterface
     */
    private $captchaUiConfigResolver;

    /**
     * @var IsCaptchaEnabledInterface
     */
    private $isCaptchaEnabled;

    /**
     * @param UiConfigResolverInterface $captchaUiConfigResolver
     * @param IsCaptchaEnabledInterface $isCaptchaEnabled
     */
    public function __construct(
        UiConfigResolverInterface $captchaUiConfigResolver,
        IsCaptchaEnabledInterface $isCaptchaEnabled
    ) {
        $this->captchaUiConfigResolver = $captchaUiConfigResolver;
        $this->isCaptchaEnabled = $isCaptchaEnabled;
    }

    /**
     * @inheritdoc
     *
     * @param array $jsLayout
     * @return array
     * @throws InputException
     */
    public function process($jsLayout): array
    {
        $key = 'paypal_payflowpro';
        $skipCheckoutRecaptchaForPayments = [
            Config::METHOD_EXPRESS => true,
            Config::METHOD_WPP_PE_EXPRESS => true,
            Config::METHOD_WPP_PE_BML => true,
        ];
        if ($this->isCaptchaEnabled->isCaptchaEnabledFor($key)) {
            $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
            ['payment']['children']['payments-list']['children']['paypal-captcha']['children']
            ['recaptcha']['settings'] = $this->captchaUiConfigResolver->get($key);
            if ($this->isCaptchaEnabled->isCaptchaEnabledFor('place_order')) {
                $skipCheckoutRecaptchaForPayments[Config::METHOD_PAYFLOWPRO] = true;
            }
        } else {
            if (isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children']['payments-list']['children']['paypal-captcha']['children']['recaptcha'])) {
                unset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children']['paypal-captcha']['children']['recaptcha']);
            }
        }
        if ($this->isCaptchaEnabled->isCaptchaEnabledFor('place_order')) {
            $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
            ['payment']['children']['payments-list']['children']['before-place-order']['children']
            ['place-order-recaptcha']['skipPayments'] += $skipCheckoutRecaptchaForPayments;
        }

        return $jsLayout;
    }
}
