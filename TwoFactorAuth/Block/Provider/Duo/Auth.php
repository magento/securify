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

/**
 * @api
 */
class Auth extends Template
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @param Template\Context $context
     * @param Session $session
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Session $session,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->session = $session;
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
        $authUrl = $this->getData('auth_url');
        if ($authUrl) {
            $this->jsLayout['components']['tfa-auth']['authUrl'] = $authUrl;
        }
        return parent::getJsLayout();
    }
}
