<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaUser\Model;

use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;

/**
 * Disable reCAPTCHA for user login (causes config cache flush)
 */
class DisableReCaptchaForUserLogin
{
    private const XML_PATH_ENABLED = 'recaptcha_backend/type_for/user_login';

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var Manager
     */
    private $cacheManager;

    /**
     * @param ConfigInterface $config
     * @param Manager $cacheManager
     */
    public function __construct(
        ConfigInterface $config,
        Manager $cacheManager
    ) {
        $this->config = $config;
        $this->cacheManager = $cacheManager;
    }

    /**
     * Disable reCAPTCHA for user login (causes config cache flush)
     */
    public function execute(): void
    {
        $this->config->saveConfig(self::XML_PATH_ENABLED, null);
        $this->cacheManager->flush(['config']);
    }
}
