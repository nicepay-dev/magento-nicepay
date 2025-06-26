<?php

namespace Nicepay\NicePayment\Model\Payment;



class Card extends NicepayAbstractMethod
{

    protected $_isInitializeNeeded = true;
    protected $_code = 'card';
    protected $methodCode = 'CARD';
}
