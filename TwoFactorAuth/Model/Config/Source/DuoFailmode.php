<?php

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
