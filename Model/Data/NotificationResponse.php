<?php

namespace Nicepay\NicePayment\Model\Data;

use Nicepay\NicePayment\Api\Data\NotificationResponseInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

class NotificationResponse extends AbstractExtensibleModel  implements NotificationResponseInterface
{
    public function getResponseCode(): string
    {
        return $this->getData(self::responseCode);
    }

    public function setResponseCode($code)
    {
        return $this->setData(self::responseCode, $code);
    }

    public function getResponseMessage(): string
    {
        return $this->getData(self::responseMessage);
    }

    public function setResponseMessage($message): NotificationResponseInterface
    {
        return $this->setData(self::responseMessage, $message);
    }
}
