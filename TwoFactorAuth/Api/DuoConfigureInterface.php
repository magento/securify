<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Api;

use Magento\TwoFactorAuth\Api\Data\DuoDataInterface;

/**
 * Represents configuration for the duo security provider
 *
 * @api
 */
interface DuoConfigureInterface
{
    /**
     * Get the information required to configure duo
     *
     * @param string $tfaToken
     * @return \Magento\TwoFactorAuth\Api\Data\DuoDataInterface
     */
    public function getConfigurationData(
        string $tfaToken
    ): DuoDataInterface;

    /**
     * Activate the provider and get an admin token
     *
     * @param string $tfaToken
     * @param string $userIdentifier
     * @param string $passcode
     * @return void
     */
    public function activate(string $tfaToken, string $userIdentifier, string $passcode): void;
}
