<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TwoFactorAuth\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Represents the data needed to use duo
 *
 * @api
 */
interface DuoDataInterface extends ExtensibleDataInterface
{
    /**
     * Retrieve existing extension attributes object or create a new one
     *
     * Used fully qualified namespaces in annotations for proper work of extension interface/class code generation
     *
     * @return \Magento\TwoFactorAuth\Api\Data\DuoDataExtensionInterface|null
     */
    public function getExtensionAttributes(): ?DuoDataExtensionInterface;

    /**
     * Set an extension attributes object
     *
     * @param \Magento\TwoFactorAuth\Api\Data\DuoDataExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(
        DuoDataExtensionInterface $extensionAttributes
    ): void;
}
