define([
    "uiComponent",
    "Magento_Checkout/js/model/payment/renderer-list",
], function (Component, rendererList) {
    "use strict";
    rendererList.push(
        {
            type: "card",
            component:
                "Nicepay_NicePayment/js/view/payment/method-renderer/card",
        },
        {
            type: "virtual_account",
            component:
                "Nicepay_NicePayment/js/view/payment/method-renderer/virtualaccount",
        },
        {
            type: "cvs",
            component:
                "Nicepay_NicePayment/js/view/payment/method-renderer/cvs",
        },
        {
            type: "qris",
            component:
                "Nicepay_NicePayment/js/view/payment/method-renderer/qris",
        },
        {
            type: "ewallet",
            component:
                "Nicepay_NicePayment/js/view/payment/method-renderer/ewallet",
        },
        {
            type: "payout",
            component:
                "Nicepay_NicePayment/js/view/payment/method-renderer/payout",
        },
        {
            type: "payloan",
            component:
                "Nicepay_NicePayment/js/view/payment/method-renderer/payloan",
        },
        {
            type: "redirect",
            component:
                "Nicepay_NicePayment/js/view/payment/method-renderer/redirect",
        }
    );
    return Component.extend({});
});
