<?php

namespace Nicepay\NicePayment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Nicepay\NicePayment\Model\Ui\ConfigProvider;

class PayloanMitraList implements OptionSourceInterface
{
    protected $helper;

    public function __construct(ConfigProvider $configProvider)
    {
        $this->helper = $configProvider;
    }

    public function toOptionArray()
    {
        $banks = $this->helper->payloanMitraList();
        $options = [];
        foreach ($banks as $code => $data) {
            $options[] = ['value' => $code, 'label' => $data['label']];
        }
        return $options;
    }
}
