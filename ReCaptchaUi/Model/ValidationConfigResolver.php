<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaUi\Model;

use Magento\Framework\Exception\InputException;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigInterface;

/**
 * Extension point for resolving reCAPTCHA Validation config
 *
 * It is NOT direct part of reCAPTCHA validation but performs role of bridge between UI and reCAPTCHA validation
 *
 * @api Class name should be used in DI for adding new Validation config providers
 *      but for config resolving need to use ValidationConfigResolverInterface
 */
class ValidationConfigResolver implements ValidationConfigResolverInterface
{
    /**
     * @var CaptchaTypeResolverInterface
     */
    private $captchaTypeResolver;

    /**
     * @var ValidationConfigProviderInterface[]
     */
    private $validationConfigProviders;

    /**
     * @param CaptchaTypeResolverInterface $captchaTypeResolver
     * @param ValidationConfigProviderInterface[] $validationConfigProviders
     */
    public function __construct(
        CaptchaTypeResolverInterface $captchaTypeResolver,
        array $validationConfigProviders = []
    ) {
        $this->captchaTypeResolver = $captchaTypeResolver;
        $this->validationConfigProviders = $validationConfigProviders;
    }

    /**
     * @inheritdoc
     */
    public function get(string $key): ValidationConfigInterface
    {
        $captchaType = $this->captchaTypeResolver->getCaptchaTypeFor($key);

        if (!isset($this->validationConfigProviders[$captchaType])) {
            throw new InputException(
                __('Validation config provider for "%type" is not configured.', ['type' => $captchaType])
            );
        }

        return $this->validationConfigProviders[$captchaType]->get();
    }
}
