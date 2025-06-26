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
            selectedBank: ko.observable(null),
        },

        initialize: function () {
            this._super();

            // additionalInfo is an associative object { BMRI: {...}, BBBA: {...}, ... }
            this.paymentConfig = window.checkoutConfig.payment[this.getCode()];

            this.additionalInfo = this.paymentConfig.additionalInfo || {};

            // Set default selected bank (first key)
            var keys = Object.keys(this.additionalInfo);
            if (keys.length > 0) {
                this.selectedBank(keys[0]);
            }

            return this;
        },

        getCode: function () {
            return "virtual_account";
        },

        getMethod: function () {
            return "VA";
        },

        getTest: function () {
            return "1";
        },

        getDescription: function () {
            return window.checkoutConfig.payment.virtual_account.description;
        },

        getTestDescription: function () {
            return {
                prefix: "Note: ",
                content: "Virtual Account payment sandbox environment",
            };
        },

        isPlaceOrderActionAllowed: function () {
            return this.selectedBank() !== null;
        },

        afterPlaceOrder: function () {
            console.log("afterPlaceOrder called");
            var bankCd = this.selectedBank() || "";
            var redirectUrl = url.build(
                "nicepay/nicepayment/registration?payMethod=02&bankCd=" + bankCd
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

        // placeOrder: function () {
        //     if (!this.validate()) {
        //         return;
        //     }

        //     window.location.replace(redirectUrl);
        // },
        // validate: function () {
        //     var billingAddress = quote.billingAddress();
        //     this.messageContainer.clear();

        //     if (!billingAddress) {
        //         this.messageContainer.addErrorMessage({
        //             message: "Please enter your billing address",
        //         });
        //         return false;
        //     }

        //     return true;
        // },
        validate: function () {
            var billingAddress = quote.billingAddress();
            this.messageContainer.clear();

            // Add validation if needed, e.g. ensure bank selected
            if (this.getCode() === "virtual_account" && !this.selectedBank()) {
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
