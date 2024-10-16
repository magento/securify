<?php

namespace Magento\TwoFactorAuth\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class DuoFailmode implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'closed', 'label' => __('Closed')],
            ['value' => 'open', 'label' => __('Open')]
        ];
    }

}
