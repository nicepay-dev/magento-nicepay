define([
    "Magento_Checkout/js/view/payment/default",
    "mage/url",
    "Magento_Checkout/js/model/quote",
], function (Component, url, quote) {
    "use strict";

    return Component.extend({
        defaults: {
            template: "Nicepay_NicePayment/payment/invoice",
            redirectAfterPlaceOrder: false,
        },

        initialize: function () {
            this._super();
        },

        getCode: function () {
            return "redirect";
        },

        getMethod: function () {
            return "REDIRECT";
        },

        getTest: function () {
            return "1";
        },

        getDescription: function () {
            return window.checkoutConfig.payment.redirect.description;
        },

        afterPlaceOrder: function () {
            console.log("afterPlaceOrder called");
            window.location.replace(
                url.build("nicepay/nicepayment/registration?payMethod=00")
            );
        },

        placeOrder: function (data, event) {
            if (event) {
                event.preventDefault();
            }

            if (!this.validate()) {
                return;
            }

            // Call parent's placeOrder, no Promise chaining
            this._super(data, event);

            // // Trigger redirect manually
            // this.afterPlaceOrder();
        },

        isPlaceOrderActionAllowed: function () {
            // Return true or base on validation logic
            return true; // for testing, enable button always
        },

        validate: function () {
            var billingAddress = quote.billingAddress();

            this.messageContainer.clear();

            if (!billingAddress) {
                this.messageContainer.addErrorMessage({
                    message: "Please enter your billing address",
                });
                return false;
            }

            return true;
        },

        getTestDescription: function () {
            return {
                prefix: "Note: ",
                content: "Redirect sandbox environment",
            };
        },
    });
});
