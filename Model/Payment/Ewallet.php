<?php

namespace Nicepay\NicePayment\Model\Payment;

class Ewallet extends NicepayAbstractMethod
{
    protected $_isInitializeNeeded = true;
    protected $_code = 'ewallet';
    protected $methodCode = 'Ewallet';
}
