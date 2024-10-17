/**
 * Duo Web SDK v2
 * Copyright 2017, Duo Security
 */

define([], function () {
    'use strict';

    return function (config, element) {
        var redirectUrl = config.components['tfa-auth'].redirectUrl;
        if (window.location.href !== redirectUrl) {
            window.location.href = redirectUrl;
        }
    };
});


/* eslint-enable */
