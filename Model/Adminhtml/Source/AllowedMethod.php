<?php

namespace Nicepay\NicePayment\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;


class AllowedMethod implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'all', 'label' => __('All available payment method on Nicepay')],
            ['value' => 'specific', 'label' => __('Specific payment method on Nicepay')]
        ];
    }
}
