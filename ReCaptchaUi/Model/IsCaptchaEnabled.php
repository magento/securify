<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaUi\Model;

use Magento\Framework\Exception\InputException;

/**
 * @inheritdoc
 */
class IsCaptchaEnabled implements IsCaptchaEnabledInterface
{
    /**
     * @var CaptchaTypeResolverInterface
     */
    private $captchaTypeResolver;

    /**
     * @var UiConfigResolverInterface
     */
    private $uiConfigResolver;

    /**
     * @var ValidationConfigResolverInterface
     */
    private $validationConfigResolver;

    /**
     * @param CaptchaTypeResolverInterface $captchaTypeResolver
     * @param UiConfigResolverInterface $uiConfigResolver
     * @param ValidationConfigResolverInterface $validationConfigResolver
     */
    public function __construct(
        CaptchaTypeResolverInterface $captchaTypeResolver,
        UiConfigResolverInterface $uiConfigResolver,
        ValidationConfigResolverInterface $validationConfigResolver
    ) {
        $this->captchaTypeResolver = $captchaTypeResolver;
        $this->uiConfigResolver = $uiConfigResolver;
        $this->validationConfigResolver = $validationConfigResolver;
    }

    /**
     * @inheritdoc
     */
    public function isCaptchaEnabledFor(string $key): bool
    {
        return (null !== $this->captchaTypeResolver->getCaptchaTypeFor($key)) && $this->areKeysConfigured($key);
    }

    /**
     * Return true if reCAPTCHA keys (public and private) are configured
     *
     * @param string $key
     * @return bool
     * @throws InputException
     */
    private function areKeysConfigured(string $key): bool
    {
        $uiConfig = $this->uiConfigResolver->get($key);
        $validationConfig = $this->validationConfigResolver->get($key);

        // TODO:
        return $validationConfig->getPrivateKey() && !empty($uiConfig['rendering']['sitekey']);
    }
}
