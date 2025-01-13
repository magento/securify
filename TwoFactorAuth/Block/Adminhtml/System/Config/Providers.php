<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);
namespace Magento\TwoFactorAuth\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Displays a warning modal if the all currently available providers are deselected
 */
class Providers extends Field
{
    /**
     * @var Json
     */
    private $json;

    /**
     * @param Context $context
     * @param Json $json
     * @param array $data
     */
    public function __construct(
        Context $context,
        Json $json,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->json = $json;
    }

    /**
     * @inheritdoc
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $html = parent::_getElementHtml($element);
        $config = [
            '#twofactorauth_general_force_providers' => [
                'Magento_TwoFactorAuth/js/system/config/providers' => [
                    'modalTitleText' => $this->getModalTitleText(),
                    'modalContentBody' => $this->getModalContentBody(),
                    'duoProviderValue' => 'duo_security',
                    'duoFields' => [
                        'twofactorauth_duo_client_id',
                        'twofactorauth_duo_client_secret',
                        'twofactorauth_duo_api_hostname',
                        'twofactorauth_duo_integration_key',
                        'twofactorauth_duo_secret_key',
                    ]
                ]
            ]
        ];
        $html .= '<script type="text/x-magento-init">' . $this->json->serialize($config) . '</script>';

        return $html;
    }

    /**
     * Get text for the modal title heading when user switches to disable
     *
     * @return Phrase
     */
    private function getModalTitleText() : Phrase
    {
        return __('Are you sure you want to disable all currently active providers?');
    }

    /**
     * Get HTML for the modal content body when user switches to disable
     *
     * @return string
     */
    private function getModalContentBody(): string
    {
        $templateFileName = $this->getTemplateFile(
            'Magento_TwoFactorAuth::system/config/providers/modal_content_body.phtml'
        );

        return $this->fetchView($templateFileName);
    }
}
