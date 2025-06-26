<?php

namespace Nicepay\NicePayment\Helper;

use Magento\Framework\App\RequestInterface;

class Security
{
    protected $whitelistedIps = [
        '103.20.51.0/24',
        '103.117.8.0/24',
        '103.20.51.34/32'
    ];

    public function validateNotificationRequest(RequestInterface $request): bool
    {
        // Uncomment to only allow requests from whitelisted IPs
        // IP Whitelisting
        // if (!$this->isIpWhitelisted($request->getClientIp())) {
        //     return false;
        // }

        return true;
    }

    protected function isIpWhitelisted(string $ip): bool
    {
        foreach ($this->whitelistedIps as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }
        return false;
    }



    protected function ipInRange(string $ip, string $range)
    {
        // Implementation for IP range checking
    }
}
