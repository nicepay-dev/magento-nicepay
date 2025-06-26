<?php

namespace Nicepay\NicePayment\Block;

use Magento\Payment\Block\ConfigurableInfo;


class Info extends ConfigurableInfo
{
    /**
     * @param string $field
     * @return \Magento\Framework\Phrase|string
     */
    protected function getLabel($field)
    {
        return $field;
    }
}
