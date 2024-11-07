<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Model\Data\Provider\Engine\DuoSecurity;

use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\TwoFactorAuth\Api\Data\DuoDataExtensionInterface;
use Magento\TwoFactorAuth\Api\Data\DuoDataInterface;

/**
 * Represents the data needed to authenticate with duo
 */
class Data extends AbstractExtensibleModel implements DuoDataInterface
{
    /**
     * Retrieve existing extension attributes object or create a new one
     *
     * Used fully qualified namespaces in annotations for proper work of extension interface/class code generation
     *
     * @return \Magento\TwoFactorAuth\Api\Data\DuoDataExtensionInterface|null
     */
    public function getExtensionAttributes(): ?DuoDataExtensionInterface
    {
        return $this->getData(self::EXTENSION_ATTRIBUTES_KEY);
    }

    /**
     * Set an extension attributes object
     *
     * @param \Magento\TwoFactorAuth\Api\Data\DuoDataExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(DuoDataExtensionInterface $extensionAttributes): void
    {
        $this->setData(self::EXTENSION_ATTRIBUTES_KEY, $extensionAttributes);
    }
}
