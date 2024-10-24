<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
     * @param string $username
     * @param string $password
     * @param string $userIdentifier
     * @param string $passcode
     * @return string
     */
    public function createAdminAccessTokenWithCredentials(
        string $username,
        string $password,
        string $userIdentifier,
        string $passcode
    ): string;
}
