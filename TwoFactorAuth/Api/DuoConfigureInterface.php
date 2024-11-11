<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Api;

/**
 * Represents configuration for the duo security provider
 *
 * @api
 */
interface DuoConfigureInterface
{
    /**
     * configure duo for first time user
     *
     * @param string $tfaToken
     * @return void
     */
    public function getConfigurationData(
        string $tfaToken
    );

    /**
     * Activate the provider and get an admin token
     *
     * @param string $tfaToken
     * @return void
     */
    public function activate(string $tfaToken): void;
}
