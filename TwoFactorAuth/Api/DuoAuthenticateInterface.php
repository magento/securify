<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Api;

use Magento\TwoFactorAuth\Api\Data\DuoDataInterface;

/**
 * Represents authentication for the duo security provider
 *
 * @api
 */
interface DuoAuthenticateInterface
{
    /**
     * Get the information required to configure duo
     *
     * @deprecated this method is deprecated and will be removed in a future release.
     * @see none
     * @param string $username
     * @param string $password
     * @return \Magento\TwoFactorAuth\Api\Data\DuoDataInterface
     */
    public function getAuthenticateData(
        string $username,
        string $password
    ): DuoDataInterface;

    /**
     * Authenticate and get an admin token
     *
     * @deprecated this method is deprecated and will be removed in a future release.
     * @see createAdminAccessTokenWithCredentialsAndPasscode
     *
     * @param string $username
     * @param string $password
     * @param string $signatureResponse
     * @return string
     */
    public function createAdminAccessTokenWithCredentials(
        string $username,
        string $password,
        string $signatureResponse
    ): string;

    /**
     * Authenticate and get an admin token with passcode
     *
     * @param string $username
     * @param string $password
     * @param string $passcode
     * @return string
     */
    public function createAdminAccessTokenWithCredentialsAndPasscode(
        string $username,
        string $password,
        string $passcode
    ): string;
}
