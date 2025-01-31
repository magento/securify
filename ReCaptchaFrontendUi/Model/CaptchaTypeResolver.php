<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaFrontendUi\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\ReCaptchaUi\Model\CaptchaTypeResolverInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * @inheritdoc
 */
class CaptchaTypeResolver implements CaptchaTypeResolverInterface
{
    private const XML_PATH_TYPE_FOR = 'recaptcha_frontend/type_for/';

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
    public function getCaptchaTypeFor(string $key): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_TYPE_FOR . $key, ScopeInterface::SCOPE_WEBSITE);
    }
}
