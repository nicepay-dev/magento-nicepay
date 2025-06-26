<?php

namespace Nicepay\NicePayment\Gateway\Request;

use Magento\Checkout\Model\Session;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Data\Order\OrderAdapter;
use Magento\Sales\Model\Order;
use Nicepay\NicePayment\Gateway\Config\Config;


class InitializationRequest implements BuilderInterface
{
    /**
     * @var Config
     */
    private $gatewayConfig;



    /**
     * @var Session
     */
    private $session;


    public function __construct(
        Config $gatewayConfig,
        Session $session
    ) {
        $this->gatewayConfig = $gatewayConfig;
        $this->session = $session;
    }

    /**
     * @param OrderAdapter $order
     * @return bool
     */
    private function validateQuote(OrderAdapter $order)
    {

        return true;
    }

    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $payment = (isset($buildSubject['payment'])) ? $buildSubject['payment'] : '';
        $stateObject = (isset($buildSubject['stateObject'])) ? $buildSubject['stateObject'] : '';
        $order = ($payment) ? $payment->getOrder() : '';

        if ($order) {
            if ($this->validateQuote($order)) {
                $stateObject->setState(Order::STATE_PENDING_PAYMENT);
                $stateObject->setStatus('pending');
                $stateObject->setIsNotified(false);
            } else {
                $stateObject->setState(Order::STATE_CANCELED);
                $stateObject->setStatus(Order::STATE_CANCELED);
                $stateObject->setIsNotified(false);
            }
        }

        return ['IGNORED' => ['IGNORED']];
    }
}
