<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaUi\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\InputException;

/**
 * @inheritdoc
 */
class CaptchaResponseResolver implements CaptchaResponseResolverInterface
{
    /**
     * @inheritdoc
     */
    public function resolve(RequestInterface $request): string
    {
        $reCaptchaParam = $request->getParam(self::PARAM_RECAPTCHA);
        if (!$reCaptchaParam) {
            throw new InputException(__('Can not resolve reCAPTCHA parameter.'));
        }

        return $reCaptchaParam;
    }
}
