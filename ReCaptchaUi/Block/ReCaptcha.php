<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaUi\Block;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Magento\ReCaptchaUi\Model\IsCaptchaEnabledInterface;
use Magento\ReCaptchaUi\Model\UiConfigResolverInterface;

/**
 * @api
 */
class ReCaptcha extends Template
{
    /**
     * @var UiConfigResolverInterface
     */
    private $captchaUiConfigResolver;

    /**
     * @var IsCaptchaEnabledInterface
     */
    private $isCaptchaEnabled;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param Template\Context $context
     * @param UiConfigResolverInterface $captchaUiConfigResolver
     * @param IsCaptchaEnabledInterface $isCaptchaEnabled
     * @param Json $serializer
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        UiConfigResolverInterface $captchaUiConfigResolver,
        IsCaptchaEnabledInterface $isCaptchaEnabled,
        Json $serializer,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->captchaUiConfigResolver = $captchaUiConfigResolver;
        $this->isCaptchaEnabled = $isCaptchaEnabled;
        $this->serializer = $serializer;
    }

    /**
     * Get reCAPTCHA ID
     */
    public function getRecaptchaId(): string
    {
        return (string)$this->getData('recaptcha_id') ?: 'recaptcha-' . sha1($this->getNameInLayout());
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     * @throws InputException
     */
    public function getJsLayout(): string
    {
        $layout = $this->serializer->unserialize(parent::getJsLayout());

        if (isset($layout['components']['recaptcha'])) {
            $layout['components'][$this->getRecaptchaId()] = $layout['components']['recaptcha'];
            unset($layout['components']['recaptcha']);
        }

        $layout['components'][$this->getRecaptchaId()] = array_replace_recursive(
            [
                'settings' => $this->getCaptchaUiConfig(),
            ],
            $layout['components'][$this->getRecaptchaId()]
        );
        $layout['components'][$this->getRecaptchaId()]['reCaptchaId'] = $this->getRecaptchaId();

        return $this->serializer->serialize($layout);
    }

    /**
     * Get UI config for reCAPTCHA rendering
     *
     * @return array
     * @throws InputException
     */
    public function getCaptchaUiConfig(): array
    {
        $key = (string) $this->getData('recaptcha_for');
        $uiConfig = (array) $this->getData('captcha_ui_config');

        return array_replace_recursive($this->captchaUiConfigResolver->get($key), $uiConfig);
    }


    /**
     * @return string
     * @throws InputException
     */
    public function toHtml(): string
    {
        $key = $this->getData('recaptcha_for');
        if (empty($key) || !$this->isCaptchaEnabled->isCaptchaEnabledFor($key)) {
            return '';
        }

        return parent::toHtml();
    }
}
