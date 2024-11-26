<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaVersion2Checkbox\Model\Frontend;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\ReCaptchaUi\Model\UiConfigProviderInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * @inheritdoc
 */
class UiConfigProvider implements UiConfigProviderInterface
{
    private const XML_PATH_PUBLIC_KEY = 'recaptcha_frontend/type_recaptcha/public_key';
    private const XML_PATH_SIZE = 'recaptcha_frontend/type_recaptcha/size';
    private const XML_PATH_THEME = 'recaptcha_frontend/type_recaptcha/theme';
    private const XML_PATH_LANGUAGE_CODE = 'recaptcha_frontend/type_recaptcha/lang';

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
     * Get Google API Website Key
     *
     * @return string
     */
    private function getPublicKey(): string
    {
        return trim((string)$this->scopeConfig->getValue(self::XML_PATH_PUBLIC_KEY, ScopeInterface::SCOPE_WEBSITE));
    }

    /**
     * Get size
     *
     * @return string
     */
    private function getSize(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_SIZE, ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * Get theme
     *
     * @return string
     */
    private function getTheme(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_THEME, ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * Get language code
     *
     * @return string
     */
    private function getLanguageCode(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_LANGUAGE_CODE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Return frontend UI config for reCAPTCHA v2.
     *
     * @return array
     */
    public function get(): array
    {
        return [
            'rendering' => [
                'sitekey' => $this->getPublicKey(),
                'size' => $this->getSize(),
                'theme' => $this->getTheme(),
                'hl' => $this->getLanguageCode(),
            ],
            'invisible' => false,
        ];
    }
}
