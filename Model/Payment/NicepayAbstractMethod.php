<?php

namespace Nicepay\NicePayment\Model\Payment;

use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\Logger;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\SalesRule\Model\RuleRepository;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Serialize\Serializer\Json as MagentoSerializerJson;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Customer\Model\Session as CustomerSession;



class NicepayAbstractMethod extends AbstractMethod
{
    /**
     * @var
     */
    protected $_minAmount;

    /**
     * @var
     */
    protected $_maxAmount;

    /**
     * @var
     */
    protected $methodCode;

    protected $dataHelper;



    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $cache;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var RuleRepository
     */
    protected $ruleRepo;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var MagentoSerializerJson
     */
    protected $magentoSerializerJson;



    protected $serializer;

    /**
     * @var CustomerSession
     */
    protected $customerSession;



    /**
     * AbstractInvoice constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param \Nicepay\NicePayment\Helper\Data $dataHelper
     * @param StoreManagerInterface $storeManager
     * @param RuleRepository $ruleRepo
     * @param CartRepositoryInterface $quoteRepository
     * @param MagentoSerializerJson $magentoSerializerJson
     * @param CustomerSession $customerSession
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        \Nicepay\NicePayment\Helper\Data $dataHelper,
        StoreManagerInterface $storeManager,
        RuleRepository $ruleRepo,
        CartRepositoryInterface $quoteRepository,
        MagentoSerializerJson $magentoSerializerJson,
        CustomerSession $customerSession,
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger
        );

        $this->dataHelper               = $dataHelper;
        $this->cache                    = $context->getCacheManager();
        $this->storeManager             = $storeManager;
        $this->ruleRepo                 = $ruleRepo;
        $this->quoteRepository          = $quoteRepository;
        $this->magentoSerializerJson    = $magentoSerializerJson;
        $this->customerSession          = $customerSession;
        $this->serializer               = $this->magentoSerializerJson;
        $this->_logger = $logger;
    }

    /**
     * @param CartInterface|null $quote
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isAvailable(CartInterface $quote = null)
    {
        if ($quote === null || !$this->dataHelper->getIsActive() || !$this->dataHelper->getIsPaymentActive($this->_code)) {
            return false;
        }

        $amount = ceil($quote->getSubtotal() + $quote->getShippingAddress()->getShippingAmount());

        if ($this->dataHelper->getIsUsingMinAmount($this->_code) && $amount < $this->dataHelper->getMinAmount($this->_code)) {
            return false;
        }
        if ($this->dataHelper->getIsUsingMaxAmount($this->_code) && $amount > $this->dataHelper->getMaxAmount($this->_code)) {
            return false;
        }
        return true;
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
    protected function getNicepayCallbackUrl()
    {
        $baseUrl = $this->getStoreManager()->getStore()->getBaseUrl(UrlInterface::URL_TYPE_LINK);
        return $baseUrl . 'nicepay/checkout/notification';
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getCurrency()
    {
        return $this->storeManager->getStore()->getCurrentCurrencyCode();
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isAvailableOnCurrency()
    {
        return $this->dataHelper->isAvailableOnCurrency($this->_code, $this->getCurrency());
    }

    public function getPaymentConfig($field, $storeId = null)
    {
        return $this->dataHelper->getPaymentConfig($this->_code, $field, $storeId);
    }
}
