<?php

namespace Nicepay\NicePayment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\ResourceConnection;

class SyncNicepayData implements ObserverInterface
{
    protected $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('sales_order_grid');

        $data = [
            'nicepay_txid' => $order->getData('nicepay_txid'),
            'nicepay_payment_method' => $order->getData('nicepay_payment_method'),
            'nicepay_payment_status' => $order->getData('nicepay_payment_status'),
        ];

        // Log to verify that data is being synced
        $logger = \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class);
        $logger->info('Syncing Nicepay Data to sales_order_grid', ['order_id' => $order->getId(), 'data' => $data]);

        // Update the data in sales_order_grid
        $connection->update(
            $table,
            $data,
            ['entity_id = ?' => $order->getId()]
        );
    }
}
