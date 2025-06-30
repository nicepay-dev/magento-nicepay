<?php

namespace Nicepay\NicePayment\Setup\Patch\Schema;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;

class AddNicepayColumnsToSalesOrderGrid implements SchemaPatchInterface
{
    private $moduleDataSetup;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply()
    {
        $setup = $this->moduleDataSetup;
        $setup->startSetup();

        // Add columns if not exist
        $connection = $setup->getConnection();
        $tableName = $setup->getTable('sales_order_grid');

        if (!$connection->tableColumnExists($tableName, 'nicepay_txid')) {
            $connection->addColumn(
                $tableName,
                'nicepay_txid',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 30,
                    'nullable' => true,
                    'comment' => 'Nicepay Transaction Id',
                ]
            );
        }

        if (!$connection->tableColumnExists($tableName, 'nicepay_payment_method')) {
            $connection->addColumn(
                $tableName,
                'nicepay_payment_method',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 2,
                    'nullable' => true,
                    'comment' => 'Nicepay Payment Method',
                ]
            );
        }

        if (!$connection->tableColumnExists($tableName, 'nicepay_payment_status')) {
            $connection->addColumn(
                $tableName,
                'nicepay_payment_status',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 2,
                    'nullable' => true,
                    'comment' => 'Nicepay Payment Status',
                ]
            );
        }

        if (!$connection->tableColumnExists($tableName, 'nicepay_beneficiary_account_no')) {
            $connection->addColumn(
                $tableName,
                'nicepay_beneficiary_account_no',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 20,
                    'nullable' => true,
                    'comment' => 'Nicepay Payout Account Number',
                ]
            );
        }

        $setup->endSetup();
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
