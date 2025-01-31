<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaVersion2Invisible\Model\Frontend;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\ReCaptchaUi\Model\UiConfigProviderInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * @inheritdoc
 */
class UiConfigProvider implements UiConfigProviderInterface
{
    private const XML_PATH_PUBLIC_KEY = 'recaptcha_frontend/type_invisible/public_key';
    private const XML_PATH_POSITION = 'recaptcha_frontend/type_invisible/position';
    private const XML_PATH_THEME = 'recaptcha_frontend/type_invisible/theme';
    private const XML_PATH_LANGUAGE_CODE = 'recaptcha_frontend/type_invisible/lang';

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
     * Get language code
     *
     * @return string
     */
    private function getLanguageCode(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_LANGUAGE_CODE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get Invisible Badge Position
     *
     * @return string
     */
    private function getInvisibleBadgePosition(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_POSITION, ScopeInterface::SCOPE_WEBSITE);
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
     * Get theme
     *
     * @return string
     */
    private function getTheme(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_THEME, ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @inheritdoc
     */
    public function get(): array
    {
        return [
            'rendering' => [
                'sitekey' => $this->getPublicKey(),
                'badge' => $this->getInvisibleBadgePosition(),
                'size' => 'invisible',
                'theme' => $this->getTheme(),
                'hl' => $this->getLanguageCode()
            ],
            'invisible' => true,
        ];
    }
}
