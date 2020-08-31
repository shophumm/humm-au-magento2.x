/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/url-builder',
        'mage/url',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Humm_HummPaymentGateway/js/action/form-builder',
    ],
    function (
        $,
        Component,
        urlBuilder,
        url,
        quote,
        customerData,
        errorProcessor,
        fullScreenLoader,
        formBuilder
    ) {
        'use strict';

        var self = this;

        return Component.extend({
            redirectAfterPlaceOrder: false,

            defaults: {
                template: 'Humm_HummPaymentGateway/payment/form',
            },

            initialize: function () {
                this._super();
                self = this;
            },

            getCode: function () {
                return 'humm_gateway';
            },

            getData: function () {
                return {
                    'method': this.item.method
                };
            },
            disableButton: function () {
                // stop any previous shown loaders
                fullScreenLoader.stopLoader(true);
                fullScreenLoader.startLoader();
                $('[data-button="place"]').attr('disabled', 'disabled');
            },

            /**
             * Enable submit button
             */
            enableButton: function () {
                $('[data-button="place"]').removeAttr('disabled');
                fullScreenLoader.stopLoader();
            },

            afterPlaceOrder: function (event) {
                console.log("Redirect humm payment..");
                this.disableButton();
                self.isPlaceOrderActionAllowed(false);
                if (event) {
                    event.preventDefault();
                }
                var body = $('body').loader();
                body.loader('show');
                let hummControllerUrl = url.build('humm/checkout/index');
                $.post(hummControllerUrl, 'json').done(function (response) {
                    $('[data-button="place"]').attr('disabled', 'disabled');
                    formBuilder(response).submit();
                })
                    .fail(function (response) {
                        errorProcessor.process(response, this.messageContainer);
                    })
                    .always(function () {
                        body.loader('hide');
                    });
                return true;
            },
            validate: function () {
                var billingAddress = quote.billingAddress();
                var shippingAddress = quote.shippingAddress();
                var allowedCountries = self.getAllowedCountries();
                var orderMinVal = parseInt(self.getHummOrderValue());
                var totals = quote.totals();
                var allowedCountriesArray = [];

                if (typeof (allowedCountries) == 'string' && allowedCountries.length > 0) {
                    allowedCountriesArray = allowedCountries.split(',');
                }

                self.messageContainer.clear();

                if (!billingAddress) {
                    self.messageContainer.addErrorMessage({'message': 'Please enter your billing address'});
                    return false;
                }


                if (!billingAddress.firstname ||
                    !billingAddress.lastname ||
                    !billingAddress.street ||
                    !billingAddress.city ||
                    !billingAddress.postcode ||
                    billingAddress.firstname.length == 0 ||
                    billingAddress.lastname.length == 0 ||
                    billingAddress.street.length == 0 ||
                    billingAddress.city.length == 0 ||
                    billingAddress.postcode.length == 0) {
                    self.messageContainer.addErrorMessage({'message': 'Please enter your billing address details'});
                    return false;
                }

                if (allowedCountriesArray.indexOf(billingAddress.countryId) == -1 ||
                    allowedCountriesArray.indexOf(shippingAddress.countryId) == -1) {
                    self.messageContainer.addErrorMessage({'message': 'Orders from this country are not supported by Humm. Please select a different payment option.'});
                    return false;
                }

                if (totals.grand_total < orderMinVal) {
                    self.messageContainer.addErrorMessage({'message': 'Humm doesn\'t support purchases less than $' + orderMinVal});
                    return false;
                }

                return true;
            },

            getTitle: function () {
                return window.checkoutConfig.payment.humm_gateway.title;
            },

            getDescription: function () {
                return window.checkoutConfig.payment.humm_gateway.description;
            },

            getHummLogo: function () {
                var logo = window.checkoutConfig.payment.humm_gateway.logo;

                return logo;
            },

            getHummOrderValue: function () {
                return window.checkoutConfig.payment.humm_gateway.order_min_value;
            },
            getAllowedCountries: function () {
                return window.checkoutConfig.payment.humm_gateway.allowed_countries;
            }

        });
    });
