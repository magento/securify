/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([], function () {
    'use strict';

    var scriptTagAdded = false;

    return {
        /**
         * Add script tag. Script tag should be added once
         */
        addReCaptchaScriptTag: function (apiUrl) {
            var element, scriptTag;

            if (!scriptTagAdded) {
                element = document.createElement('script');
                scriptTag = document.getElementsByTagName('script')[0];

                element.async = true;
                element.src = apiUrl + '?onload=globalOnRecaptchaOnLoadCallback&render=explicit';

                scriptTag.parentNode.insertBefore(element, scriptTag);
                scriptTagAdded = true;
            }
        }
    };
});
