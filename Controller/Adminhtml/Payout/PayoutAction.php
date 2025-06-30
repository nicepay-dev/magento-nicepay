<?php

namespace Nicepay\NicePayment\Controller\Adminhtml\Payout;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Nicepay\NicePayment\Logger\Logger as NiceLogger;
use Psr\Log\LoggerInterface;



abstract class PayoutAction extends Action
{
    const ADMIN_RESOURCE = 'Nicepay_NicePayment::payout';

    protected $orderRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        NiceLogger $logger,
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Nicepay_NicePayment::payout');
    }



    /**
     * Load order by ID from request param 'id'
     * @return \Magento\Sales\Api\Data\OrderInterface|null
     */
    protected function getOrder()
    {
        $id = $this->getRequest()->getParam('id');
        if (!$id) {
            return null;
        }
        try {
            return $this->orderRepository->get($id);
        } catch (\Exception $e) {
            return null;
        }
    }


    protected function getLogger()
    {
        return $this->logger;
    }
}
