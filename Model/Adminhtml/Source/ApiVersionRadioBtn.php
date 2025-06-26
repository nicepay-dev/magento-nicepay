<?php

namespace Nicepay\NicePayment\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;


class ApiVersionRadioBtn implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'v2',
                'label' => __('NICEPAY API Version 2 (Direct)')
            ],
            [
                'value' => 'snap',
                'label' => __('NICEPAY API SNAP VERSION')
            ],
        ];
    }
}
