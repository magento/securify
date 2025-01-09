<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
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
     * @deprecated this method is deprecated and will be removed in a future release.
     * @see getDuoConfigurationData
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
     * @deprecated this method is deprecated and will be removed in a future release.
     * @see duoActivate
     * @param string $tfaToken
     * @param string $signatureResponse
     * @return void
     */
    public function activate(string $tfaToken, string $signatureResponse): void;

    /**
     * Configure duo for first time user
     *
     * @param string $tfaToken
     * @return void
     */
    public function getDuoConfigurationData(
        string $tfaToken
    );

    /**
     * Activate the provider and get an admin token
     *
     * @param string $tfaToken
     * @return void
     */
    public function duoActivate(string $tfaToken): void;
}
