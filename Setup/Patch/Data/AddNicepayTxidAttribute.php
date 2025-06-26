<?php

namespace Nicepay\NicePayment\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Setup\SalesSetupFactory;

class AddNicepayTxidAttribute implements DataPatchInterface
{
    /**
     * @var SalesSetupFactory
     */
    private $salesSetupFactory;

    /**
     * @param SalesSetupFactory $salesSetupFactory
     */
    public function __construct(
        SalesSetupFactory $salesSetupFactory
    ) {
        $this->salesSetupFactory = $salesSetupFactory;
    }

    public function apply()
    {
        /**
         * Add 'nicepay_txid' attributes for order
         */
        $salesSetup = $this->salesSetupFactory->create();
        $salesSetup->addAttribute('order', 'nicepay_txid', [
            'type' => 'varchar',
            'visible' => false,
            'required' => false
        ]);

        return $this;
    }

    public function getAliases()
    {
        return [];
    }

    public static function getDependencies()
    {
        return [];
    }
}
