<?php

namespace Nicepay\NicePayment\Controller\Nicepayment;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DB\Transaction as DbTransaction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Category;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Nicepay\NicePayment\Helper\Checkout;
use Nicepay\NicePayment\Helper\Data;
use Nicepay\NicePayment\Logger\Logger as NiceLogger;
use Magento\Catalog\Api\ProductRepositoryInterface;

abstract class AbstractAction extends Action
{

    protected $securityHelper;
    private $checkoutSession;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var CategoryFactory
     */
    private $categoryFactory;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Data
     */
    private $dataHelper;



    /**
     * @var Checkout
     */
    private $checkoutHelper;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepo;


    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var JsonFactory
     */
    private $jsonResultFactory;

    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var InvoiceService
     */
    private $invoiceService;

    /**
     * @var DbTransaction
     */
    private $dbTransaction;

    /**
     * @var CustomerSession
     */
    private $customerSession;


    protected $productRepository;



    public function __construct(
        Session $checkoutSession,
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
        DbTransaction $dbTransaction,
        CustomerSession $customerSession,
        ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($context);

        $this->checkoutSession = $checkoutSession;
        $this->context = $context;
        $this->categoryFactory = $categoryFactory;
        $this->orderFactory = $orderFactory;
        $this->logger = $logger;
        $this->dataHelper = $dataHelper;
        $this->checkoutHelper = $checkoutHelper;
        $this->messageManager = $context->getMessageManager();
        $this->orderRepo = $orderRepo;
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        $this->storeManager = $storeManager;
        $this->quoteRepository = $quoteRepository;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->cookieManager = $cookieManager;
        $this->invoiceService = $invoiceService;
        $this->dbTransaction = $dbTransaction;
        $this->customerSession = $customerSession;
        $this->productRepository = $productRepository;
    }

    /**
     * @return Context
     */
    protected function getContext()
    {
        return $this->context;
    }

    /**
     * @return Session
     */
    protected function getCheckoutSession()
    {
        return $this->checkoutSession;
    }


    protected function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return InvoiceService
     */
    protected function getInvoiceService()
    {
        return $this->invoiceService;
    }

    /**
     * @return DbTransaction
     */
    protected function getDbTransaction()
    {
        return $this->dbTransaction;
    }

    /**
     * @return CustomerSession
     */
    protected function getCustomerSession()
    {
        return $this->customerSession;
    }

    /**
     * @return Order|null
     */
    protected function getOrder()
    {
        $orderId = $this->checkoutSession->getLastRealOrderId();

        if (!isset($orderId)) {
            return null;
        }

        return $this->getOrderById($orderId);
    }

    /**
     * @param $categoryId
     * @return Category|null
     */
    protected function getCategoryById($categoryId)
    {
        $category = $this->categoryFactory->create()->loadByIncrementId($categoryId);

        if (!$category->getId()) {
            return null;
        }

        return $category;
    }

    /**
     * @param $orderId
     * @return Order|null
     */
    protected function getOrderById($orderId)
    {
        $order = $this->orderFactory->create()->loadByIncrementId($orderId);

        if (!$order->getId()) {
            return null;
        }

        return $order;
    }

    /**
     * @param $orderId
     * @return Order|null
     */
    protected function getOrderByEntityId($entityId)
    {
        $order = $this->orderFactory->create()->load($entityId);

        if (!$order->getId()) {
            return null;
        }

        return $order;
    }

    /**
     * @return Data
     */
    protected function getDataHelper()
    {
        return $this->dataHelper;
    }




    /**
     * @return Checkout
     */
    protected function getCheckoutHelper()
    {
        return $this->checkoutHelper;
    }

    /**
     * @return \Magento\Framework\Message\ManagerInterface
     */
    protected function getMessageManager()
    {
        return $this->messageManager;
    }

    /**
     * @return OrderRepositoryInterface
     */
    protected function getOrderRepo()
    {
        return $this->orderRepo;
    }



    /**
     * @return CartRepositoryInterface
     */
    protected function getQuoteRepository()
    {
        return $this->quoteRepository;
    }

    /**
     * @return JsonFactory
     */
    protected function getJsonResultFactory()
    {
        return $this->jsonResultFactory;
    }

    /**
     * @param $order
     * @param $transactionId
     * @throws LocalizedException
     */
    protected function invoiceOrder($order, $transactionId)
    {
        if (!$order->canInvoice()) {
            throw new LocalizedException(
                __('Cannot create an invoice.')
            );
        }

        $invoice = $this->getInvoiceService()->prepareInvoice($order);

        if (!$invoice->getTotalQty()) {
            throw new LocalizedException(
                __('You can\'t create an invoice without products.')
            );
        }

        /*
         * Look Magento/Sales/Model/Order/Invoice.register() for CAPTURE_OFFLINE explanation.
         * Basically, if !config/can_capture and config/is_gateway and CAPTURE_OFFLINE and
         * Payment.IsTransactionPending => pay (Invoice.STATE = STATE_PAID...)
         */
        $invoice->setTransactionId($transactionId);
        $invoice->setRequestedCaptureCase(Order\Invoice::CAPTURE_OFFLINE);
        $invoice->register();
        $transaction = $this->getDbTransaction()->addObject($invoice)->addObject($invoice->getOrder());
        $transaction->save();
    }

    /**
     * @return \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected function getRedirectFactory()
    {
        return $this->resultRedirectFactory;
    }

    /**
     * @param Order $order
     * @param $failureReason
     * @return Order
     * @throws LocalizedException
     */
    protected function cancelOrder(Order $order, $failureReason)
    {
        $orderState = Order::STATE_CANCELED;
        if ($order->getStatus() != $orderState) {
            try {
                $message = "Order #" . $order->getIncrementId() . " was cancelled by Nicepay because " . $failureReason;
                $order->setState($orderState)
                    ->setStatus($orderState)
                    ->addStatusHistoryComment($message);
                $this->orderRepo->save($order);

                $this->getCheckoutHelper()->cancelOrderById($order->getId(), "Order #" . ($order->getId()) . " was rejected by Nicepay");
                $this->getCheckoutHelper()->restoreQuote(); //restore cart

                $this->logger->info($message);
            } catch (\Exception $ex) {
                $this->logger->error('Cancel order failed:' . $ex->getMessage(), ['order_id' => $order->getIncrementId()]);
                throw new LocalizedException(
                    new Phrase($ex->getMessage())
                );
            }
        }
        return $order;
    }

    /**
     * @return StoreManagerInterface
     */
    protected function getStoreManager()
    {
        return $this->storeManager;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getCustomStoreUrl($endpoint)
    {
        $baseUrl = $this->getStoreManager()->getStore()->getBaseUrl(UrlInterface::URL_TYPE_LINK);

        return $baseUrl . $endpoint;
    }


    /**
     * @param Order $order
     * @return bool
     */
    protected function orderValidToCreateNicepayInvoice(Order $order): bool
    {
        if (empty($order->getNicepayTxid())) {
            return true;
        }
        return false;
    }

    /**
     * Get preferred payment from order
     *
     * @param Order $order
     * @return false|string
     */
    protected function getPreferredMethod(Order $order)
    {
        $payment = $order->getPayment();
        return $this->getDataHelper()->nicepayPaymentMethod(
            $payment->getMethod()
        );
    }

    /**
     * @param string $orderByIncrementId
     * @return Order
     */
    public function getOrderByIncrementId(string $orderByIncrementId): Order
    {
        $order = $this->orderFactory->create();
        return $order->loadByIncrementId($orderByIncrementId);
    }
}
