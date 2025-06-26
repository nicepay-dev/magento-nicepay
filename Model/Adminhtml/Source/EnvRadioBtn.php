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
                'value' => 'dev',
                'label' => __('Sandbox')
            ],
            [
                'value' => 'prod',
                'label' => __('Live')
            ],
        ];
    }
}
