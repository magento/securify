<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained from
 * Adobe.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaWebapiGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\ReCaptchaFrontendUi\Model\CaptchaTypeResolver;
use Magento\ReCaptchaFrontendUi\Model\ErrorMessageConfig;

/**
 * Query returning reCaptcha configuration details for selected form type
 */
class ReCaptchaFormConfig implements ResolverInterface
{
    /**
     * @var array
     */
    private array $reCaptchaConfigProviders;

    /**
     * @var array
     */
    private array $formTypes;

    /**
     * @param CaptchaTypeResolver $captchaTypeResolver
     * @param ErrorMessageConfig $errorMessageConfig
     * @param array $providers
     * @param array $formTypes
     */
    public function __construct(
        private readonly CaptchaTypeResolver $captchaTypeResolver,
        private readonly ErrorMessageConfig $errorMessageConfig,
        array $providers = [],
        array $formTypes = []
    ) {
        $this->reCaptchaConfigProviders = $providers;
        $this->formTypes = $formTypes;
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null,
    ) {
        try {
            $captchaType = $this->captchaTypeResolver->getCaptchaTypeFor($this->formTypes[$args['formType']]);

            if (!$captchaType) {
                return [
                    'is_enabled' => false,
                    'configurations' => null
                ];
            }

            $reCaptchaConfigProvider = $this->reCaptchaConfigProviders[$captchaType];
            return [
                'is_enabled' => true,
                'configurations' => [
                    're_captcha_type' => mb_strtoupper($captchaType),
                    'website_key' => $reCaptchaConfigProvider->getWebsiteKey(),
                    'minimum_score' => $reCaptchaConfigProvider->getMinimumScore(),
                    'badge_position' => $reCaptchaConfigProvider->getBadgePosition(),
                    'theme' => $reCaptchaConfigProvider->getTheme(),
                    'language_code' => $reCaptchaConfigProvider->getLanguageCode(),
                    'validation_failure_message' => $this->errorMessageConfig->getValidationFailureMessage(),
                    'technical_failure_message' => $this->errorMessageConfig->getTechnicalFailureMessage()
                ]
            ];
        } catch (\Exception $e) {
            throw new GraphQlInputException(
                __('Configuration for provided captcha type can not be retrieved.')
            );
        }
    }
}
