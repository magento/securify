<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Model\Provider\Engine\DuoSecurity;

use Magento\TwoFactorAuth\Api\DuoConfigureInterface;
use Magento\TwoFactorAuth\Api\TfaInterface;
use Magento\TwoFactorAuth\Model\Provider\Engine\DuoSecurity;
use Magento\TwoFactorAuth\Model\UserAuthenticator;

/**
 * Configure duo
 */
class Configure implements DuoConfigureInterface
{
    /**
     * @var UserAuthenticator
     */
    private $userAuthenticator;

    /**
     * @var DuoSecurity
     */
    private $duo;

    /**
     * @var TfaInterface
     */
    private $tfa;

    /**
     * @var Authenticate
     */
    private $authenticate;

    /**
     * @param UserAuthenticator $userAuthenticator
     * @param DuoSecurity $duo
     * @param TfaInterface $tfa
     * @param Authenticate $authenticate
     */
    public function __construct(
        UserAuthenticator $userAuthenticator,
        DuoSecurity $duo,
        TfaInterface $tfa,
        Authenticate $authenticate
    ) {
        $this->userAuthenticator = $userAuthenticator;
        $this->duo = $duo;
        $this->tfa = $tfa;
        $this->authenticate = $authenticate;
    }

    /**
     * @inheritDoc
     */
    public function getConfigurationData(string $tfaToken)
    {
        $user = $this->userAuthenticator->authenticateWithTokenAndProvider($tfaToken, DuoSecurity::CODE);
        return $this->duo->enrollNewUser($user->getUserName(), 60);
    }

    /**
     * @inheritDoc
     */
    public function activate(string $tfaToken): void
    {
        $user = $this->userAuthenticator->authenticateWithTokenAndProvider($tfaToken, DuoSecurity::CODE);
        $userId = (int)$user->getId();

        if ($this->duo->assertUserIsValid($user->getUserName()) == "auth") {
            $this->tfa->getProviderByCode(DuoSecurity::CODE)
                ->activate($userId);
        }
    }
}
