/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'uiComponent',
    'Magento_TwoFactorAuth/js/duo/api'
], function (ko, Component, duo) {
    'use strict';

    return Component.extend({
        currentStep: ko.observable('register'),

        defaults: {
            template: 'Magento_TwoFactorAuth/duo/auth'
        },

        redirectUrl: '',
        authenticateData: {},

        /**
         * Start waiting loop
         */
        onAfterRender: function () {
            window.setTimeout(function () {
                duo(this, null);
            }, 1000);
        },
    });
});
