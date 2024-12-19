<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Model\Provider\Engine\DuoSecurity;

use Magento\TwoFactorAuth\Api\Data\DuoDataInterface;
use Magento\TwoFactorAuth\Api\Data\DuoDataInterfaceFactory;
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
     * @var DuoDataInterfaceFactory
     */
    private $dataFactory;

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
     * @param DuoDataInterfaceFactory $dataFactory
     * @param TfaInterface $tfa
     * @param Authenticate $authenticate
     */
    public function __construct(
        UserAuthenticator $userAuthenticator,
        DuoSecurity $duo,
        DuoDataInterfaceFactory $dataFactory,
        TfaInterface $tfa,
        Authenticate $authenticate
    ) {
        $this->userAuthenticator = $userAuthenticator;
        $this->duo = $duo;
        $this->dataFactory = $dataFactory;
        $this->tfa = $tfa;
        $this->authenticate = $authenticate;
    }

    /**
     * @inheritDoc
     */
    public function getConfigurationData(string $tfaToken): DuoDataInterface
    {
        $user = $this->userAuthenticator->authenticateWithTokenAndProvider($tfaToken, DuoSecurity::CODE);

        return $this->dataFactory->create(
            [
                'data' => [
                    DuoDataInterface::API_HOSTNAME => $this->duo->getApiHostname(),
                    DuoDataInterface::SIGNATURE => $this->duo->getRequestSignature($user)
                ]
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function activate(string $tfaToken, string $signatureResponse): void
    {
        $user = $this->userAuthenticator->authenticateWithTokenAndProvider($tfaToken, DuoSecurity::CODE);
        $userId = (int)$user->getId();

        $this->authenticate->assertResponseIsValid($user, $signatureResponse);
        $this->tfa->getProviderByCode(DuoSecurity::CODE)
            ->activate($userId);
    }

    /**
     * @inheritDoc
     */
    public function getDuoConfigurationData(string $tfaToken)
    {
        $user = $this->userAuthenticator->authenticateWithTokenAndProvider($tfaToken, DuoSecurity::CODE);
        return $this->duo->enrollNewUser($user->getUserName(), 60);
    }

    /**
     * @inheritDoc
     */
    public function duoActivate(string $tfaToken): void
    {
        $user = $this->userAuthenticator->authenticateWithTokenAndProvider($tfaToken, DuoSecurity::CODE);
        $userId = (int)$user->getId();

        if ($this->duo->assertUserIsValid($user->getUserName()) == "auth") {
            $this->tfa->getProviderByCode(DuoSecurity::CODE)
                ->activate($userId);
        }
    }
}
