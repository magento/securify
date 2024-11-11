<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Api;

/**
 * Represents authentication for the duo security provider
 *
 * @api
 */
interface DuoAuthenticateInterface
{
    /**
     * Authenticate and get an admin token
     *
     * @param string $username
     * @param string $password
     * @param string $passcode
     * @return string
     */
    public function createAdminAccessTokenWithCredentials(
        string $username,
        string $password,
        string $passcode
    ): string;
}
