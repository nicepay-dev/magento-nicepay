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
            accountNo: ko.observable(""),
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
            return "payout";
        },

        getMethod: function () {
            return "payout";
        },

        getTest: function () {
            return "1";
        },

        getDescription: function () {
            return window.checkoutConfig.payment.payout.description;
        },

        getTestDescription: function () {
            return {
                prefix: "Note: ",
                content: "Payout sandbox environment",
            };
        },

        isPlaceOrderActionAllowed: function () {
            return this.selectedBank() !== null;
        },

        afterPlaceOrder: function () {
            console.log("afterPlaceOrder called");
            var bankCd = this.selectedBank() || "";
            var accountNo = this.accountNo() || "";

            var redirectUrl = url.build(
                "nicepay/nicepayment/registration?payMethod=07&bankCd=" +
                    bankCd +
                    "&accountNo=" +
                    accountNo
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
            if (this.getCode() === "payout" && !this.selectedBank()) {
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
