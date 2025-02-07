/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */

define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/confirm',
    'domReady!'
], function ($, $t, confirm) {
    'use strict';

    return function (config, element) {

        var $element = $(element),
            initialValue = $element.val(),
            duoProviderValue = config.duoProviderValue,
            duoFields = config.duoFields;

        /**
         * Adds the "required" attribute to each Duo field
         *
         * @param {Array} fields - List of field IDs to mark as required
         */
        function addRequiredAttributes(fields) {
            fields.forEach(function (fieldId) {
                var $field = $('#' + fieldId);

                if ($field.length) {
                    $field.attr('required', 'required');
                    $field.addClass('required-entry');
                }
            });
        }

        /**
         * Removes the "required" attribute from each Duo field
         *
         * @param {Array} fields - List of field IDs to unmark as required
         */
        function removeRequiredAttributes(fields) {
            fields.forEach(function (fieldId) {
                var $field = $('#' + fieldId);

                if ($field.length) {
                    $field.removeAttr('required');
                    $field.removeClass('required-entry');
                }
            });
        }

        $element.on('change', function () {
            var selectedValues = $element.val() || [];

            if (selectedValues.includes(duoProviderValue)) {
                addRequiredAttributes(duoFields);
            } else {
                removeRequiredAttributes(duoFields);
            }
        });

        element.on('blur', function () {
            var currentValue = $element.val();

            if (currentValue && currentValue.some(function (item) {
                return initialValue.indexOf(item) !== -1;
            })) {
                return;
            }

            confirm({
                title: config.modalTitleText,
                content: config.modalContentBody,
                buttons: [{
                    text: $t('Cancel'),
                    class: 'action-secondary action-dismiss',

                    click: function (event) {
                        this.closeModal(event);
                    }
                }, {
                    text: $t('Confirm'),
                    class: 'action-primary action-accept',

                    click: function (event) {
                        this.closeModal(event, true);
                    }
                }],
                actions: {
                    cancel: function () {
                        $element.val(initialValue);
                    }
                }
            });
        });
    };
});

