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
use Magento\Framework\Registry;
use Nicepay\NicePayment\Controller\Nicepayment\AbstractAction;

class Approve extends AbstractAction
{

    protected $nicepayLib;

    protected $registry;

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

        $this->registry = $registry;

        $this->nicepayLib = $nicepayLib;
        $this->logPrefix = uniqid() . ' NicePayout Menu : Approve - ';
    }


    public function execute()
    {
        $niceLogger = $this->getLogger();

        $niceLogger->info($this->logPrefix . 'Approve execute start ');


        $id = $this->getRequest()->getParam('id');
        $niceLogger->info($this->logPrefix . 'Approve execute id :' . $id);

        $payMethod = "07"; // PAYOUT
        $action = "APPROVE";
        $resultRedirect = $this->resultRedirectFactory->create();

        try {

            // Get transaction data 
            $order = $this->getOrderByEntityId($id);

            if (!$order) {
                $niceLogger->error($this->logPrefix . 'Approve execute order not found');
                throw new Exception(__('Order %1 not found', $id));
            }

            $orderPaymentMethod = $order->getNicepayPaymentMethod();
            $orderTxid = $order->getNicepayTxid();
            $orderRefNo = $order->getIncrementId();
            $orderBeneficiaryAccountNo = $order->getNicepayBeneficiaryAccountNo();


            if ($orderPaymentMethod != "07") {
                $niceLogger->error($this->logPrefix . 'order is not payout transaction');
                throw new Exception(__('Order %1 is not payout transaction', $id));
            }

            if ($orderTxid == null) {
                $niceLogger->error($this->logPrefix . 'order tXid is not found');
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

            $niceLogger->info($this->logPrefix . 'Approve Response : ', ['response' => $nicepayResponse]);

            if ($nicepayResponse['status_code'] == "2000000" || $nicepayResponse['status_code'] == "0000") {
                $niceLogger->info($this->logPrefix . 'Request approve payout to Nicepay success');
                $this->messageManager->addSuccessMessage(__('Transacrtion %1 has been approved.', $id));

                // Inquiry and update status

                $niceLib->set('referenceNo', $orderRefNo ?? "");
                $niceLib->set('payoutAction', "STATUS_INQUIRY");
                $niceLib->set('accountNo', $orderBeneficiaryAccountNo ?? "");
                $nicepayInquiryResponse = $niceLib->nicepayPayout();

                $niceLogger->info($this->logPrefix . 'Inquiry Response : ', ['response' => $nicepayInquiryResponse]);

                if (($nicepayInquiryResponse['status_code'] == "2000000" || $nicepayInquiryResponse['status_code'] == "0000") && !empty($nicepayInquiryResponse['status_trx'] ?? null)) {
                    $niceLogger->info($this->logPrefix . 'Approve update status transaction to : ' . $nicepayInquiryResponse['status_trx']);
                    $order->setNicepayPaymentStatus($nicepayInquiryResponse['status_trx']);
                    $this->getOrderRepo()->save($order);
                }
            } else {
                $niceLogger->error($this->logPrefix . 'Request approve payout to Nicepay failed');
                $this->messageManager->addErrorMessage(__('Transacrtion %1 Approval Failed : %2', $id, $nicepayResponse['status']));
            }
        } catch (\Exception $e) {
            $niceLogger->error($this->logPrefix . 'Approve execute error : ' . $e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return $resultRedirect->setPath('*/*/');
    }
}
