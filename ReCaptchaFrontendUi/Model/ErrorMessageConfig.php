<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaFrontendUi\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\ReCaptchaUi\Model\ErrorMessageConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * @inheritdoc
 */
class ErrorMessageConfig implements ErrorMessageConfigInterface
{
    private const XML_PATH_VALIDATION = 'recaptcha_frontend/failure_messages/validation_failure_message';
    private const XML_PATH_TECHNICAL = 'recaptcha_frontend/failure_messages/technical_failure_message';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritdoc
     */
    public function getTechnicalFailureMessage(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_TECHNICAL, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @inheritdoc
     */
    public function getValidationFailureMessage(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_VALIDATION, ScopeInterface::SCOPE_STORE);
    }
}
