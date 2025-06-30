<?php

namespace Nicepay\NicePayment\Plugin;

use Magento\Config\Model\Config\Structure\Element\Group;
use Magento\Store\Model\StoreManagerInterface;

class ConfigElementGroupPlugin
{
    const PAYMENT_GROUP = [
        'virtual_account',
        'qris',
        'ewallet',
        'payout',
        'card',
        'cvs',
        'payloan',
        'redirect',
    ];

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Nicepay\NicePayment\Helper\Data
     */
    protected $helper;

    /**
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Nicepay\NicePayment\Helper\Data $helper
     */
    public function __construct(
        StoreManagerInterface                   $storeManager,
        \Magento\Framework\App\RequestInterface $request,
        \Nicepay\NicePayment\Helper\Data           $helper
    ) {
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->helper = $helper;
    }

    /**
     * @return \Magento\Store\Api\Data\WebsiteInterface|string|void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrentCurrency()
    {
        if (!empty($this->request->getParam('store'))) {
            return $this->storeManager->getStore((int)$this->request->getParam('store'))
                ->getCurrentCurrency()
                ->getCode();
        }
        if (!empty($this->request->getParam('website'))) {
            return $this->storeManager->getWebsite((int)$this->request->getParam('website'))
                ->getDefaultStore()
                ->getCurrentCurrency()
                ->getCode();
        }

        return $this->storeManager->getStore()->getCurrentCurrency()->getCode();
    }

    /**
     * @param Group $group
     * @param $result
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterIsVisible(Group $group, $result)
    {
        if (strpos($group->getPath(), 'nicepay') !== false) {
            if (in_array($group->getId(), self::PAYMENT_GROUP) && !$group->hasChildren()) {
                return false;
            }

            if ($this->helper->nicepayPaymentMethod($group->getId())) {
                return $this->helper->isAvailableOnCurrency($group->getId(), $this->getCurrentCurrency());
            }
        }
        return $result;
    }
}
