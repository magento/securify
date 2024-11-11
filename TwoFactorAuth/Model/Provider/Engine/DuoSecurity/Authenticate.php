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
     * @param DataObjectFactory $dataObjectFactory
     * @param UserAuthenticator $userAuthenticator
     */
    public function __construct(
        UserFactory $userFactory,
        AlertInterface $alert,
        DuoSecurity $duo,
        AdminTokenServiceInterface $adminTokenService,
        DataObjectFactory $dataObjectFactory,
        UserAuthenticator $userAuthenticator
    ) {
        $this->userFactory = $userFactory;
        $this->alert = $alert;
        $this->duo = $duo;
        $this->adminTokenService = $adminTokenService;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->userAuthenticator = $userAuthenticator;
    }

    /**
     * @inheritDoc
     */
    public function createAdminAccessTokenWithCredentials(
        string $username,
        string $password,
        string $passcode
    ): string {
        $token = $this->adminTokenService->createAdminAccessToken($username, $password);

        $user = $this->getUser($username);
        $this->userAuthenticator->assertProviderIsValidForUser((int)$user->getId(), DuoSecurity::CODE);

        $this->assertResponseIsValid($user, $username, $passcode);

        return $token;
    }

    /**
     * Assert that the given signature is valid for the user
     *
     * @param UserInterface $user
     * @param string $username
     * @throws LocalizedException
     */
    public function assertResponseIsValid(UserInterface $user, $username, string $passcode): void
    {
        $duoAuthResponse = $this->duo->authorizeUser($username,"passcode", ['passcode' => $passcode]);
        if ($duoAuthResponse['status'] !== 'allow') {
            $this->alert->event(
                'Magento_TwoFactorAuth',
                'DuoSecurity invalid auth '. $duoAuthResponse['msg'],
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
