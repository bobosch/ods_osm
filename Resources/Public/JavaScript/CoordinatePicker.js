/**
 * Module: TYPO3/CMS/OdsOsm/CoordinatePicker
 *
 * JavaScript to handle data pick
 * @exports TYPO3/CMS/OdsOsm/CoordinatePicker
 */
define(function () {
    'use strict';

    /**
     * @exports TYPO3/CMS/sOdsOsm/CoordinatePicker
     */
    var CoordinatePicker = {};

    /**
     * @param {int} id
     */
    CoordinatePicker.pick = function (id) {
        $.ajax({
            type: 'POST',
            url: TYPO3.settings.ajaxUrls['coordinatepicker'],
            data: {
                'id': id,
                'mode': 'point'
            }
        }).done(function (response) {
            if (response.success) {
                top.TYPO3.Notification.success('Import Done', response.output);
            } else {
                top.TYPO3.Notification.error('Import Error!');
            }
        });
    };

    /**
     * initializes events using deferred bound to document
     * so AJAX reloads are no problem
     */
    CoordinatePicker.initializeEvents = function () {

        $('.coordinatepicker').on('click', function (evt) {
            evt.preventDefault();
            CoordinatePicker.pick($(this).attr('data-id'));
        });
    };

    $(CoordinatePicker.initializeEvents);

    return CoordinatePicker;
});
