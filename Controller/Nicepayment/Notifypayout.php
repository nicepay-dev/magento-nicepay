<?php

namespace Nicepay\NicePayment\Controller\Nicepayment;


use Nicepay\NicePayment\Controller\Nicepayment\AbstractNotification;
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
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order;
use Nicepay\utils\Helper;
use Nicepay\NicePayment\Helper\Security;


class Notifypayout extends AbstractNotification
{
    protected $nicepayLib;

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

        $this->nicepayLib = $nicepayLib;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->serializer = $serializer;
        $this->logPrefix = 'NicePayment Notification : Payout SNAP Notification - ';
    }
    /**
     * Notification API for Payout
     *
     */
    public function execute()
    {
        $result = $this->jsonResultFactory->create();
        $niceLogger = $this->getLogger();
        $niceLogger->info($this->logPrefix . 'Notification execute start ');

        $nicepay = $this->nicepayLib;
        $nicepay->set('payMethod', '07');



        try {
            // prepare response
            $responseData = array();
            $responseCd = "200";

            // incoming request
            $request = $this->getRequest();

            // Get raw JSON POST data
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
            $accountNo = $incomingNotif['beneficiaryAccountNo'] ?? "";


            $order = $this->getOrderById($referenceNo);

            if (!$order) {
                $niceLogger->error($this->logPrefix . 'Order not found');
                throw new \Exception('Order not found');
            }

            $tXid = $order->getNicepayTxid();

            // Verify Signature
            $nicepay->set('signature', $xSignature);
            $nicepay->set('stringToSign', $xClientKey . "|" . $xTimestamp);

            $isVerified = $nicepay->verifySignature();
            $isOrderAlreadyPaid = $order->getNicepayPaymentStatus() == '00' || $order->getNicepayPaymentStatus() == '0';

            if ($isVerified && !$isOrderAlreadyPaid) {
                $niceLogger->info($this->logPrefix . 'Signature Verified', ['signature' => $xSignature, 'isVerified' => $isVerified]);

                // Set Data
                $nicepay->set('tXid', $tXid);
                $nicepay->set('amt', $amount);
                $nicepay->set('accountNo', $accountNo);
                $nicepay->set('referenceNo', $referenceNo);


                $responseInqStatus = $nicepay->nicepayInquiryStatus();
                $statusCode = $responseInqStatus['status_code'];

                if ($statusCode == '0000' || strpos($statusCode, '200') === 0) {
                    $niceLogger->info("Inquiry Status Success");

                    $transactionStatus = $responseInqStatus['status_trx'];
                    if ($transactionStatus == '00' && $order->getState() == Order::STATE_PENDING_PAYMENT) {
                        $niceLogger->info($this->logPrefix . 'Transaction status is paid. Update pending payment status to processing');

                        $order->setState(Order::STATE_PROCESSING)->setStatus('processing');
                        $order->setNicepayPaymentStatus($transactionStatus);
                        $order->addCommentToStatusHistory("Payment notification received and confirmed, state changed to processing");
                        $this->getOrderRepo()->save($order);


                        // Set Response Data VA
                        $responseData = [
                            'responseCode' => '2000000',
                            'responseMessage' => 'Success',
                        ];
                    } else {
                        $niceLogger->info("Transaction status is not paid or current state is not pending payment. Ignore notification");

                        $responseCd = "400";
                        $responseData = [
                            'responseCode' => '4000000',
                            'responseMessage' => 'Data not matched'
                        ];
                    }
                } else if ($isOrderAlreadyPaid) {
                    $responseCd = "400";
                    $responseData = [
                        'responseCode' => '4005600',
                        'responseMessage' => 'Order transaction already paid'
                    ];
                } else {
                    $niceLogger->info("Inquiry Status Failed");

                    $responseCd = "400";
                    $responseData = [
                        'responseCode' => $responseInqStatus['status_code'],
                        'responseMessage' => $responseInqStatus['status']
                    ];
                }
            } else {
                $niceLogger->info("Signature not verified");

                $responseCd = "401";
                $responseData = [
                    'responseCode' => '4010000',
                    'responseMessage' => 'Unauthorized Signature'
                ];
            }

            return $result->setData($responseData)
                ->setHttpResponseCode($responseCd)
                ->setHeader('Content-type', 'application/json', true)
                ->setHeader('X-TIMESTAMP', Helper::getFormattedDate(), true);
        } catch (\Exception $e) {
            $niceLogger->error($this->logPrefix . 'Exception caught', ['exception' => $e->getMessage()]);
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
