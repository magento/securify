<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Model\Provider\Engine\DuoSecurity;

use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Integration\Api\AdminTokenServiceInterface;
use Magento\TwoFactorAuth\Api\Data\DuoDataInterface;
use Magento\TwoFactorAuth\Api\Data\DuoDataInterfaceFactory;
use Magento\TwoFactorAuth\Api\DuoAuthenticateInterface;
use Magento\TwoFactorAuth\Model\AlertInterface;
use Magento\TwoFactorAuth\Model\Provider\Engine\DuoSecurity;
use Magento\TwoFactorAuth\Model\UserAuthenticator;
use Magento\User\Api\Data\UserInterface;
use Magento\User\Model\UserFactory;

/**
 * Authenticate with duo
 */
class Authenticate implements DuoAuthenticateInterface
{
    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var AlertInterface
     */
    private $alert;

    /**
     * @var DuoSecurity
     */
    private $duo;

    /**
     * @var AdminTokenServiceInterface
     */
    private $adminTokenService;

    /**
     * @var DuoDataInterfaceFactory
     */
    private $dataFactory;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var UserAuthenticator
     */
    private $userAuthenticator;

    /**
     * @param UserFactory $userFactory
     * @param AlertInterface $alert
     * @param DuoSecurity $duo
     * @param AdminTokenServiceInterface $adminTokenService
     * @param DuoDataInterfaceFactory $dataFactory
     * @param DataObjectFactory $dataObjectFactory
     * @param UserAuthenticator $userAuthenticator
     */
    public function __construct(
        UserFactory $userFactory,
        AlertInterface $alert,
        DuoSecurity $duo,
        AdminTokenServiceInterface $adminTokenService,
        DuoDataInterfaceFactory $dataFactory,
        DataObjectFactory $dataObjectFactory,
        UserAuthenticator $userAuthenticator
    ) {
        $this->userFactory = $userFactory;
        $this->alert = $alert;
        $this->duo = $duo;
        $this->adminTokenService = $adminTokenService;
        $this->dataFactory = $dataFactory;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->userAuthenticator = $userAuthenticator;
    }

    /**
     * @inheritDoc
     */
    public function getAuthenticateData(string $username, string $password): DuoDataInterface
    {
        $this->adminTokenService->createAdminAccessToken($username, $password);

        $user = $this->getUser($username);
        $this->userAuthenticator->assertProviderIsValidForUser((int)$user->getId(), DuoSecurity::CODE);

        return $this->dataFactory->create(
            [
                'data' => [
                    DuoDataInterface::USER_ID => $this->duo->enrollNewUser($username, 60)
                ]
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function createAdminAccessTokenWithCredentials(
        string $username,
        string $password,
        string $userIdentifier,
        string $passcode
    ): string {
        $token = $this->adminTokenService->createAdminAccessToken($username, $password);

        $user = $this->getUser($username);
        $this->userAuthenticator->assertProviderIsValidForUser((int)$user->getId(), DuoSecurity::CODE);

        $this->assertResponseIsValid($user, $userIdentifier, $passcode);

        return $token;
    }

    /**
     * Assert that the given signature is valid for the user
     *
     * @param UserInterface $user
     * @param string $userIdentifier
     * @throws LocalizedException
     */
    public function assertResponseIsValid(UserInterface $user, string $userIdentifier, string $passcode): void
    {
        $data = $this->dataObjectFactory->create(
            [
                'data' => [
                    'user_id' => $userIdentifier
                ]
            ]
        );
        if (!$this->duo->duoAuthorize($userIdentifier,"passcode", ['passcode' => $passcode])) {
            $this->alert->event(
                'Magento_TwoFactorAuth',
                'DuoSecurity invalid auth',
                AlertInterface::LEVEL_WARNING,
                $user->getUserName()
            );

            throw new LocalizedException(__('Invalid response'));
        }
    }

    /**
     * Retrieve a user using the username
     *
     * @param string $username
     * @return UserInterface
     * @throws AuthenticationException
     */
    private function getUser(string $username): UserInterface
    {
        $user = $this->userFactory->create();
        $user->loadByUsername($username);
        $userId = (int)$user->getId();
        if ($userId === 0) {
            throw new AuthenticationException(__(
                'The account sign-in was incorrect or your account is disabled temporarily. '
                . 'Please wait and try again later.'
            ));
        }

        return $user;
    }
}
