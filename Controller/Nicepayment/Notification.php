<?php

namespace Nicepay\NicePayment\Controller\Nicepayment;

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
use Magento\TestFramework\Catalog\Model\Product\Option\DataProvider\Type\Date;
use Nicepay\NicePayment\Helper\Security;
use Nicepay\NicePayment\Helper\DateHelper;
use Magento\Sales\Model\Order;




class Notification extends AbstractNotification
{
    protected $nicepayLib;
    protected $registry;
    protected $jsonResultFactory;
    protected $logPrefix;

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
        Registry $registry,
        Security $security
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
            $productRepository,
            $security
        );

        $this->registry = $registry;

        $this->nicepayLib = $nicepayLib;

        $this->jsonResultFactory = $jsonResultFactory;

        $this->logPrefix = uniqid() . ' : NicePayment Notification - ';
    }

    /**
     * Notification API for Non SNAP version
     *
     */
    public function execute()
    {
        $niceLogger = $this->getLogger();
        $niceLogger->info($this->logPrefix . 'Notification execute start ');

        $result = $this->jsonResultFactory->create();
        $responseData = array();
        $responseCd = "400";

        $nicepay = $this->nicepayLib;

        try {
            // From request param
            $niceLogger->debug($this->logPrefix . 'Incoming request', ['requestData' => $this->getRequest()->getParams()]);

            $payMethod          = $this->getRequest()->getParam('payMethod');
            $tXid               = $this->getRequest()->getParam('tXid');
            $referenceNo        = $this->getRequest()->getParam('referenceNo');
            $amt                = $this->getRequest()->getParam('amt');
            $pushedToken        = $this->getRequest()->getParam('merchantToken');

            $timeStamp          = DateHelper::getFormattedTimestampV2();

            // Get Order Information
            $order_information = $this->getOrderById($referenceNo);
            $payMethod = $order_information->getNicepayPaymentMethod() ?? $payMethod;

            // From Config
            $iMid               = $nicepay->getIMidConfig($payMethod);
            $merchantKey        = $nicepay->getMerchantKey($payMethod);
            $niceLogger->debug($this->logPrefix . 'Merchant Configuration', ['iMid' => $iMid, 'merchantKey' => $merchantKey]);


            $nicepay->set('payMethod', $payMethod);
            $nicepay->set('tXid', $tXid);
            $nicepay->set('referenceNo', $referenceNo);
            $nicepay->set('amt', $amt);
            $nicepay->set('iMid', $iMid);
            $nicepay->set('merchantKey', $merchantKey);
            $nicepay->set('timeStamp', $timeStamp);


            // For check merchantToken notification received
            $merchantToken = $nicepay->merchantTokenNotification();

            // For check status
            $mertokCheckStatus = $nicepay->merchantTokenV2();


            $nicepay->set('merchantToken', $mertokCheckStatus);


            if ($pushedToken == $merchantToken) {

                $niceLogger->info($this->logPrefix . 'Incoming merchant token is valid ', ['incomingMerTok' => $merchantToken, 'checkedMerTok' => $pushedToken]);




                $paymentStatus = $nicepay->nicepayInquiryStatus();


                if (!$order_information) {
                    $niceLogger->warning($this->logPrefix . 'Order not found', ['order_id' => $referenceNo]);
                    $responseCd = '404';
                    $responseData = [
                        'resultCd' => $responseCd,
                        'resultMsg' => 'Order transaction not found',
                    ];
                } else if ($order_information->getNicepayPaymentStatus() == '00' || $order_information->getNicepayPaymentStatus() == '0') {
                    $niceLogger->warning($this->logPrefix . 'Order transaction is already paid', ['order_id' => $referenceNo]);

                    $responseCd = '400';
                    $responseData = [
                        'resultCd' => $responseCd,
                        'resultMsg' => 'Order transaction is already paid',
                    ];
                } else if ($paymentStatus['status_code'] == '0000' && isset($paymentStatus['status_trx']) && ($paymentStatus['status_trx'] == '0' || $paymentStatus['status_trx'] == '00')) {
                    // Check Status Success + Payment status matched 
                    $niceLogger->info($this->logPrefix . 'Status Matched Payment completed. Updating order status', ['order_id' => $order_information->getIncrementId()]);


                    $order_information->setState(Order::STATE_PROCESSING)->setStatus('processing');
                    $order_information->addStatusToHistory('processing', 'Order was set to processing by NICEPay Magento Payment. tXid ' . $tXid);
                    $order_information->setNicepayPaymentStatus($paymentStatus['status_trx']);
                    $this->getOrderRepo()->save($order_information);
                    $responseCd = '200';
                    $responseData = [
                        'resultCd' => $responseCd,
                        'resultMsg' => 'Notification received. Status Matched',
                    ];

                    // $this->sentUpdateOrderEmail($order);
                } else if ($paymentStatus['status_code'] == '0000' && isset($paymentStatus['status_trx'])) {

                    $niceLogger->info($this->logPrefix . 'Status Not Paid. Update to latest inquired transaction status ' . $paymentStatus['status_trx']);
                    $order_information->addCommentToStatusHistory($order_information->getStatus(), 'Nicepay status is ' . $paymentStatus['status_trx'] . ' by NICEPay Magento Payment. tXid ' . $tXid);
                    $order_information->setNicepayPaymentStatus($paymentStatus['status_trx']);

                    $this->getOrderRepo()->save($order_information);
                    $responseCd = '400';
                    $responseData = [
                        'resultCd' => $responseCd,
                        'resultMsg' => 'Notification received. Status Not Matched',
                    ];
                } else {
                    // Check Status Failed
                    $niceLogger->info($this->logPrefix . 'Failed Inquiring latest transaction status to Nicepay', ['paymentStatus' => $paymentStatus]);
                    $responseCd = '400';
                    $responseData = [
                        'resultCd' => $responseCd,
                        'resultMsg' => 'Failed Inquiring latest status',
                    ];
                }
            } else {
                $niceLogger->info($this->logPrefix . 'Incoming merchant token is invalid ', ['incomingMerTok' => $merchantToken, 'checkedMerTok' => $pushedToken]);
                $responseCd = '401';
                $responseData = [
                    'resultCd' => $responseCd,
                    'resultMsg' => 'Unauthorized merchant token',
                ];
            }
        } catch (\Exception $e) {
            $niceLogger->info($this->logPrefix . 'Exception thrown: ' . $e->getMessage());
            $responseCd = $e->getCode();
            $responseData = [
                'resultCd' => $e->getCode(),
                'resultMsg' => $e->getMessage(),
            ];
        }

        $result->setData($responseData);
        $result->setHttpResponseCode($responseCd);
        return $result;
    }
}
