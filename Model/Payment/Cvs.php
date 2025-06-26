<?php

namespace Nicepay\NicePayment\Model\Payment;



class Cvs extends NicepayAbstractMethod
{

    protected $_isInitializeNeeded = true;
    protected $_code = 'cvs';
    protected $methodCode = 'CVS';
}
