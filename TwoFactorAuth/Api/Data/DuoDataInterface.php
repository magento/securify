<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\TwoFactorAuth\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Represents the data needed to use duo
 *
 * @deprecated This interface is no longer used.
 * @see none
 * @api
 */
interface DuoDataInterface extends ExtensibleDataInterface
{
    /**
     * Signature field name
     *
     * @deprecated
     * @see none
     */
    public const SIGNATURE = 'signature';

    /**
     * Api host field name
     *
     * @deprecated
     * @see none
     */
    public const API_HOSTNAME = 'api_hostname';

    /**
     * Get the signature
     *
     * @deprecated
     * @see none
     * @return string
     */
    public function getSignature(): string;

    /**
     * Set the signature
     *
     * @deprecated
     * @see none
     * @param string $value
     * @return void
     */
    public function setSignature(string $value): void;

    /**
     * Set the api hostname
     *
     * @deprecated
     * @see none
     * @param string $value
     * @return void
     */
    public function setApiHostname(string $value): void;

    /**
     * Get the api hostname
     *
     * @deprecated
     * @see none
     * @return string
     */
    public function getApiHostname(): string;

    /**
     * Retrieve existing extension attributes object or create a new one
     *
     * Used fully qualified namespaces in annotations for proper work of extension interface/class code generation
     *
     * @deprecated
     * @see none
     * @return \Magento\TwoFactorAuth\Api\Data\DuoDataExtensionInterface|null
     */
    public function getExtensionAttributes(): ?DuoDataExtensionInterface;

    /**
     * Set an extension attributes object
     *
     * @deprecated
     * @see none
     * @param \Magento\TwoFactorAuth\Api\Data\DuoDataExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(
        DuoDataExtensionInterface $extensionAttributes
    ): void;
}
