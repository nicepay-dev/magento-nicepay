<?php

namespace Nicepay\NicePayment\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;


class RefundClient implements ClientInterface
{

    public function placeRequest(TransferInterface $transferObject)
    {
        $response = ['IGNORED' => ['IGNORED']];
        return $response;
    }
}
