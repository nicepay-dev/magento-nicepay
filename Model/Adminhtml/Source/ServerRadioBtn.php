<?php

namespace Nicepay\NicePayment\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

class ServerRadioBtn implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'old',
                'label' => __('Old')
            ],
            [
                'value' => 'cloud',
                'label' => __('Cloud')
            ],
        ];
    }
}
