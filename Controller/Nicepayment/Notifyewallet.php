<?php

namespace Nicepay\NicePayment\Controller\Nicepayment;

use Nicepay\NicePayment\Controller\Nicepayment\AbstractAction;
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
use Magento\Sales\Model\Order;
use Nicepay\utils\Helper;
use Magento\Framework\Serialize\Serializer\Json;
use Nicepay\NicePayment\Helper\Security;



class Notifyewallet extends AbstractNotification
{
    protected $nicepayLib;

    protected $registry;
    protected $jsonResultFactory;

    protected $serializer;

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
        Json $serializer,
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
        $this->serializer = $serializer;
        $this->logPrefix = uniqid() . ' : Ewallet SNAP Notification - ';
    }


    /**
     * Handle notification from Nicepay for E-Wallet.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonResultFactory->create();
        $niceLogger = $this->getLogger();

        $niceLogger->info($this->logPrefix . 'Notification Ewallet execute start ');

        $nicepay = $this->nicepayLib;


        try {
            // prepare response
            $responseData = array();
            $responseCd = "200";

            // Get raw JSON POST data
            $request = $this->getRequest();
            $jsonData = $request->getContent();

            // Get headers data
            $xTimestamp = $request->getHeader('X-TIMESTAMP');
            $xSignature = $request->getHeader('X-SIGNATURE');
            $xClientKey = $request->getHeader('X-CLIENT-KEY');

            // Decode JSON to PHP array and get data
            $incomingNotif = $this->serializer->unserialize($jsonData);

            $niceLogger->debug($this->logPrefix . 'Incoming Notification Request Data: ', ['incomingNotif' => $incomingNotif]);

            $referenceNo = $incomingNotif['originalPartnerReferenceNo'] ?? "";
            $amount = $incomingNotif['amount']['value'] ?? "";
            $tXid = $incomingNotif['originalReferenceNo'] ?? "";
            $externalStoreId = $incomingNotif['externalStoreId'] ?? "";


            $order = $this->getOrderById($referenceNo);

            if (!$order) {
                $niceLogger->error($this->logPrefix . 'Order not found');
                throw new \Exception('Order not found');
            }

            // Get Nicepay transaction data
            $tXid = $order->getNicepayTxid();

            // Set Data
            $nicepay->set('payMethod', '05');
            $nicepay->set('tXid', $tXid);
            $nicepay->set('amt', $amount);
            $nicepay->set('externalStoreId', $externalStoreId);
            $nicepay->set('referenceNo', $referenceNo);


            // Verify Signature
            $nicepay->set('signature', $xSignature);
            $nicepay->set('stringToSign', $xClientKey . "|" . $xTimestamp);

            $isVerified = $nicepay->verifySignature();
            $isOrderAlreadyPaid = $order->getNicepayPaymentStatus() == '00' || $order->getNicepayPaymentStatus() == '0';

            if ($isVerified && !$isOrderAlreadyPaid) {
                $niceLogger->info($this->logPrefix . 'Signature Verified', ['signature' => $xSignature, 'isVerified' => $isVerified]);
                // Inquiry Status to Nicepay
                $responseInqStatus = $nicepay->nicepayInquiryStatus();
                $statusCode = $responseInqStatus['status_code'];

                if ($statusCode == '0000' || strpos($statusCode, '200') === 0) {
                    $niceLogger->info($this->logPrefix . 'Inquiry Status Success');
                    $transactionStatus = $responseInqStatus['status_trx'];

                    // Check latest status is paid > update
                    if ($transactionStatus == '00' && $order->getState() == Order::STATE_PENDING_PAYMENT) {
                        $niceLogger->info($this->logPrefix . 'Transaction status is paid. Update pending payment status to processing');

                        $order->setState(Order::STATE_PROCESSING)->setStatus('processing');
                        $order->setNicepayPaymentStatus($transactionStatus);
                        $order->addCommentToStatusHistory("Payment notification received and confirmed, state changed to processing");
                        $this->getOrderRepo()->save($order);


                        $responseData = [
                            'responseCode' => '2005600',
                            'responseMessage' => 'Success'
                        ];
                    } else {
                        $niceLogger->info($this->logPrefix . 'Transaction status is not paid or current state is not pending payment. Ignore notification');

                        $responseCd = "400";
                        $responseData = [
                            'responseCode' => '4005600',
                            'responseMessage' => 'Data not matched'
                        ];
                    }
                } else {
                    $niceLogger->warning($this->logPrefix . 'Inquiry Status Failed');

                    $responseCd = "400";
                    $responseData = [
                        'responseCode' => $responseInqStatus['status_code'],
                        'responseMessage' => $responseInqStatus['status']
                    ];
                }
            } else if ($isOrderAlreadyPaid) {
                $responseCd = "400";
                $responseData = [
                    'responseCode' => '4005600',
                    'responseMessage' => 'Order transaction already paid'
                ];
            } else {
                $niceLogger->info($this->logPrefix . 'Signature not verified');

                $responseCd = "401";
                $responseData = [
                    'responseCode' => '4015600',
                    'responseMessage' => 'Unauthorized Signature'
                ];
            }


            return $result->setData($responseData)
                ->setHttpResponseCode($responseCd)
                ->setHeader('Content-type', 'application/json', true)
                ->setHeader('X-TIMESTAMP', Helper::getFormattedDate(), true);
        } catch (\Exception $e) {
            $niceLogger->error($this->logPrefix . 'Exception caught : ' . $e->getMessage());
            $responseData = [
                'responseCode' => '4000000',
                'responseMessage' => 'Exception caught : ' . $e->getMessage()
            ];


            return $result->setData($responseData)->setHttpResponseCode(400)
                ->setHeader('Content-type', 'application/json', true)
                ->setHeader('X-TIMESTAMP', Helper::getFormattedDate(), true);;
        }
    }
}
