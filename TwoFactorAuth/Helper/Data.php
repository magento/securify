<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */

namespace Magento\TwoFactorAuth\Helper;

use Magento\Framework\Data\Form\FormKey;

class Data
{
    /**
     * @var FormKey
     */
    private $formKey;

    /**
     * @param FormKey $formKey
     */
    public function __construct(FormKey $formKey)
    {
        $this->formKey = $formKey;
    }

    /**
     * Get form key
     *
     * @return string
     */
    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }
}
