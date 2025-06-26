<?php

namespace Nicepay\NicePayment\Controller\Nicepayment;


use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\Order;
use Nicepay\NicePayment\Library\NicepayLib;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Context;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Sales\Model\OrderFactory;
use Nicepay\NicePayment\Logger\Logger as NiceLogger;
use Nicepay\NicePayment\Helper\Data;
use Nicepay\NicePayment\Helper\Checkout;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Registry;

class Success extends AbstractAction
{
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    public function __construct(
        CheckoutSession $checkoutSession,
        Context $context,
        CategoryFactory $categoryFactory,
        OrderFactory $orderFactory,
        NiceLogger $logger,
        Data $dataHelper,
        Checkout $checkoutHelper,
        OrderRepositoryInterface $orderRepo,
        StoreManagerInterface $storeManager,
        CartRepositoryInterface $quoteRepository,
        JsonFactory $jsonResultFactory,
        CookieManagerInterface $cookieManager,
        InvoiceService $invoiceService,
        Transaction $dbTransaction,
        CustomerSession $customerSession,
        ProductRepositoryInterface $productRepository,
        NicepayLib $nicepayLib,
        Registry $registry
    ) {

        parent::__construct(
            $checkoutSession,
            $context,
            $categoryFactory,
            $orderFactory,
            $logger,
            $dataHelper,
            $checkoutHelper,
            $orderRepo,
            $storeManager,
            $quoteRepository,
            $jsonResultFactory,
            $cookieManager,
            $invoiceService,
            $dbTransaction,
            $customerSession,
            $productRepository
        );
    }


    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {

        $this->getLogger()->info('Registration complete execute start ');

        $data = $this->getRequest()->getParams();

        if (empty($data)) {
            return $this->_redirect('checkout/cart');
        }



        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $resultPage->getConfig()->getTitle()->set(__('Payment Registration Completed'));

        return $resultPage;
    }
}
