<?php

namespace Nicepay\NicePayment\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class PayoutActions extends Column
{
    protected $urlBuilder;

    const URL_PATH_APPROVE = 'nicepay/payout/approve';
    const URL_PATH_REJECT = 'nicepay/payout/reject';
    const URL_PATH_CANCEL = 'nicepay/payout/cancel';

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['entity_id'])) {
                    $id = $item['entity_id'];
                    $item[$this->getData('name')] = [
                        'approve' => [
                            'href' => $this->urlBuilder->getUrl(self::URL_PATH_APPROVE, ['id' => $id]),
                            'label' => __('Approve'),
                            'class' => 'actions-approve',
                            'confirm' => [
                                'title' => __('Approve Payout'),
                                'message' => __('Are you sure you want to approve transaction #%1?', $id)
                            ],
                            'post' => true, // Ensures POST request
                            'data' => [
                                'action' => 'approve' // Additional data for JS handler
                            ]
                        ],
                        'reject' => [
                            'href' => $this->urlBuilder->getUrl(self::URL_PATH_REJECT, ['id' => $id]),
                            'label' => __('Reject'),
                            'class' => 'actions-reject',
                            'confirm' => [
                                'title' => __('Reject Payout'),
                                'message' => __('Are you sure you want to reject transaction #%1?', $id)
                            ],
                            'post' => true,
                            'data' => [
                                'action' => 'reject'
                            ]
                        ],
                        'cancel' => [
                            'href' => $this->urlBuilder->getUrl(self::URL_PATH_CANCEL, ['id' => $id]),
                            'label' => __('Cancel'),
                            'class' => 'actions-cancel',
                            'confirm' => [
                                'title' => __('Cancel Payout'),
                                'message' => __('Are you sure you want to cancel transaction #%1?', $id)
                            ],
                            'post' => true,
                            'data' => [
                                'action' => 'cancel'
                            ]
                        ],
                    ];
                }
            }
        }
        return $dataSource;
    }
}
