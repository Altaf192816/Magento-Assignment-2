define(
    [
        'Magento_Checkout/js/view/payment/default'
    ],
    function (Component) {
        'use strict';

        const customPaymentConfig = window.checkoutConfig.payment.custompayment;
        console.log(window.checkoutConfig.payment.custompayment);

        return Component.extend({
            defaults: {
                template: 'NewMagentoAssignment_Wallet/payment/customtemplate'
            },

            getTitle: function () {
                return customPaymentConfig && customPaymentConfig.title ? customPaymentConfig.title : 'Wallet Payment';
            },

            isActive: function () {
                return customPaymentConfig && customPaymentConfig.active;
            },
            getWalletBalance: function () {
                return customPaymentConfig.walletBalance;
            }
        });
    }
);
