<?php

namespace Nicepay\NicePayment\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

class QrisMitraCdRadioBtn implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'QSHP',
                'label' => __('Qris Shopee')
            ],
            [
                'value' => 'NOBU',
                'label' => __('Qris Nobu')
            ],
        ];
    }
}
