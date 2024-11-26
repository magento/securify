<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaVersion2Checkbox\Model\Frontend;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\ReCaptchaUi\Model\ValidationConfigProviderInterface;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigInterface;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigInterfaceFactory;
use Magento\Store\Model\ScopeInterface;

/**
 * @inheritdoc
 */
class ValidationConfigProvider implements ValidationConfigProviderInterface
{
    private const XML_PATH_PRIVATE_KEY = 'recaptcha_frontend/type_recaptcha/private_key';
    private const XML_PATH_VALIDATION_FAILURE = 'recaptcha_frontend/type_recaptcha/validation_failure_message';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    /**
     * @var ValidationConfigInterfaceFactory
     */
    private $validationConfigFactory;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param RemoteAddress $remoteAddress
     * @param ValidationConfigInterfaceFactory $validationConfigFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        RemoteAddress $remoteAddress,
        ValidationConfigInterfaceFactory $validationConfigFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->remoteAddress = $remoteAddress;
        $this->validationConfigFactory = $validationConfigFactory;
    }

    /**
     * Get Google API Secret Key
     *
     * @return string
     */
    private function getPrivateKey(): string
    {
        return trim((string)$this->scopeConfig->getValue(self::XML_PATH_PRIVATE_KEY, ScopeInterface::SCOPE_WEBSITE));
    }

    /**
     * Get validation failure message
     *
     * @return string
     */
    private function getValidationFailureMessage(): string
    {
        return trim(
            (string)$this->scopeConfig->getValue(self::XML_PATH_VALIDATION_FAILURE, ScopeInterface::SCOPE_STORE)
        );
    }

    /**
     * Return frontend Validation config for reCAPTCHA v2.
     *
     * @return ValidationConfigInterface
     */
    public function get(): ValidationConfigInterface
    {
        return $this->validationConfigFactory->create(
            [
                'privateKey' => $this->getPrivateKey(),
                'remoteIp' => $this->remoteAddress->getRemoteAddress(),
                'validationFailureMessage' => $this->getValidationFailureMessage(),
                'extensionAttributes' => null,
            ]
        );
    }
}
