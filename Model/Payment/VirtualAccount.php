<?php

namespace Nicepay\NicePayment\Model\Payment;

class VirtualAccount extends NicepayAbstractMethod
{
    protected $_isInitializeNeeded = true;
    protected $_code = 'virtual_account';
    protected $methodCode = 'VA';
}
