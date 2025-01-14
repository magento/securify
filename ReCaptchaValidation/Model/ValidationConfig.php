<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaValidation\Model;

use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigExtensionInterface;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigInterface;

/**
 * @inheritDoc
 */
class ValidationConfig implements ValidationConfigInterface
{
    /**
     * @var string
     */
    private $privateKey;

    /**
     * @var string
     */
    private $remoteIp;

    /**
     * @var string
     */
    private $validationFailureMessage;

    /**
     * @var ValidationConfigExtensionInterface|null
     */
    private $extensionAttributes;

    /**
     * @param string $privateKey
     * @param string $remoteIp
     * @param string $validationFailureMessage
     * @param ValidationConfigExtensionInterface|null $extensionAttributes
     */
    public function __construct(
        string $privateKey,
        string $remoteIp,
        string $validationFailureMessage,
        ?ValidationConfigExtensionInterface $extensionAttributes = null
    ) {
        $this->privateKey = $privateKey;
        $this->remoteIp = $remoteIp;
        $this->validationFailureMessage = $validationFailureMessage;
        $this->extensionAttributes = $extensionAttributes;
    }

    /**
     * @inheritdoc
     */
    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    /**
     * @inheritdoc
     */
    public function getRemoteIp(): string
    {
        return $this->remoteIp;
    }

    /**
     * @inheritdoc
     */
    public function getValidationFailureMessage(): string
    {
        return $this->validationFailureMessage;
    }

    /**
     * @inheritdoc
     */
    public function getExtensionAttributes(): ?ValidationConfigExtensionInterface
    {
        return $this->extensionAttributes;
    }
}
