<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\TwoFactorAuth\Controller\Adminhtml\Duo;

use Magento\Backend\Model\Auth\Session;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\DataObjectFactory;
use Magento\TwoFactorAuth\Model\AlertInterface;
use Magento\TwoFactorAuth\Api\TfaInterface;
use Magento\TwoFactorAuth\Api\TfaSessionInterface;
use Magento\TwoFactorAuth\Controller\Adminhtml\AbstractAction;
use Magento\TwoFactorAuth\Model\Provider\Engine\DuoSecurity;
use Magento\TwoFactorAuth\Api\UserConfigManagerInterface;
use Magento\TwoFactorAuth\Model\UserConfig\HtmlAreaTokenVerifier;

/**
 * Duo security authentication post controller
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class Authpost extends AbstractAction implements HttpGetActionInterface
{
    /**
     * @var TfaInterface
     */
    private $tfa;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var TfaSessionInterface
     */
    private $tfaSession;

    /**
     * @var DuoSecurity
     */
    private $duoSecurity;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var AlertInterface
     */
    private $alert;

    /**
     * @var Action\Context
     */
    private $context;

    /**
     * @var HtmlAreaTokenVerifier
     */
    private $tokenVerifier;

    /**
     * @var UserConfigManagerInterface
     */
    private $userConfig;

    /**
     * Authpost constructor.
     * @param Action\Context $context
     * @param Session $session
     * @param DuoSecurity $duoSecurity
     * @param TfaSessionInterface $tfaSession
     * @param DataObjectFactory $dataObjectFactory
     * @param AlertInterface $alert
     * @param TfaInterface $tfa
     * @param HtmlAreaTokenVerifier $tokenVerifier
     * @param UserConfigManagerInterface $userConfig
     */
    public function __construct(
        Action\Context $context,
        Session $session,
        DuoSecurity $duoSecurity,
        TfaSessionInterface $tfaSession,
        DataObjectFactory $dataObjectFactory,
        AlertInterface $alert,
        TfaInterface $tfa,
        HtmlAreaTokenVerifier $tokenVerifier,
        UserConfigManagerInterface $userConfig
    ) {
        parent::__construct($context);
        $this->tfa = $tfa;
        $this->session = $session;
        $this->tfaSession = $tfaSession;
        $this->duoSecurity = $duoSecurity;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->alert = $alert;
        $this->context = $context;
        $this->tokenVerifier = $tokenVerifier;
        $this->userConfig = $userConfig;
    }

    /**
     * Get current user
     *
     * @return \Magento\User\Model\User|null
     */
    private function getUser()
    {
        return $this->session->getUser();
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $user = $this->getUser();
        $username = $user->getUserName();
        $savedState = $this->session->getDuoState();

        if (!empty($savedState) && !empty($username) && ($this->getRequest()->getParam('state') == $savedState)) {
            if ($this->duoSecurity->verify($user, $this->dataObjectFactory->create([
                'data' => $this->getRequest()->getParams(),
            ]))) {
                $this->tfa->getProvider(DuoSecurity::CODE)->activate((int) $user->getId());
                $this->tfaSession->grantAccess();
                return $this->_redirect($this->context->getBackendUrl()->getStartupPageUrl());
            }
        } else {
            $this->alert->event(
                'Magento_TwoFactorAuth',
                'DuoSecurity invalid auth',
                AlertInterface::LEVEL_WARNING,
                $user->getUserName()
            );

            return $this->_redirect('*/*/auth');
        }

        return $this->_redirect('*/*/auth');
    }

    /**
     * @inheritDoc
     */
    protected function _isAllowed()
    {
        if (!parent::_isAllowed()) {
            return false;
        }

        // 1st time users must have the token.
        $user = $this->getUser();

        return
            $user &&
            $this->tfa->getProviderIsAllowed((int)$user->getId(), DuoSecurity::CODE)
            && (
                $this->userConfig->isProviderConfigurationActive((int)$user->getId(), DuoSecurity::CODE)
                || $this->tokenVerifier->isConfigTokenProvided()
            );
    }
}
