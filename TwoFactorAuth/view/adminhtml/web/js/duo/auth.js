/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */

define([
    'ko',
    'uiComponent'
], function (ko, Component) {
    'use strict';

    return Component.extend({
        currentStep: ko.observable('register'),

        defaults: {
            template: 'Magento_TwoFactorAuth/duo/auth'
        },

        authUrl: '',

        getAuthUrl: function () {
            return this.authUrl;
        },

        redirectToAuthUrl: function () {
            var redirectUrl = this.getAuthUrl();

            if (redirectUrl) {
                window.location.href = redirectUrl;
            }
        },

        /**
         * After the element is rendered, bind the authUrl (optional)
         */
        onAfterRender: function () {
            // Not Required
        }
    });
});
