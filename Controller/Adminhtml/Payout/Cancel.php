<?php

namespace Nicepay\NicePayment\Controller\Adminhtml\Payout;

use Exception;
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
use Nicepay\NicePayment\Controller\Nicepayment\AbstractAction;

class Cancel extends AbstractAction
{
    protected $nicepayLib;
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
        NicepayLib $nicepayLib
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


        $this->nicepayLib = $nicepayLib;
        $this->logPrefix = uniqid() . ' NicePayout Menu : Cancel - ';
    }


    public function execute()
    {
        $niceLogger = $this->getLogger();
        $niceLogger->info($this->logPrefix . 'Cancel execute start ');

        $id = $this->getRequest()->getParam('id');
        $payMethod = "07"; // PAYOUT
        $action = "CANCEL";

        $resultRedirect = $this->resultRedirectFactory->create();
        try {

            // Get transaction data 
            $order = $this->getOrderByEntityId($id);

            if (!$order) {
                $niceLogger->error($this->logPrefix . 'Order not found');
                throw new Exception(__('Order %1 not found', $id));
            }
            $orderPaymentMethod = $order->getNicepayPaymentMethod();
            $orderTxid = $order->getNicepayTxid();
            $orderRefNo = $order->getIncrementId();
            $orderBeneficiaryAccountNo = $order->getNicepayBeneficiaryAccountNo();

            if ($orderPaymentMethod != "07") {
                $niceLogger->error($this->logPrefix . 'Order is not payout transaction');
                throw new Exception(__('Order %1 is not payout transaction', $id));
            }

            if ($orderTxid == null) {
                $niceLogger->error($this->logPrefix . 'Order tXid is not found');
                throw new Exception(__('Order %1 tXid is not found', $id));
            }


            // Set Nicepay Lib 

            $niceLib = $this->nicepayLib;
            $niceLib->set('payMethod', $payMethod);
            $niceLib->set('payoutAction', $action);

            $niceLib->set('tXid', $orderTxid ?? "");
            $niceLib->set('originalPartnerReferenceNo', $orderRefNo ?? "");
            $niceLib->set('orderId', $orderRefNo ?? "");

            $nicepayResponse = $niceLib->nicepayPayout();
            $niceLogger->info($this->logPrefix . 'Cancel Response : ', ['response' => $nicepayResponse]);


            if ($nicepayResponse['status_code'] == "2000000" || $nicepayResponse['status_code'] == "0000") {
                $niceLogger->info($this->logPrefix . 'Request cancel payout transaction success');
                $this->messageManager->addSuccessMessage(__('Transacrtion %1 has been canceled.', $id));

                // Inquiry and update status

                $niceLib->set('payoutAction', "STATUS_INQUIRY");
                $niceLib->set('accountNo', $orderBeneficiaryAccountNo ?? "");
                $nicepayInquiryResponse = $niceLib->nicepayPayout();

                $niceLogger->info($this->logPrefix . 'Inquiry Response : ', ['response' => $nicepayInquiryResponse]);

                if (($nicepayInquiryResponse['status_code'] == "2000000" || $nicepayInquiryResponse['status_code'] == "0000") && !empty($nicepayInquiryResponse['status_trx'] ?? null)) {
                    $niceLogger->info($this->logPrefix . 'Inquiry status success. Update Payout Status to : ' . $nicepayInquiryResponse['status_trx']);
                    $order->setNicepayPaymentStatus($nicepayInquiryResponse['status_trx']);
                    $this->getOrderRepo()->save($order);
                }
            } else {
                $niceLogger->error($this->logPrefix . 'Request cancel payout transaction failed');
                $this->messageManager->addErrorMessage(__('Transacrtion %1 Cancelation Failed : %2', $id, $nicepayResponse['status']));
            }
        } catch (Exception $e) {
            $niceLogger->error($this->logPrefix . 'Error : ' . $e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return $resultRedirect->setPath('*/*/');
    }
}
