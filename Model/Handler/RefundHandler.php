<?php

namespace Nicepay\NicePayment\Model\Handler;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\Method\AbstractMethod;


class RefundHandler extends AbstractMethod implements HandlerInterface
{

    public function handle(array $handlingSubject, array $response)
    {
        return $this;
    }
}
