/**
 * PlatiOnline payment payments javascritp
 *
 * @category    Xtreme
 * @package     Xtreme_PlatiOnline
 * @author      Marian-Daniel Ursache <marian.ursache@gmail.com>, Michael Mussulis <michael@xtreme-vision.net>
 * @copyright   Xtreme Vision SRL
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 *   Payment Redirect Method
 */
/*browser:true*/
/*global define*/
define(
[
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/action/place-order',
    'Magento_Checkout/js/action/select-payment-method',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/payment/additional-validators',
    'mage/url',
],
function (
    $,
    Component,
    placeOrderAction,
    selectPaymentMethodAction,
    customer,
    checkoutData,
    additionalValidators,
    url) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Xtreme_PlatiOnline/payment/plationline-form'
            },
            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }
                var self = this,
                    placeOrder,
                    emailValidationResult = customer.isLoggedIn(),
                    loginFormSelector = 'form[data-role=email-with-possible-login]';
                if (!customer.isLoggedIn()) {
                    $(loginFormSelector).validation();
                    emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
                }
                if (emailValidationResult && this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    placeOrder = placeOrderAction(this.getData(), false, this.messageContainer);

                    $.when(placeOrder).fail(function () {
                        self.isPlaceOrderActionAllowed(true);
                    }).done(this.afterPlaceOrder.bind(this));
                    return true;
                }
                return false;
            },

            selectPaymentMethod: function() {
                selectPaymentMethodAction(this.getData());
                checkoutData.setSelectedPaymentMethod(this.item.method);
                return true;
            },

            afterPlaceOrder: function () {
                this.selectPaymentMethod();
                 window.location.replace(url.build('plationline/payment/redirect/'));
            },
            /** Returns send check to info */
            getImage: function() {
                return window.checkoutConfig.payment.platiOnline.logo;
            },
            getInstructions() {
                return $.mage.__("You will be redirected to PlatiOnline page");
            }

        });
    }
);
