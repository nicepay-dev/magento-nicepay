<?php

namespace Nicepay\NicePayment\Model\Payment;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;


class Nicepay extends AbstractMethod
{
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'nicepay';

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var OrderRepository
     */
    private $orderRepository;


    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param DirectoryHelper $directory
     * @param OrderRepository $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        DirectoryHelper $directory,
        OrderRepository $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            null,
            null,
            [],
            $directory
        );
    }





    /**
     * @return mixed
     */
    public function getAllowedMethod()
    {
        return $this->getConfigData('allowed_method');
    }


    /**
     * @return mixed
     */
    public function getIsActive()
    {
        return $this->getConfigData('active');
    }

    public function getIsUsingMinAmount()
    {
        return $this->getConfigData('use_min_amount');
    }

    public function getIsUsingMaxAmount()
    {
        return $this->getConfigData('use_max_amount');
    }

    public function getMinAmount()
    {
        return $this->getConfigData('min_amount');
    }

    public function getMaxAmount()
    {
        return $this->getConfigData('max_amount');
    }


    /**
     * Get order(s) by TransactionId
     *
     * @param string $tXid
     * @return array
     */
    public function getOrderIdsByTransactionId(string $tXid): array
    {
        $this->searchCriteriaBuilder->addFilter('nicepay_txid', $tXid);
        $orders = $this->orderRepository->getList($this->searchCriteriaBuilder->create());

        if (!$orders->getTotalCount()) {
            return [];
        }

        return array_map(function (OrderInterface $order) {
            return $order->getId();
        }, $orders->getItems());
    }

    /**
     * @param string $incrementId
     * @return OrderInterface
     * @throws NoSuchEntityException
     */
    public function getOrderByIncrementId(string $incrementId): OrderInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            'main_table.' . OrderInterface::INCREMENT_ID,
            $incrementId
        )->create();

        if (!($orderItems = $this->orderRepository->getList($searchCriteria)->getItems())) {
            throw new NoSuchEntityException(__('Requested order doesn\'t exist'));
        }

        return reset($orderItems);
    }
}
