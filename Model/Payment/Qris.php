<?php

namespace Nicepay\NicePayment\Model\Payment;



class Qris extends NicepayAbstractMethod
{

    protected $_isInitializeNeeded = true;
    protected $_code = 'qris';
    protected $methodCode = 'QRIS';
}
