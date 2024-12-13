<?php
/**
 * Copyright 2020 Adobe
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
        $user = $this->session->getUser();
        if (!$user) {
            throw new LocalizedException(__('User session not found.'));
        }
        $username = $user->getUserName();
        $state = $this->duoSecurity->generateDuoState();
        $this->session->setDuoState($state);
        $response = $this->duoSecurity->initiateAuth($username, $state);

        if ($response['status'] == 'open') {
            $this->messageManager->addErrorMessage($response['message']);
        } elseif ($response['status'] == 'closed') {
            $this->messageManager->addErrorMessage($response['message']);
        }

        $this->jsLayout['components']['tfa-auth']['authUrl'] = $response['redirect_url'];
        return parent::getJsLayout();
    }
}
