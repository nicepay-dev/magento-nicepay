<?php

namespace Nicepay\NicePayment\Helper;

use Magento\Framework\Controller\Result\JsonFactory;

class ApiResponse
{
    protected $jsonFactory;

    public function __construct(JsonFactory $jsonFactory)
    {
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * Create standardized API response
     */
    public function createResponse(
        array $data,
        int $httpCode = 200
    ) {
        $result = $this->jsonFactory->create();

        $response = $data;

        return $result->setData($response)
            ->setHttpResponseCode($httpCode);
    }

    /**
     * Quick error response
     */
    public function createErrorResponse(
        array $data = [],
        int $httpCode = 400
    ) {
        return $this->createResponse(
            $data,
            $httpCode
        );
    }
}
