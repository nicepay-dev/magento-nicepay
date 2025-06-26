<?php

namespace Nicepay\NicePayment\Controller\Adminhtml\Payout;

use Magento\Framework\View\Result\PageFactory;
use Nicepay\NicePayment\Controller\Adminhtml\Payout\PayoutAction;

class Index extends PayoutAction
{
    protected $resultPageFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        PageFactory $resultPageFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Nicepay\NicePayment\Logger\Logger $logger
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context, $orderRepository, $logger);
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Nicepay_NicePayment::menu');
        $resultPage->getConfig()->getTitle()->prepend(__('Nicepay Payout Transactions'));


        return $resultPage;
    }
}
