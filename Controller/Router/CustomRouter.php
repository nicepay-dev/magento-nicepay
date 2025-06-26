<?php

namespace Nicepay\NicePayment\Controller\Router;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;
use Magento\Framework\App\ResponseInterface;

class CustomRouter implements RouterInterface
{
    /**
     * @var ActionFactory
     */
    protected $actionFactory;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @param ActionFactory $actionFactory
     * @param ResponseInterface $response
     */
    public function __construct(
        ActionFactory $actionFactory,
        ResponseInterface $response
    ) {
        $this->actionFactory = $actionFactory;
        $this->response = $response;
    }

    /**
     * Validate and Match
     *
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ActionInterface|null
     */
    public function match(RequestInterface $request)
    {
        $identifier = trim($request->getPathInfo(), '/');

        if (preg_match('%^notification/api/v1\.0/debit/notify(/.*)?$%i', $identifier, $matches)) {
            $request->setModuleName('nicepay')
                ->setControllerName('nicepayment')
                ->setActionName('notifyewallet');

            if (isset($matches[1])) {
                $request->setParam('additional_path', trim($matches[1], '/'));
            }

            return $this->actionFactory->create(
                \Magento\Framework\App\Action\Forward::class,
                ['request' => $request]
            );
        }

        // NOTIF VA

        if (preg_match('%^notification/api/v1\.0/transfer-va/payment(/.*)?$%i', $identifier, $matches)) {
            $request->setModuleName('nicepay')
                ->setControllerName('nicepayment')
                ->setActionName('notifyva');

            // Optionally capture additional path segments if needed
            if (isset($matches[1])) {
                $request->setParam('additional_path', trim($matches[1], '/'));
            }

            return $this->actionFactory->create(
                \Magento\Framework\App\Action\Forward::class,
                ['request' => $request]
            );
        }

        // NOTIF QRIS 
        if (preg_match('%^notification/api/v1\.0/qr/qr-mpm-notify(/.*)?$%i', $identifier, $matches)) {
            $request->setModuleName('nicepay')
                ->setControllerName('nicepayment')
                ->setActionName('notifyqris');

            // Optionally capture additional path segments if needed
            if (isset($matches[1])) {
                $request->setParam('additional_path', trim($matches[1], '/'));
            }

            return $this->actionFactory->create(
                \Magento\Framework\App\Action\Forward::class,
                ['request' => $request]
            );
        }

        // NOTIF PAYOUT
        if (preg_match('%^notification/api/v1\.0/debit/notify(/.*)?$%i', $identifier, $matches)) {
            $request->setModuleName('nicepay')
                ->setControllerName('nicepayment')
                ->setActionName('notifypayout');

            if (isset($matches[1])) {
                $request->setParam('additional_path', trim($matches[1], '/'));
            }

            return $this->actionFactory->create(
                \Magento\Framework\App\Action\Forward::class,
                ['request' => $request]
            );
        }

        return null;
    }
}
