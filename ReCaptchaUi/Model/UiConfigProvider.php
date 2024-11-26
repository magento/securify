<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaUi\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class UiConfigProvider implements UiConfigProviderInterface
{
    private const CONFIG_PATH_RECAPTCHA_API_URL = 'recaptcha/api/url';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    private function getApiUrl(): string
    {
        return (string)$this->scopeConfig->getValue(self::CONFIG_PATH_RECAPTCHA_API_URL, ScopeInterface::SCOPE_WEBSITE);
    }

    public function get(): array
    {
        return [
            'api_url' => $this->getApiUrl(),
        ];
    }
}
