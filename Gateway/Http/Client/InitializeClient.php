<?php

namespace Nicepay\NicePayment\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;


class InitializeClient implements ClientInterface
{
    /**
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $response = ['IGNORED' => ['IGNORED']];
        return $response;
    }
}
