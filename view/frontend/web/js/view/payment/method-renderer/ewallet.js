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
            phoneNo: ko.observable(""),
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
            return "ewallet";
        },

        getMethod: function () {
            return "Ewallet";
        },

        getTest: function () {
            return "1";
        },

        getDescription: function () {
            return window.checkoutConfig.payment.ewallet.description;
        },

        getTestDescription: function () {
            return {
                prefix: "Note: ",
                content: "Ewallet payment sandbox environment",
            };
        },

        isPlaceOrderActionAllowed: function () {
            return this.selectedMitra() !== null;
        },

        afterPlaceOrder: function () {
            console.log("afterPlaceOrder called");
            var mitra = this.selectedMitra() || "";
            var phoneNo = this.phoneNo() || ""; // make sure this matches your observable name
            var redirectUrl = url.build(
                "nicepay/nicepayment/registration?payMethod=05&mitraCd=" +
                    mitra +
                    "&phoneNo=" +
                    phoneNo
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
            if (this.getCode() === "ewallet" && !this.selectedMitra()) {
                alert("Please select a bank.");
                return false;
            }

            if (this.getCode() === "ewallet" && !this.phoneNo()) {
                alert("Please Fill Phone Number");
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
