<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Block\Provider\Duo;

use Magento\Backend\Block\Template;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\TwoFactorAuth\Model\Provider\Engine\DuoSecurity;

/**
 * @api
 */
class Auth extends Template
{
    /**
     * @var DuoSecurity
     */
    private $duoSecurity;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @param Template\Context $context
     * @param Session $session
     * @param DuoSecurity $duoSecurity
     * @param ManagerInterface $messageManager
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Session $session,
        DuoSecurity $duoSecurity,
        ManagerInterface $messageManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->duoSecurity = $duoSecurity;
        $this->session = $session;
        $this->messageManager = $messageManager;
    }

    /**
     * @inheritdoc
     */
    public function getJsLayout()
    {
        $duoFailMode = $this->duoSecurity->getDuoFailmode();
        try {
            $this->duoSecurity->healthCheck();
        } catch (LocalizedException $e) {
            if ($duoFailMode === "OPEN") {
                $this->messageManager->addSuccessMessage(
                    __("Login 'Successful', but 2FA Not Performed. Confirm Duo client/secret/host values are correct")
                );
                return $this->_url->getUrl('adminhtml/dashboard');
            } else {
                $this->messageManager->addErrorMessage(
                    __("2FA Unavailable. Confirm Duo client/secret/host values are correct")
                );
                return $this->_url->getUrl('adminhtml');
            }
        }

        $user = $this->session->getUser();
        if (!$user) {
            throw new LocalizedException(__('User session not found.'));
        }
        $username = $user->getUserName();
        $state = $this->duoSecurity->generateDuoState();
        $prompt_uri = $this->duoSecurity->initiateAuth($username, $state);
        $this->jsLayout['components']['tfa-auth']['authUrl'] = $prompt_uri;
        return parent::getJsLayout();
    }
}
