<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaValidationApi\Api;

use Magento\Framework\Validation\ValidationResult;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigInterface;

/**
 * Validate reCAPTCHA response
 *
 * @api
 */
interface ValidatorInterface
{
    /**
     * Return true if reCAPTCHA validation has passed
     *
     * @param string $reCaptchaResponse
     * @param ValidationConfigInterface $validationConfig
     * @return ValidationResult
     */
    public function isValid(string $reCaptchaResponse, ValidationConfigInterface $validationConfig): ValidationResult;
}
