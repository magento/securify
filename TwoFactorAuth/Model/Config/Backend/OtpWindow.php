<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\TwoFactorAuth\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\App\Config\Data\ProcessorInterface;
use Magento\Framework\Exception\ValidatorException;
use OTPHP\TOTPInterface;

class OtpWindow extends Value implements ProcessorInterface
{
    /**
     * Fetch Totp default period value
     *
     * @return int
     */
    private function getDefaultPeriod(): int
    {
        return TOTPInterface::DEFAULT_PERIOD;
    }

    /**
     * Process the value before saving.
     *
     * @param mixed $value The configuration value.
     * @return mixed The processed value.
     * @throws ValidatorException If the value is invalid.
     */
    public function processValue($value)
    {
        if (!is_numeric($value)) {
            throw new ValidatorException(__('The OTP window must be a numeric value.'));
        }
        $numericValue = (int) $value;
        return $numericValue;
    }

    /**
     * Validates the value before saving.
     *
     * @throws ValidatorException If the value is invalid.
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        $period = $this->getDefaultPeriod();
        if (!is_numeric($value) || $value < 1 || $value >= $period) {
            throw new ValidatorException(
                __(
                    'Invalid OTP Window value. It must be between 1 and %1 as default OTP period value is %2',
                    $period-1,
                    $period
                )
            );
        }

        return parent::beforeSave();
    }
}
