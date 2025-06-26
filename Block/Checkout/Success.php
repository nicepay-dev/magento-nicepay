<?php

namespace Nicepay\NicePayment\Block\Checkout;

use Magento\Framework\View\Element\Template;
use Magento\Framework\App\RequestInterface;
use Nicepay\NicePayment\Model\Ui\ConfigProvider;

class Success extends Template
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    public function __construct(
        Template\Context $context,
        RequestInterface $request,
        ConfigProvider $configProvider,
        array $data = []
    ) {
        $this->request = $request;
        $this->configProvider = $configProvider;
        parent::__construct($context, $data);
    }

    public function getRegistrationData()
    {
        // Get all params sent in request (GET or POST)
        $data = $this->request->getParams();

        // Optional: remove controller, action, module params if present
        unset($data['route']);
        unset($data['controller']);
        unset($data['action']);
        unset($data['module']);

        return $data ?: null;
    }

    public function getBankList()
    {
        return $this->configProvider->bankList();
    }

    public function getConvenienceStoreList()
    {
        return $this->configProvider->convenienceStoreList();
    }
}
