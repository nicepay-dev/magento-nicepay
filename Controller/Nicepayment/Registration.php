<?php

namespace Nicepay\NicePayment\Controller\Nicepayment;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
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
use Magento\Framework\UrlInterface;
use Nicepay\NicePayment\Helper\CommonHelper;
use Magento\Framework\Registry;


class Registration extends AbstractAction
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


        $this->nicepayLib = $nicepayLib;
        $this->logPrefix = uniqid() . " : NicePayment Registration - ";
    }

    /**
     * Handle Registration Request
     * 
     * This function handle the registration request based on payMethod
     * 
     * @throws \Exception
     */
    public function execute()
    {
        $niceLogger = $this->getLogger();

        $niceLogger->info($this->logPrefix . 'Registration execute start ');
        $nicepay = $this->nicepayLib;


        $order = $this->getOrder();



        try {

            // Get From Request
            $payMethod = $this->getRequest()->getParam('payMethod');
            $userIp = $this->getRequest()->getClientIp();
            $sessionId = $this->getCheckoutSession()->getSessionId();
            $reqDomain = $this->getStoreManager()->getStore()->getBaseUrl();
            $serverIP = $_SERVER['SERVER_ADDR'] ?? getHostByName(getHostName());;
            $userAgent = $this->getRequest()->getHeader('User-Agent');
            $apiVersion = $nicepay->getPaymentConfig(CommonHelper::getNicepayPaymentCode($payMethod), 'nicepay_api_version');
            $isSnap = $apiVersion !== null && $apiVersion == 'snap';

            $grandTotal = (int)$order->getGrandTotal();
            $shippingAmount = (int)$order->getShippingAmount();
            $shippingDesc = $order->getShippingDescription();
            $discountAmount = (int)$order->getDiscountAmount();
            $discountDesc = $order->getDiscountDescription();
            $orderCurrency = $order->getOrderCurrency()->getData()["currency_code"];

            // GET CART DATA
            $items = $order->getAllVisibleItems();

            $cartData["count"] = strval(count($items));
            $goodsNm = "";

            $dataSeller = [];

            if ($payMethod == "00" || $payMethod == "06") {
                $niceLogger->info($this->logPrefix . 'Generate Seller Data');

                $store = $this->getStoreManager()->getStore();

                $dataSeller[] = [
                    'sellersId' => $store->getId(),
                    'sellersNm' => $store->getName(),
                    'sellersEmail' => "NicepayClient@email.com",
                    'sellersUrl' => $store->getBaseUrl(),
                    'sellersAddress' => [
                        'sellerNm' => $store->getName() . " Nice",
                        'sellerLastNm' => "Nice",
                        'sellerAddr' => "Jl. Raya Casablanca Raya No.Kav. 88",
                        'sellerCity' => "Jakarta Selatan",
                        "sellerPostCd" => "12870",
                        "sellerPhone" => "081234567890",
                        'sellerCountry' => "ID",
                    ],

                ];

                $niceLogger->debug($this->logPrefix . 'Seller data : ', ['sellerData' => $dataSeller]);
            }

            $niceLogger->info($this->logPrefix . 'Generate Cart Data');
            foreach ($items as $i) {
                $productId = $i->getProductId();

                try {
                    $product = $this->productRepository->getById($productId);
                    $niceLogger->info($this->logPrefix . 'Get product item with productId :', ['productId' => $productId, 'name' => $product->getName()]);

                    $image = $this->getMediaBaseUrl() . 'catalog/product' . $product->getImage();

                    $goodsQuantity = (int) $i->getQtyOrdered();
                    $goodsPrice = (int) $i->getPrice();

                    $goodsTotal = $goodsQuantity * $goodsPrice;

                    // For Snap using decimals 
                    if ($isSnap) {
                        $goodsTotal = (string) $goodsTotal . ".00";
                    }

                    $itemData = [
                        "img_url" => $image,
                        "goods_name" => $i->getName(),
                        "goods_detail" => "SKU:" . $i->getSku() . " (" . (int)$i->getQtyOrdered() . " Items)",
                        "goods_amt" => (string) $goodsTotal,
                        "goods_quantity" => (string)$goodsQuantity
                    ];


                    if ($payMethod == "00" || $payMethod == "06") {
                        $additionalData = [
                            "goods_id" => $i->getSku(),
                            "goods_url" => $product->getProductUrl(),
                            "goods_type" => "OTHERS",
                            "goods_sellers_id" => $this->getStoreManager()->getStore()->getId(),
                            "goods_sellers_name" => $this->getStoreManager()->getStore()->getName(),
                        ];

                        $itemData = array_merge($itemData, $additionalData);
                    }

                    $cartData["item"][] = $itemData;
                    $goodsNm .= $i->getName() . ", ";

                    $niceLogger->debug($this->logPrefix . 'Updated cart data', ['cartData' => $cartData]);
                } catch (\Exception $e) {
                    $niceLogger->error($this->logPrefix . 'Failed to load product', ['productId' => $productId, 'exception' => $e->getMessage()]);
                }
            }

            if ($discountAmount != 0) {
                $niceLogger->info($this->logPrefix . 'Generate Discount Data');

                $updatedCount = $cartData["count"] + 1;
                $cartData["count"] = (string)$updatedCount;

                // For Snap using decimals 
                if ($isSnap) {
                    $discountAmount = (string) $discountAmount . ".00";
                }

                $itemData = [
                    "img_url" => $this->getMediaBaseUrl() . "Nicepay/coupon.png",
                    "goods_name" => "DISCOUNT COUPON",
                    "goods_detail" => $discountDesc,
                    "goods_amt" => (string)$discountAmount,
                    "goods_quantity" => "1"
                ];

                if ($payMethod == "00" || $payMethod == "06") {
                    $additionalData = [
                        "goods_id" => "disount-coupon",
                        "goods_url" => $this->getStoreManager()->getStore()->getBaseUrl(),
                        "goods_type" => "OTHERS",
                        "goods_sellers_id" => $this->getStoreManager()->getStore()->getId(),
                        "goods_sellers_name" => $this->getStoreManager()->getStore()->getName(),
                    ];

                    $itemData = array_merge($itemData, $additionalData);
                }

                $cartData["item"][] = $itemData;
            }

            if ($shippingAmount > 0) {
                $niceLogger->info($this->logPrefix . 'Generate Shipping Data');
                $updatedCount = $cartData["count"] + 1;
                $cartData["count"] = (string)$updatedCount;

                // For Snap using decimals 
                if ($isSnap) {
                    $shippingAmount = (string) $shippingAmount . ".00";
                }
                $itemData = [
                    "img_url" => $this->getMediaBaseUrl() . "Nicepay/delivery.png",
                    "goods_name" => "SHIPPING",
                    "goods_detail" => $shippingDesc,
                    "goods_amt" => (string)$shippingAmount,
                    "goods_quantity" => "1"
                ];

                if ($payMethod == "00" || $payMethod == "06") {
                    $additionalData = [
                        "goods_id" => "shipping",
                        "goods_url" => $this->getStoreManager()->getStore()->getBaseUrl(),
                        "goods_type" => "OTHERS",
                        "goods_sellers_id" => $this->getStoreManager()->getStore()->getId(),
                        "goods_sellers_name" => $this->getStoreManager()->getStore()->getName(),
                    ];

                    $itemData = array_merge($itemData, $additionalData);
                }

                $cartData["item"][] = $itemData;
            }

            $niceLogger->info('Processing Shipping and Billing data');

            $customerId = $order->getCustomerId();
            $billing = $order->getBillingAddress();
            $shipping = $order->getShippingAddress();

            //Set Billing Address
            $name = $billing['firstname'] . " " . $billing['lastname'];
            $billingNm = $this->checkingAddrRule("name", $name);

            $email = $billing['email'];
            $billingEmail = $this->checkingAddrRule("email", $email);

            $phone = $billing['telephone'];
            $billingPhone = $this->checkingAddrRule("phone", $phone);

            $addr = $billing['street'];
            $billingAddr = $this->checkingAddrRule("addr", $addr);

            $country = $billing['country_id'];
            $billingCountry = $this->checkingAddrRule("country", $country);

            $state = $billing['region'];
            $billingState = $this->checkingAddrRule("state", $state);

            $city = $billing['city'];
            $billingCity = $this->checkingAddrRule("city", $city);

            $postCd = $billing['postcode'];
            $billingPostCd = $this->checkingAddrRule("postCd", $postCd);

            //Set Shipping Address
            $name = $shipping['firstname'] . " " . $shipping['lastname'];
            $deliveryNm = $this->checkingAddrRule("name", $name);

            $addr = $shipping['street'];
            $deliveryAddr = $this->checkingAddrRule("addr", $addr);

            $city = $shipping['city'];
            $deliveryCity = $this->checkingAddrRule("city", $city);

            $country = $shipping['country_id'];
            $deliveryCountry = $this->checkingAddrRule("country", $country);

            $state = $shipping['region'];
            $deliveryState = $this->checkingAddrRule("state", $state);

            $email = $shipping['email'];
            $deliveryEmail = $this->checkingAddrRule("email", $email);

            $phone = $shipping['telephone'];
            $deliveryPhone = $this->checkingAddrRule("phone", $phone);

            $postCd = $shipping['postcode'];
            $deliveryPostCd = $this->checkingAddrRule("postCd", $postCd);


            $bankCd = null;

            // SET PAYLOAD DATA 

            $niceLogger->info($this->logPrefix . 'Set up request data');


            // Set From Request
            $nicepay->set('userIP', $userIp);
            $nicepay->set('userSessionID', $sessionId);
            $nicepay->set('reqDomain', $reqDomain);
            $nicepay->set('reqServerIP', $serverIP);
            $nicepay->set('userAgent', $userAgent);

            // Set Payment Method 
            $nicepay->set('payMethod', $payMethod);

            // Goodsname no more than 200 characters
            if (strlen($goodsNm) > 200) {
                $goodsNm = substr($goodsNm, 0, 200);
            }
            $nicepay->set('goodsNm', $goodsNm);
            $nicepay->set('currency', $orderCurrency);
            $nicepay->set('cartData', json_encode($cartData));
            $nicepay->set("sellers", json_encode($dataSeller));
            $nicepay->set('amt', $grandTotal); // Total gross amount //

            $nicepay->set('billingNm', $billingNm); // Customer name
            $nicepay->set('billingPhone', $billingPhone); // Customer phone number
            $nicepay->set('billingEmail', $billingEmail); //
            $nicepay->set('billingAddr', $billingAddr);
            $nicepay->set('billingCity', $billingCity);
            $nicepay->set('billingState', $billingState);
            $nicepay->set('billingPostCd', $billingPostCd);
            $nicepay->set('billingCountry', $billingCountry);

            $nicepay->set('deliveryNm', $deliveryNm); // Delivery name
            $nicepay->set('deliveryPhone', $deliveryPhone);
            $nicepay->set('deliveryEmail', $deliveryEmail);
            $nicepay->set('deliveryAddr', $deliveryAddr);
            $nicepay->set('deliveryCity', $deliveryCity);
            $nicepay->set('deliveryState', $deliveryState);
            $nicepay->set('deliveryPostCd', $deliveryPostCd);
            $nicepay->set('deliveryCountry', "indonesia"); //$deliveryCountry

            $nicepay->set('orderId', $order->getIncrementId());

            // set notif url 
            $nicepay->dbProcessUrl = $this->getCustomStoreUrl("nicepay/nicepayment/notification");


            if (isset($payMethod) && $payMethod == '01') {
                $niceLogger->info($this->logPrefix . 'Set up additional request data for Card Payment');


                $cardNo = $this->getRequest()->getParam('cardNo');
                $cardExpYymm = $this->getRequest()->getParam('cardExpYymm');
                $cardCvv = $this->getRequest()->getParam('cardCvv');
                $cardHolderNm = $this->getRequest()->getParam('cardHolderNm');

                $nicepay->set('cardNo', $cardNo);
                $nicepay->set('cardExpYymm', $cardExpYymm);
                $nicepay->set('cardCvv', $cardCvv);
                $nicepay->set('cardHolderNm', $cardHolderNm);

                $nicepay->set('description',  "Card Registration Request Magento Nicepay For Store order ID : " . $order->getIncrementId());
            } else if (isset($payMethod) && $payMethod == '02') {
                $niceLogger->info($this->logPrefix . 'Set up additional request data for Virtual Account');

                $nicepay->snapDbProcessUrl = $this->getCustomStoreUrl("notification/api/v1.0/transfer-va/payment");
                $bankCd = $this->getRequest()->getParam('bankCd');
                $nicepay->set('bankCd', $bankCd);
                $nicepay->set('description',  "VA Registration Request Magento Nicepay For Store order ID : " . $order->getIncrementId());
            } else if (isset($payMethod) && $payMethod == '03') {
                $niceLogger->info($this->logPrefix . 'Set up additional request data for Convenience Store');
                $nicepay->set('description',  "CVS Registration Request Magento Nicepay For Store order ID : " . $order->getIncrementId());
                $mitraCd = $this->getRequest()->getParam('mitraCd');
                $nicepay->set('mitraCd', $mitraCd);
            } else if (isset($payMethod) && $payMethod == '05') {
                $niceLogger->info($this->logPrefix . 'Set up additional request data for Ewallet');

                $nicepay->snapDbProcessUrl = $this->getCustomStoreUrl("notification/api/v1.0/debit/notify");
                $mitraCd = $this->getRequest()->getParam('mitraCd');
                $billingPhone = $this->getRequest()->getParam('phoneNo');

                $nicepay->set('description',  "Ewallet Registration Request Magento Nicepay For Store order ID : " . $order->getIncrementId());
                $nicepay->set('mitraCd', $mitraCd);
                $nicepay->set('billingPhone', $billingPhone);
            } else if (isset($payMethod) && $payMethod == '06') {
                $niceLogger->info($this->logPrefix . 'Set up additional request data for Payloan');

                $nicepay->set('description',  "Payloan Registration Request Magento Nicepay For Store order ID : " . $order->getIncrementId());
                $mitraCd = $this->getRequest()->getParam('mitraCd');
                $nicepay->set('mitraCd', $mitraCd);
            } else if (isset($payMethod) && $payMethod == '07') {
                $niceLogger->info($this->logPrefix . 'Set up additional request data for Payout');

                $nicepay->snapDbProcessUrl = $this->getCustomStoreUrl("notification/api/v1.0/transfer/notify");

                $nicepay->set('accountNo', $this->getRequest()->getParam('accountNo') ?? "");
                $bankCd = $this->getRequest()->getParam('bankCd');
                $nicepay->set('bankCd', $bankCd);
                $nicepay->set('msId',  "");
                $nicepay->set('benefPhone', $billingPhone ?? "");
                $nicepay->set('benefNm', $billingNm ?? "");
                $nicepay->set('benefStatus', "1");
                $nicepay->set('benefType',  "1");
                $nicepay->set('reservedDt',  "");
                $nicepay->set('reservedTm',  "");
                $nicepay->set('description',  "Payout Registration Request Magento Nicepay For Store order ID : " . $order->getIncrementId());
                $nicepay->set('payoutMethod', "0");
                $nicepay->set('benefPostCode', $billingPostCd ?? "");
            } else if (isset($payMethod) && $payMethod == '08') {
                $niceLogger->info($this->logPrefix . 'Set up additional request data for Qris');

                $nicepay->snapDbProcessUrl = $this->getCustomStoreUrl("notification/api/v1.0/qr/qr-mpm-notify");
                $nicepay->set('description',  "Qris Registration Request Magento Nicepay For Store order ID : " . $order->getIncrementId());
            }


            if ($order->getState() === Order::STATE_NEW) {
                $niceLogger->info($this->logPrefix . 'Update Order Status to Pending');
                // update from new > pending 
                $this->updatePaymentStatusToPending($order);

                $niceLogger->info($this->logPrefix . 'Payment registration to Nicepay');
                $nicepayResponse = $nicepay->nicepayRegistration();



                if ($payMethod == '07') {
                    $beneficiaryAccountNo = $this->getRequest()->getParam('accountNo');
                    $nicepayResponse['beneficiary_account_no'] = $beneficiaryAccountNo;
                }

                if (isset($nicepayResponse['tXid'])) {
                    $niceLogger->info($this->logPrefix . 'Update order with registered tXid');

                    $this->updateTXidOrder($order, $nicepayResponse);
                }

                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }

                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);


                // Build the URL with query params
                $url = $this->_url->getUrl('nicepay/nicepayment/success', ['_query' => $nicepayResponse]);

                $resultRedirect->setUrl($url);


                if (($nicepayResponse['status_code'] == '00000' || strpos($nicepayResponse['status_code'], '200') === 0) &&
                    ($payMethod == '00' || $payMethod == '01' || $payMethod == '05' || $payMethod == '06')
                ) {
                    $niceLogger->info($this->logPrefix . 'Redirect to external payment page', ['payment_url' => $nicepayResponse['payment_url']]);

                    $mitraMessages = [
                        'OVOE' => 'Check your OVO Application for Notification.',
                        'LINK' => 'Redirecting to LinkAja...',
                        'DANA' => 'Redirecting to DANA...',
                        'ESHP' => 'Redirecting to ShopeePay...',
                        'IDNA' => 'Redirecting to Indodana...',
                        'KDVI' => 'Redirecting to Kredivo...',
                        'AKLP' => 'Redirecting to Akulaku...'
                    ];

                    $defaultMessage = 'You will be redirected to complete your payment.';
                    $alertMessage = $mitraMessages[$nicepayResponse['mitra_cd'] ?? ''] ?? $defaultMessage;

                    $this->getResponse()->setBody(
                        "<script>
                            if (confirm('" . addslashes($alertMessage) . "\\n\\nClick OK to continue.')) {
                                window.location.href = '" . $nicepayResponse['payment_url'] . "';
                            } else {
                                window.location.href = '" . $this->_url->getUrl('checkout/cart') . "';
                            }
                        </script>"
                    );
                    return;
                }

                return $resultRedirect;
            } elseif ($order->getState() === Order::STATE_CANCELED) {
                $niceLogger->error($this->logPrefix . 'Order is already canceled', ['order_id' => $order->getIncrementId()]);
                return $this->_redirect('checkout/cart');
            } else {
                $niceLogger->error($this->logPrefix . 'Order in unrecognized state', ['state' => $order->getState(), 'order_id' => $order->getIncrementId()]);
                return $this->_redirect('checkout/cart');
            }
        } catch (\Throwable $e) {
            $errorMessage = sprintf('nicepay/checkout/registration failed: Order #%s - %s', $order->getIncrementId(), $e->getMessage());

            $niceLogger->error($this->logPrefix . '' . $errorMessage, ['order_id' => $order->getIncrementId()]);
            $niceLogger->error($this->logPrefix . 'Exception caught on nicepay/nicepayment/registration: ' . $e->getMessage());
            $niceLogger->error($e->getTraceAsString());

            // cancel order
            try {
                $niceLogger->warning($this->logPrefix . 'Cancel order', ['order_id' => $order->getIncrementId()]);
                $this->cancelOrder($order, $e->getMessage());
            } catch (\Exception $e) {

                $niceLogger->error($this->logPrefix . 'Cancel order failed:' . $e->getMessage(), ['order_id' => $order->getIncrementId()]);
            }

            return $this->redirectToCart($e->getMessage());
        }
    }

    public function getControllerUrl($path)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $urlInterface = $objectManager->get(\Magento\Framework\UrlInterface::class);

        return $urlInterface->getUrl($path);
    }

    public function getApiUrl($path)
    {
        $baseUrl = $this->getStoreManager()->getStore()->getBaseUrl();
        return rtrim($baseUrl, '/') . '/rest/' . ltrim($path, '/');
    }


    public function checkingAddrRule($var, $val): int|string
    {
        $value = null;

        $rule = $this->addrRule();
        $type = $rule[$var]->type;
        $length = (int)$rule[$var]->length;

        $defaultValue = $rule[$var]->defaultValue;
        if ($val == null || $val == "" || "null" == $val) {
            $val = $defaultValue;
        }

        switch ($type) {
            case "string":
                $valLength = strlen($val);
                if ($valLength > $length) {
                    $val = substr($val, 0, $length);
                }

                $value = (string)$val;
                break;

            case "integer":
                if (gettype($val) != "string" || gettype($val) != "String") {
                    $val = (string)$val;
                }

                $valLength = strlen($val);
                if ($valLength > $length) {
                    $val = substr($val, 0, $length);
                }

                $value = (int)$val;
                break;

            default:
                $value = (string)$val;
                break;
        }

        return $value;
    }



    public function getMediaBaseUrl(): string
    {
        // Use dependency injection for StoreManagerInterface instead of ObjectManager
        return $this->getStoreManager()->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    }


    public function addrRule()
    {
        $addrRule = array(
            "name" => (object) array(
                "type" => "string",
                "length" => 30,
                "defaultValue" => "dummy"
            ),
            "phone" => (object) array(
                "type" => "string",
                "length" => 15,
                "defaultValue" => "00000000000"
            ),
            "email" => (object) array(
                "type" => "string",
                "length" => 40,
                "defaultValue" => "dummy"
            ),
            "addr" => (object) array(
                "type" => "string",
                "length" => 100,
                "defaultValue" => "dummy"
            ),
            "city" => (object) array(
                "type" => "string",
                "length" => 50,
                "defaultValue" => "dummy"
            ),
            "state" => (object) array(
                "type" => "string",
                "length" => 50,
                "defaultValue" => "dummy"
            ),
            "postCd" => (object) array(
                "type" => "string",
                "length" => 10,
                "defaultValue" => "000000"
            ),
            "country" => (object) array(
                "type" => "string",
                "length" => 10,
                "defaultValue" => "dummy"
            )
        );

        return $addrRule;
    }


    private function updatePaymentStatusToPending(Order $order)
    {
        try {

            $order->setState(Order::STATE_PENDING_PAYMENT)->setStatus('pending');
            $order->addCommentToStatusHistory("Generated by Nicepay");
            $this->getOrderRepo()->save($order);

            $this->getLogger()->info(
                $this->logPrefix . 'changePendingPaymentStatus success',
                ['order_id' => $order->getIncrementId()]
            );
        } catch (\Exception $e) {
            $this->getLogger()->error(
                $this->logPrefix . '' . sprintf('changePendingPaymentStatus failed: %s', $e->getMessage()),
                ['order_id' => $order->getIncrementId()]
            );

            throw new LocalizedException(
                new Phrase($e->getMessage())
            );
        }
    }


    private function updateTXidOrder(Order $order, array $nicepayResponse)
    {
        try {
            $this->getLogger()->info(
                'Update tXid data'
            );

            $payment = $order->getPayment();
            $payment->setAdditionalInformation('payment_gateway', 'nicepay');

            if (isset($nicepayResponse['tXid'])) {
                $payment->setAdditionalInformation('nicepay_txid', $nicepayResponse['tXid']);
                $payment->setAdditionalInformation('nicepay_payment_method', $nicepayResponse['payment_method']);
                $payment->setAdditionalInformation('nicepay_payment_status', '3');
                $order->setNicepayTxid($nicepayResponse['tXid']);
                $order->setNicepayPaymentMethod($nicepayResponse['payment_method']);
                $order->setNicepayPaymentStatus('3');

                if ($nicepayResponse['payment_method'] == "07") {
                    $payment->setAdditionalInformation('account_no', $nicepayResponse['beneficiary_account_no']);
                    $order->setNicepayBeneficiaryAccountNo($nicepayResponse['account_no']);
                }
            }

            $this->getOrderRepo()->save($order);
            $this->getLogger()->info(
                'addInvoiceData success',
                ['order_id' => $order->getIncrementId(), 'nicepay_txid' => $nicepayResponse['tXid']]
            );
        } catch (\Exception $e) {
            $this->getLogger()->error(
                sprintf('addInvoiceData failed: %s', $e->getMessage()),
                ['order_id' => $order->getIncrementId(), 'nicepay_txid' => $nicepayResponse['tXid']]
            );

            throw new LocalizedException(
                new Phrase($e->getMessage())
            );
        }
    }


    private function redirectToCart($failureReason)
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_url->getUrl('checkout/cart'), ['_secure' => false]);
        return $resultRedirect;
    }
}
