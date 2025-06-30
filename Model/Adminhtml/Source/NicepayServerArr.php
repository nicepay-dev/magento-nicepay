<?php

namespace Nicepay\NicePayment\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

class EnvRadioBtn implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'old',
                'label' => __('Old Server')
            ],
            [
                'value' => 'cloud',
                'label' => __('Cloud Server')
            ],
        ];
    }
}
