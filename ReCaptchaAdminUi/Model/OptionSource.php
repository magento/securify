<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaAdminUi\Model;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Generic class for reCAPTCHA Stores/System Configuration options
 * Should not use directly, only as base class for "virtual type" in DI configuration
 *
 * Extension point for adding reCAPTCHA options
 *
 * @api
 */
class OptionSource implements OptionSourceInterface
{
    /**
     * @var array
     */
    private $options;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = array_values($options);
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return $this->options;
    }
}
