# magento-nicepay
NICEPay Library for Magento

## Description
This repository contains Magento plugins for integrating Nicepay payment methods into your Magento store. Each ZIP file contains a separate payment method module.

## Installation Guide

### Prerequisites
- Magento 2.x
- Composer
- SSH access to your Magento server

### Installation Steps

#### 1. Extract the ZIP File
- Choose the desired payment method ZIP file (e.g., `nicepay-Magento V1-VA-CC-CVS-202...zip` or `nicepay-Magento V2-Ewallet-202502...zip`).
- Extract the contents.

#### 2. Copy the Module to Magento
- Navigate to your Magento root directory.
- Copy the extracted module folder into `app/code/Nicepay/`.

```sh
cp -r extracted_folder app/code/Nicepay/PaymentMethod
```

#### 3. Enable the Module
Run the following Magento CLI commands:

```sh
php bin/magento setup:upgrade
php bin/magento cache:flush
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
```

#### 4. Configure the Payment Method
- Log in to your Magento Admin Panel.
- Navigate to `Stores > Configuration > Sales > Payment Methods`.
- Find the Nicepay payment method and configure Merchant credentials.
- Save the changes.

#### 5. Test the Integration
- Place a test order to ensure that the payment method is functioning correctly.

## Support
For any issues or inquiries, contact Nicepay support or refer to the official documentation.

