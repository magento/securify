<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Controller\Adminhtml\Tfa;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\TwoFactorAuth\Api\ProviderInterface;
use Magento\TwoFactorAuth\Api\TfaInterface;
use Magento\TwoFactorAuth\Controller\Adminhtml\AbstractAction;
use Magento\TwoFactorAuth\Model\UserConfig\HtmlAreaTokenVerifier;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface as ConfigResource;
use Magento\Framework\App\Config\ReinitableConfigInterface as ConfigInterface;

/**
 * Configure 2FA for the application.
 */
class Configurepost extends AbstractAction implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Magento_TwoFactorAuth::config';

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var ConfigResource
     */
    private $configResource;

    /**
     * @var TfaInterface
     */
    private $tfa;

    /**
     * @var HtmlAreaTokenVerifier
     */
    private $tokenVerifier;

    /**
     * @var string
     */
    private $startUpUrl;

    /**
     * @var Session
     */
    private $session;

    /**
     * @param Context $context
     * @param ConfigInterface $config
     * @param ConfigResource $configResource
     * @param TfaInterface $tfa
     * @param HtmlAreaTokenVerifier $tokenVerifier
     * @param Session $session
     */
    public function __construct(
        Context $context,
        ConfigInterface $config,
        ConfigResource $configResource,
        TfaInterface $tfa,
        HtmlAreaTokenVerifier $tokenVerifier,
        Session $session
    ) {
        parent::__construct($context);
        $this->startUpUrl = $context->getBackendUrl()->getStartupPageUrl();
        $this->config = $config;
        $this->configResource = $configResource;
        $this->tfa = $tfa;
        $this->tokenVerifier = $tokenVerifier;
        $this->session = $session;
    }

    /**
     * Validate user input
     *
     * @param mixed $selected
     * @return bool
     */
    private function validate($selected): bool
    {
        $providerCodes = array_map(
            function (ProviderInterface $provider): string {
                return $provider->getCode();
            },
            $this->tfa->getAllEnabledProviders()
        );

        return is_array($selected) && !array_diff(array_keys($selected), $providerCodes);
    }

    /**
     * @inheritDoc
     */
    protected function _isAllowed()
    {
        $user = $this->session->getUser();
        if ($user && !$this->tokenVerifier->isConfigTokenProvided()) {
            return false;
        }

        return parent::_isAllowed();
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $selected = $this->getRequest()->getParam('tfa_selected');
        if ($this->validate($selected)) {
            $this->configResource->saveConfig(
                TfaInterface::XML_PATH_FORCED_PROVIDERS,
                implode(',', array_keys($selected))
            );
            $this->config->reinit();
            $this->getMessageManager()->addSuccessMessage(
                __('Two-Factor Authorization providers have been successfully configured')
            );

            return $this->_redirect($this->startUpUrl);
        } else {
            $this->getMessageManager()->addErrorMessage(__('Please select valid providers.'));

            return $this->_redirect('tfa/tfa/configure');
        }
    }
}
