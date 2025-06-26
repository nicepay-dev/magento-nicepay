<?php

namespace Nicepay\NicePayment\Ui\DataProvider\Payout;

use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Psr\Log\LoggerInterface;


class ListingDataProvider extends DataProvider
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Collection
     */
    protected $collection;


    // ...

    private $logger;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        CollectionFactory $collectionFactory,
        LoggerInterface $logger,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        $collection = $this->getCollection();

        // Get pagination parameters from request
        $requestName = $this->request->getParam('namespace');
        if ($requestName === 'nicepay_payout_listing') {
            $pageSize = $this->request->getParam('paging')['pageSize'] ?? 20;
            $currentPage = $this->request->getParam('paging')['current'] ?? 1;

            $collection->setPageSize($pageSize);
            $collection->setCurPage($currentPage);
        }

        // Convert collection to array and remove any numeric indexes
        $items = $collection->toArray();
        $cleanItems = isset($items['items']) ? $items['items'] : array_values($items);

        $arrItems = [
            'totalRecords' => $collection->getSize(),
            'items' => $cleanItems
        ];

        $this->logger->debug('Grid Data: ', $arrItems);
        return $arrItems;
    }

    /**
     * @return Collection
     */
    public function getCollection()
    {
        if (!$this->collection) {
            $this->collection = $this->collectionFactory->create();

            // Select specific fields
            $this->collection->addFieldToSelect([
                'entity_id',
                'increment_id',
                'nicepay_txid',
                'nicepay_payment_method',
                'nicepay_payment_status',
                'grand_total',
                'created_at'
            ]);

            // Apply filters
            $this->collection->addAttributeToFilter('nicepay_txid', ['notnull' => true]);
            $this->collection->addAttributeToFilter('nicepay_payment_method', ['eq' => '07']);

            // Set default sorting
            $this->collection->setOrder('created_at', 'DESC');
        }
        return $this->collection;
    }

    /**
     * @inheritdoc
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        $field = $filter->getField();

        // Handle special cases for fields
        if (in_array($field, ['nicepay_payment_method', 'nicepay_payment_status'])) {
            $this->getCollection()->addFieldToFilter($field, ['eq' => $filter->getValue()]);
            return;
        }

        if ($field === 'grand_total') {
            $condition = $filter->getConditionType() ?: 'eq';
            $this->getCollection()->addFieldToFilter($field, [$condition => $filter->getValue()]);
            return;
        }

        if ($field === 'increment_id') {
            $this->getCollection()->addFieldToFilter($field, ['like' => '%' . $filter->getValue() . '%']);
            return;
        }

        parent::addFilter($filter);
    }
}
