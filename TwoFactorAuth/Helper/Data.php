<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */

namespace Magento\TwoFactorAuth\Helper;

use Magento\Backend\Model\Auth\Session;

class Data
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Get form key
     *
     * @return string
     */
    public function getSavedDuoState(): string
    {
        return $this->session->getDuoState();
    }
}
