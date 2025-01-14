<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaWebapiGraphQl\Model\Resolver;

use Magento\Framework\Exception\InputException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\ReCaptchaFrontendUi\Model\CaptchaTypeResolver;
use Magento\ReCaptchaFrontendUi\Model\ErrorMessageConfig;
use Magento\ReCaptchaVersion3Invisible\Model\Config;

class ReCaptchaV3 implements ResolverInterface, ResetAfterRequestInterface
{
    private const RECAPTCHA_TYPE = 'recaptcha_v3';

    /**
     * @var bool|null
     */
    private ?bool $isEnabled = null;

    /**
     * @var array
     */
    private array $forms = [];

    /**
     * @var Config
     */
    private Config $reCaptchaV3Config;

    /**
     * @var CaptchaTypeResolver
     */
    private CaptchaTypeResolver $captchaTypeResolver;

    /**
     * @var string|null
     */
    private ?string $failureMessage = null;

    /**
     * @var ErrorMessageConfig $errorMessageConfig
     */
    private ErrorMessageConfig $errorMessageConfig;

    /**
     * @param Config $reCaptchaV3Config
     * @param CaptchaTypeResolver $captchaTypeResolver
     * @param ErrorMessageConfig $errorMessageConfig
     */
    public function __construct(
        Config $reCaptchaV3Config,
        CaptchaTypeResolver $captchaTypeResolver,
        ErrorMessageConfig $errorMessageConfig
    ) {
        $this->reCaptchaV3Config = $reCaptchaV3Config;
        $this->captchaTypeResolver = $captchaTypeResolver;
        $this->errorMessageConfig = $errorMessageConfig;
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        return [
            'is_enabled' => $this->isEnabled(),
            'website_key' => $this->reCaptchaV3Config->getWebsiteKey(),
            'minimum_score' => $this->reCaptchaV3Config->getMinimumScore(),
            'badge_position' => $this->reCaptchaV3Config->getBadgePosition(),
            'language_code' => $this->reCaptchaV3Config->getLanguageCode(),
            'failure_message' => $this->getFailureMessage(),
            'forms' => $this->getEnumFormTypes(),
            'theme' => $this->reCaptchaV3Config->getTheme()
        ];
    }

    /**
     * Get whether service has all the required settings set up to be enabled or not
     *
     * @return bool
     * @throws InputException
     */
    public function isEnabled(): bool
    {
        if ($this->isEnabled === null) {
            $this->isEnabled = $this->reCaptchaV3Config->getValidationConfig()->getPrivateKey() &&
                !empty($this->reCaptchaV3Config->getWebsiteKey()) &&
                !empty($this->getEnumFormTypes());
        }
        return $this->isEnabled;
    }

    /**
     * Get form keys that are configured to ReCaptcha V3
     *
     * @return array
     * @throws InputException
     */
    private function getEnumFormTypes(): array
    {
        $forms = [];
        if (empty($this->forms)) {
            foreach ($this->reCaptchaV3Config->getFormTypes() as $formType) {
                if ($this->captchaTypeResolver->getCaptchaTypeFor($formType) === self::RECAPTCHA_TYPE) {
                    $forms[] = $formType;
                }
            }
        }
        return array_map('strtoupper', $forms);
    }

    /**
     * Get configured message sent in case of failure
     *
     * @return string
     */
    public function getFailureMessage(): string
    {
        if (!$this->failureMessage) {
            $this->failureMessage = $this->errorMessageConfig->getValidationFailureMessage();
        }
        return $this->failureMessage;
    }

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->isEnabled = null;
        $this->failureMessage = null;
    }
}
