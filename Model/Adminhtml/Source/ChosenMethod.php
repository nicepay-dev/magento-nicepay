<?php

namespace Nicepay\NicePayment\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;


class ChosenMethod implements ArrayInterface
{
    /**
     * Return options array
     *
     * @param boolean $isMultiselect
     * @return array
     */
    public function toOptionArray($isMultiselect = false)
    {
        $options = [

            ['value' => 'qris', 'label' => __('QRIS')],
            ['value' => 'virtual_account', 'label' => __('VIRTUAL ACCOUNT')],
            ['value' => 'ewallet', 'label' => __('EWALLET')]

        ];

        if (!$isMultiselect) {
            array_unshift($options, ['value' => '', 'label' => __('--Please Select--')]);
        }
        return $options;
    }
}
