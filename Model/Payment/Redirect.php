<?php

namespace Nicepay\NicePayment\Model\Payment;



class Redirect extends NicepayAbstractMethod
{

    protected $_isInitializeNeeded = true;
    protected $_code = 'redirect';
    protected $methodCode = 'REDIRECT';
}
