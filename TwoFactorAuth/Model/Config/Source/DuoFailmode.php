<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\TwoFactorAuth\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class DuoFailmode implements OptionSourceInterface
{

    /**
     * Get options
     *
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'closed', 'label' => __('Closed')],
            ['value' => 'open', 'label' => __('Open')]
        ];
    }
}
