# NICEPAY Payment Gateway for Magento 2

NICEPAY ❤️ Magento 2!
&#x20;

Official NICEPAY payment gateway module for Magento 2. This extension enables merchants to accept a wide range of payments securely and efficiently through NICEPAY’s trusted payment APIs.

---

## 🚀 Features

-   Seamless integration with SNAP and V2 NICEPAY APIs
-   Secure transaction handling and logging
-   Full support for sandbox (test) and production environments
-   Modular payment method support (enable what you need)
-   Configuration directly from Magento Admin
-   Admin-side payout approval tools
-   Built-in support for both SNAP and Non-SNAP notifications

---

## 💳 Supported Payment Methods

### SNAP API

-   **Virtual Account** (14+ Indonesian banks)
-   **E-Wallet** (Dana, OVO, ShopeePay, LinkAja)
-   **QRIS**
-   **Payout / Disbursement**

### V2 API

-   **Credit/Debit Card**
-   **Virtual Account** (same as SNAP)
-   **Convenience Store** (Alfa Group, Indomaret)
-   **E-Wallet** (Dana, OVO, ShopeePay, LinkAja)
-   **Payloan** (Akulaku, Kredivo, Indodana)
-   **Payout / Disbursement**
-   **QRIS**
-   **Redirect to NICEPAY Payment Page**

---

## ⚙️ Installation

### Method 1: Composer Installation (Recommended)

```bash
composer require nicepay/magento-nicepay
```

### Method 2: Manual Installation

1. Download the module from [Github](https://github.com/nicepay-dev/magento-nicepay)
2. Extract into: `app/code/Nicepay/NicePayment`

Then run:

```bash
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
```

---

## 🔧 Configuration

After installing the module, configure it from:

```
Admin Panel > Stores > Configuration > Sales > Payment Methods > NICEPAY
```

### General Configuration Options

-   **Title**: Display name of the payment method
-   **Debug Mode**: Enable request/response logs
-   **Active**: Enable or disable the method
-   **Merchant ID**: From NICEPAY
-   **Environment**: `dev` or `prod`
-   **API Version**: `v2` or `snap`
-   **Merchant Key / Client Secret**: Stored securely
-   **Minimum / Maximum Order Amount**: Restrict availability by order value

### Additional Method-Specific Options

-   **Virtual Account**: Select supported banks
-   **QRIS**: `mitra_cd` (partner code), `store_id`
-   **Card Payments**: Configure 3DS and secure fields
-   **Convenience Store**: Select retailer type (Alfa, Indomaret)

---

## 💼 Admin Features

### Payout Management Interface

A dedicated **Payout Transaction** menu is included in the Magento Admin Panel:

```
Admin Panel > NICEPAY MENU > Payout Transaction
```

This tool enables merchants to:

-   ✅ Approve payout requests
-   ✅ Reject payout requests
-   ✅ Cancel pending payouts

Useful for managing refunds, vendor payments, or custom disbursement logic.

---

## 🚚 Usage Flow

1. Customer selects a NICEPAY payment method during checkout
2. System routes to the appropriate payment experience:
    - **Virtual Account**: Bank transfer instructions shown
    - **Convenience Store**: Convenience store payment instructions shown
    - **E-Wallet**: Redirect to wallet app/web, for ovo you will receive notification on customer OVO app
    - **QRIS**: QR code displayed
    - **Payloan**: Redirect to payloan web/app
    - **Card**: Secure form handled via NICEPAY
3. After the customer completes the payment, Magento receives a server-to-server notification from NICEPAY to update the transaction status automatically. The customer is then redirected back to the store for final confirmation.

---

## 🔄 Notification & Callback Handling

The module supports both **SNAP** and **Non-SNAP** notifications. Each category of payment method has a dedicated endpoint.

### Supported Routes

-   **Virtual Account (SNAP)**: `/notification/api/v1.0/transfer-va/payment`
-   **QRIS (SNAP)**: `/notification/api/v1.0/qr/qr-mpm-notify`
-   **E-Wallet (SNAP)**: `/notification/api/v1.0/debit/notify`
-   **Payout (SNAP)**: `/notification/api/v1.0/debit/notify`
-   **All Non-Snap PayMethod**: `/nicepay/nicepayment/notification`

These endpoints are automatically routed to their respective controller actions via a custom router class. No extra route configuration is needed on your end.

---

## 📑 Resources

-   [NICEPAY Documentation](https://docs.nicepay.co.id/)
-   [Repository](https://github.com/nicepay-dev/magento-nicepay)
-   [NICEPAY Dashboard](https://bo.nicepay.co.id/)

---

_Built and maintained with ❤️ by the NICEPAY Team._
