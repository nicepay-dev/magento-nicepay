<?php

namespace Nicepay\NicePayment\Model\Payment;

class Payout extends NicepayAbstractMethod
{
    protected $_isInitializeNeeded = true;
    protected $_code = 'payout';
    protected $methodCode = 'PAYOUT';
}
