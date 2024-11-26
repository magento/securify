<?php

declare(strict_types=1);

namespace Magento\ReCaptchaContact\Model;

use Magento\ContactGraphQl\Model\Resolver\ContactUs;
use Magento\ReCaptchaUi\Model\IsCaptchaEnabledInterface;
use Magento\ReCaptchaUi\Model\ValidationConfigResolverInterface;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigInterface;
use Magento\ReCaptchaWebapiApi\Api\Data\EndpointInterface;
use Magento\ReCaptchaWebapiApi\Api\WebapiValidationConfigProviderInterface;

class WebapiConfigProvider implements WebapiValidationConfigProviderInterface
{
    private const CAPTCHA_ID = 'contact';

    public function __construct(
        private readonly IsCaptchaEnabledInterface $isCaptchaEnabled,
        private readonly ValidationConfigResolverInterface $configResolver
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getConfigFor(EndpointInterface $endpoint): ?ValidationConfigInterface
    {
        if ($endpoint->getServiceMethod() === 'resolve'
            && $endpoint->getServiceClass() === ContactUs::class) {
            if ($this->isCaptchaEnabled->isCaptchaEnabledFor(self::CAPTCHA_ID)) {
                return $this->configResolver->get(self::CAPTCHA_ID);
            }
        }

        return null;
    }
}
