<?php

namespace Nicepay\NicePayment\Helper;

use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Product;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DB\Transaction as DbTransaction;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderNotifier;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Nicepay\NicePayment\Model\Payment\Nicepay;


class Data extends AbstractHelper
{
    const NICEPAY_NICEPAYMENT_VERSION = '1.0.0';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Nicepay
     */
    private $nicepay;

    /**
     * @var File
     */
    private $fileSystem;

    /**
     * @var Product
     */
    private $product;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var QuoteFactory
     */
    private $quote;

    /**
     * @var QuoteManagement
     */
    private $quoteManagement;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * @var DbTransaction
     */
    protected $dbTransaction;

    /**
     * @var
     */
    protected $orderNotifier;

    /**
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @var CategoryRepository $categoryRepository
     */
    protected $categoryRepository;

    /**
     * @var PhoneNumberFormat $phoneNumberFormatHelper
     */
    protected $phoneNumberFormatHelper;

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Nicepay $nicepay
     * @param File $fileSystem
     * @param Product $product
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerFactory $customerFactory
     * @param QuoteFactory $quote
     * @param QuoteManagement $quoteManagement
     * @param DateTimeFactory $dateTimeFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param InvoiceService $invoiceService
     * @param DbTransaction $dbTransaction
     * @param OrderNotifier $orderNotifier
     * @param AssetRepository $assetRepository
     * @param CategoryRepository $categoryRepository
     * @param CommonHelper $commonHelper
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Nicepay $nicepay,
        File $fileSystem,
        Product $product,
        CustomerRepositoryInterface $customerRepository,
        CustomerFactory $customerFactory,
        QuoteFactory $quote,
        QuoteManagement $quoteManagement,
        DateTimeFactory $dateTimeFactory,
        ScopeConfigInterface $scopeConfig,
        InvoiceService $invoiceService,
        DbTransaction $dbTransaction,
        OrderNotifier $orderNotifier,
        AssetRepository $assetRepository,
        CategoryRepository $categoryRepository,
        \Nicepay\NicePayment\Helper\CommonHelper $commonHelper
    ) {
        $this->storeManager = $storeManager;
        $this->nicepay = $nicepay;
        $this->fileSystem = $fileSystem;
        $this->product = $product;
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->scopeConfig = $scopeConfig;
        $this->invoiceService = $invoiceService;
        $this->dbTransaction = $dbTransaction;
        $this->orderNotifier = $orderNotifier;
        $this->assetRepository = $assetRepository;
        $this->categoryRepository = $categoryRepository;
        $this->phoneNumberFormatHelper = $commonHelper;

        parent::__construct($context);
    }

    /**
     * @return StoreManagerInterface
     */
    protected function getStoreManager()
    {
        return $this->storeManager;
    }



    /**
     * @return bool|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreName()
    {
        return substr(preg_replace("/[^a-z0-9]/mi", "", $this->getStoreManager()->getStore()->getName()), 0, 20);
    }



    /**
     * @return mixed
     */
    public function getAllowedMethod()
    {
        return $this->nicepay->getAllowedMethod();
    }


    /**
     * @return mixed
     */
    public function getIsActive()
    {
        return $this->nicepay->getIsActive();
    }

    public function getIsUsingMinAmount($code)
    {
        return $this->scopeConfig->getValue("payment/$code/use_min_amount", ScopeInterface::SCOPE_STORE);
    }

    public function getIsUsingMaxAmount($code)
    {
        return $this->scopeConfig->getValue("payment/$code/use_max_amount", ScopeInterface::SCOPE_STORE);
    }

    public function getMinAmount($code)
    {
        return $this->scopeConfig->getValue("payment/$code/min_amount", ScopeInterface::SCOPE_STORE);
    }

    public function getMaxAmount($code)
    {
        return $this->scopeConfig->getValue("payment/$code/max_amount", ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function jsonData()
    {
        $inputs = json_decode((string) $this->fileSystem->fileGetContents((string)'php://input'), (bool) true);
        $methods = $this->_request->getServer('REQUEST_METHOD');

        if (empty($inputs) === true && $methods === 'POST') {
            $post = $this->_request->getPostValue();

            if (array_key_exists('payment', $post)) {
                $inputs['paymentMethod']['additional_data'] = $post['payment'];
            }
        }

        return (array) $inputs;
    }

    // CEK INI NANTI
    /**
     * Map card's failure reason to more detailed explanation based on current insight.
     *
     * @param $failureReason
     * @return string
     */
    public function failureReasonInsight($failureReason)
    {
        switch ($failureReason) {
            case 'CARD_DECLINED':
            case 'STOLEN_CARD':
                return 'The bank that issued this card declined the payment but didn\'t tell us why.
                Try another card, or try calling your bank to ask why the card was declined.';
            case 'INSUFFICIENT_BALANCE':
                return "Your bank declined this payment due to insufficient balance. Ensure
                that sufficient balance is available, or try another card";
            case 'INVALID_CVN':
                return "Your bank declined the payment due to incorrect card details entered. Try to
                enter your card details again, including expiration date and CVV";
            case 'INACTIVE_CARD':
                return "This card number does not seem to be enabled for eCommerce payments. Try
                another card that is enabled for eCommerce, or ask your bank to enable eCommerce payments for your card.";
            case 'EXPIRED_CARD':
                return "Your bank declined the payment due to the card being expired. Please try
                another card that has not expired.";
            case 'PROCESSOR_ERROR':
                return 'We encountered issue in processing your card. Please try again with another card';
            case 'USER_DID_NOT_AUTHORIZE_THE_PAYMENT':
                return 'Please complete the payment request within 60 seconds.';
            case 'USER_DECLINED_THE_TRANSACTION':
                return 'You rejected the payment request, please try again when needed.';
            case 'PHONE_NUMBER_NOT_REGISTERED':
                return 'Your number is not registered in OVO, please register first or contact OVO Customer Service.';
            case 'EXTERNAL_ERROR':
                return 'There is a technical issue happens on OVO, please contact the merchant to solve this issue.';
            case 'SENDING_TRANSACTION_ERROR':
                return 'Your transaction is not sent to OVO, please try again.';
            case 'EWALLET_APP_UNREACHABLE':
                return 'Do you have OVO app on your phone? Please check your OVO app on your phone and try again.';
            case 'REQUEST_FORBIDDEN_ERROR':
                return 'Your merchant disable OVO payment from his side, please contact your merchant to re-enable it
                    before trying it again.';
            case 'DEVELOPMENT_MODE_PAYMENT_ACKNOWLEDGED':
                return 'Development mode detected. Please refer to our documentations for successful payment
                    simulation';
            default:
                return $failureReason;
        }
    }


    public function mapSalesRuleType($type)
    {
        switch ($type) {
            case 'to_percent':
            case 'by_percent':
                return 'PERCENTAGE';
            case 'to_fixed':
            case 'by_fixed':
                return 'FIXED';
            default:
                return $type;
        }
    }


    public function getNicepayPaymentList(): array
    {
        return [
            "redirect"          => "redirect",
            "card"              => "card",
            "virtual_account"   => "virtual_account",
            "cvs"               => "cvs",
            "directdebit"       => "directdebit",
            "qris"              => "qris",
            "ewallet"           => "ewallet",
            "payloan"           => "payloan",
            "payout"            => "payout",
            "gpn"               => "gpn"
        ];
    }



    /**
     * @param $payment
     * @return false|string
     */
    public function nicepayPaymentMethod($payment)
    {
        // method name => frontend routing
        $listPayment = $this->getNicepayPaymentList();
        $response = false;
        if (array_key_exists($payment, $listPayment)) {
            $response = $listPayment[$payment];
        }

        return $response;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return true;
    }

    /**
     * @param $date
     * @return false|string
     */
    protected function convertDateTime($date)
    {
        return gmdate(DATE_W3C, $date);
    }

    /**
     * Refactored function calls
     */
    public function getIsPaymentActive($code)
    {
        return $this->scopeConfig->getValue("payment/$code/active", ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param $code
     * @return mixed
     */
    public function getPaymentTitle($code)
    {
        return $this->scopeConfig->getValue("payment/$code/title", ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getActiveBanks()
    {
        $banks = $this->scopeConfig->getValue(
            'payment/virtual_account/active_banks',
            ScopeInterface::SCOPE_STORE
        );

        // For Magento 2.2+ with comma-separated values
        if (is_string($banks) && !empty($banks)) {
            return explode(',', $banks);
        }

        return [];
    }

    /**
     * @return mixed
     */
    public function getActiveMitra($code)
    {
        $mitras = $this->scopeConfig->getValue(
            "payment/$code/active_mitra",
            ScopeInterface::SCOPE_STORE
        );

        // For Magento 2.2+ with comma-separated values
        if (is_string($mitras) && !empty($mitras)) {
            return explode(',', $mitras);
        }

        return [];
    }

    /**
     * @param $code
     * @return mixed
     */
    public function getPaymentDescription($code)
    {
        return $this->scopeConfig->getValue("payment/$code/description", ScopeInterface::SCOPE_STORE);
    }






    /**
     * @param string $payment
     * @param string $currency
     * @return bool
     */
    public function isAvailableOnCurrency(string $payment, string $currency): bool
    {
        $paymentCurrencies = $this->scopeConfig->getValue('payment/' . $payment . '/currency', ScopeInterface::SCOPE_STORE);
        if (is_null($paymentCurrencies) || in_array($currency, array_map("trim", explode(',', $paymentCurrencies) ?? []))) {
            return true;
        }
        return true;
    }

    /**
     * @param $amount
     * @return false|float
     */
    public function truncateDecimal($amount)
    {
        return floor((float)$amount);
    }

    /**
     * @param Order $order
     * @return array
     */
    public function extractNicepayCustomerInfoFromOrder(Order $order): array
    {
        $shippingAddress = $order->getShippingAddress();
        $customerObject = [
            'given_names' => $order->getCustomerFirstname(),
            'surname' => $order->getCustomerLastname(),
            'email' => $order->getCustomerEmail()
        ];

        $mobileNumber = $this->phoneNumberFormatHelper->formatNumber($shippingAddress->getTelephone(), $shippingAddress->getCountryId());
        if (!empty($mobileNumber)) {
            $customerObject['mobile_number'] = $mobileNumber;
        }

        $customerObject = array_filter($customerObject);
        $addressObject = $this->extractNicepayCustomerAddress($shippingAddress);
        if (!empty($addressObject)) {
            $customerObject['addresses'] = [$addressObject];
        }
        return $customerObject;
    }

    /**
     * @param $shippingAddress
     * @return array
     */
    public function extractNicepayCustomerAddress($shippingAddress): array
    {
        if (empty($shippingAddress)) {
            return [];
        }

        $address = [
            'street_line1' => $shippingAddress->getData('street'),
            'city' => $shippingAddress->getData('city'),
            'state' => $shippingAddress->getData('region'),
            'postal_code' => $shippingAddress->getData('postcode'),
            'country' => $shippingAddress->getData('country_id')
        ];

        return array_filter($address);
    }

    /**
     * @param Product $product
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function extractProductCategoryName(Product $product): string
    {
        $categories = $product->getCategoryIds();
        $categoryNames = [];
        foreach ($categories as $categoryId) {
            try {
                $category = $this->categoryRepository->get($categoryId);
                $categoryNames[] = $category->getName();
            } catch (NoSuchEntityException $exception) {
            }
        }
        return !empty($categoryNames) ? implode(', ', $categoryNames) : 'n/a';
    }


    public function extractOrderFees(Order $order): array
    {
        $fees = [
            [
                'type' => __('Discount'),
                'value' => (float) $order->getDiscountAmount()
            ],
            [
                'type' => __('Shipping fee'),
                'value' => (float) $order->getShippingAmount()
            ],
            [
                'type' => __('Tax fee'),
                'value' => $order->getTaxAmount()
            ]
        ];

        // Make sure it will cover the other fees
        $otherFees = $this->getOtherFees($order);
        if ($otherFees > 0) {
            $fees[] = [
                'type' => __('Other Fees'),
                'value' => $this->getOtherFees($order)
            ];
        }

        return array_values(
            array_filter($fees, function ($value) {
                return $value['value'] != 0;
            }, ARRAY_FILTER_USE_BOTH)
        );
    }


    public function getOtherFees(Order $order): float
    {
        return $order->getTotalDue() - (float)array_sum(
            [
                $order->getSubtotal(), // items total
                $order->getTaxAmount(),
                $order->getShippingAmount(),
                $order->getDiscountAmount()
            ]
        );
    }

    /**
     * Merge Fees object
     *
     * @param array $feesObject
     * @return array
     */
    public function mergeFeesObject(array $feesObject = []): array
    {
        if (empty($feesObject)) {
            return [];
        }

        $mergedFeesObject = [];
        foreach ($feesObject as $feeObject) {
            foreach ($feeObject as $fee) {
                /** @var \Magento\Framework\Phrase $type */
                $type = $fee['type'];
                $typeLabel = $type->getText();
                $value = $fee['value'];

                if (isset($mergedFeesObject[$typeLabel])) {
                    $mergedFeesObject[$typeLabel] = (float)$mergedFeesObject[$typeLabel] + $value;
                } else {
                    $mergedFeesObject[$typeLabel] = $value;
                }
            }
        }

        if (empty($mergedFeesObject)) {
            return [];
        }

        $response = [];
        foreach ($mergedFeesObject as $typeLabel => $value) {
            $response[] = [
                'type' => $typeLabel,
                'value' => $value
            ];
        }
        return $response;
    }
}
