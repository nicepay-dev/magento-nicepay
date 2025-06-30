define([
    "Magento_Checkout/js/view/payment/default",
    "mage/url",
    "Magento_Checkout/js/model/quote",
    "ko",
], function (Component, url, quote, ko) {
    "use strict";

    return Component.extend({
        defaults: {
            template: "Nicepay_NicePayment/payment/invoice",
            redirectAfterPlaceOrder: false,
            selectedMitra: ko.observable(null),
        },

        initialize: function () {
            this._super();

            // additionalInfo is an associative object { BMRI: {...}, BBBA: {...}, ... }
            this.paymentConfig = window.checkoutConfig.payment[this.getCode()];

            this.additionalInfo = this.paymentConfig.additionalInfo || {};

            // Set default selected bank (first key)
            var keys = Object.keys(this.additionalInfo);
            if (keys.length > 0) {
                this.selectedMitra(keys[0]);
            }

            return this;
        },

        getCode: function () {
            return "payloan";
        },

        getMethod: function () {
            return "payloan";
        },

        getTest: function () {
            return "1";
        },

        getDescription: function () {
            return window.checkoutConfig.payment.payloan.description;
        },

        getTestDescription: function () {
            return {
                prefix: "Note: ",
                content: "Payloan payment sandbox environment",
            };
        },

        isPlaceOrderActionAllowed: function () {
            return this.selectedMitra() !== null;
        },

        afterPlaceOrder: function () {
            console.log("afterPlaceOrder called");
            var mitra = this.selectedMitra() || "";
            var redirectUrl = url.build(
                "nicepay/nicepayment/registration?payMethod=06&mitraCd=" + mitra
            );
            window.location.replace(redirectUrl);
        },

        placeOrder: function (data, event) {
            console.log("placeOrder called");
            if (event) {
                event.preventDefault();
            }

            if (!this.validate()) {
                return;
            }

            // Call parent's placeOrder, no Promise chaining
            this._super(data, event);

            // Trigger redirect manually
            // this.afterPlaceOrder();
        },

        validate: function () {
            var billingAddress = quote.billingAddress();
            this.messageContainer.clear();

            // Add validation if needed, e.g. ensure bank selected
            if (this.getCode() === "payloan" && !this.selectedMitra()) {
                alert("Please select a bank.");
                return false;
            }

            if (!billingAddress) {
                this.messageContainer.addErrorMessage({
                    message: "Please enter your billing address",
                });
                return false;
            }
            return true;
        },
    });
});
