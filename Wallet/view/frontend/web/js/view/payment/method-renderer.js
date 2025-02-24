define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list',
        'Magento_Customer/js/customer-data'
    ],
    function (
        Component,
        rendererList,
    ) {
        'use strict';
            rendererList.push(
                {
                    type: 'custompayment',
                    component: 'NewMagentoAssignment_Wallet/js/view/payment/method-renderer/custompayment'
                }
            );

        return Component.extend({});
    }
);
