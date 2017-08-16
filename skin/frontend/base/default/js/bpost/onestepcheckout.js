get_separate_save_methods_function = function get_separate_save_methods_function_bpost(url, update_payments) {
    if (typeof update_payments == 'undefined') {
        var update_payments = false;
    }

    return function () {
        triggerAjaxCallGetSeparateSaveMethods(url, update_payments);
    };
}

triggerAjaxCallGetSeparateSaveMethods = function (url, update_payments) {
    var form = $('onestepcheckout-form');
    var shipping_method = $RF(form, 'shipping_method');

    if (window.bpostSettings && shipping_method && shipping_method.indexOf("bpostshm_") > -1) {
        var shippingmethodDate = window.bpostSettings.datepicker_days[shipping_method.replace('bpostshm_', '')]['date'];
        var currentDate = new Date(shippingmethodDate);
        var currentDay = currentDate.getDay();
        var saturdayDelivery = 0;
    }
    else {
        currentDay = "";
    }
    //if undefined, page load
    if (update_payments == true && window.bpostSettings.datepicker_choose == true) {
        window.isSaturdaySelected = false;
    }

    if ($('bpost-saturday') || window.isSaturdaySelected !== undefined) {
        if (($('bpost-saturday').checked && $$('.bpost-saturday-delivery')[0].visible())
            || (!window.isSaturdaySelected && window.isSaturdaySelected !== undefined)
            || (currentDay != 6 && !window.bpostSettings.datepicker_choose)) {
            saturdayDelivery = 1;
        }
    }

    if (typeof event != 'undefined') {
        var e = event;
        var element = e.element();

        if (element.name != 'shipping_method') {
            update_payments = false;
        }
    }
    var payment_method = $RF(form, 'payment[method]');
    var totals = get_totals_element();

    var freeMethod = $('p_method_free');
    if (freeMethod) {
        payment.reloadcallback = true;
        payment.countreload = 1;
    }

    totals.update('<div class="loading-ajax">&nbsp;</div>');

    if (update_payments) {
        var payment_methods = $$('div.payment-methods')[0];
        payment_methods.update('<div class="loading-ajax">&nbsp;</div>');
    }


    var parameters = {
        shipping_method: shipping_method,
        payment_method: payment_method,
        disable_saturday_delivery: saturdayDelivery
    }

    /* Find payment parameters and include */
    var items = $$('input[name^=payment]').concat($$('select[name^=payment]'));
    var names = items.pluck('name');
    var values = items.pluck('value');

    for (var x = 0; x < names.length; x++) {
        if (names[x] != 'payment[method]') {
            parameters[names[x]] = values[x];
        }
    }

    new Ajax.Request(url, {
        method: 'post',
        onSuccess: function (transport) {
            if (transport.status == 200) {
                var data = transport.responseText.evalJSON();
                var form = $('onestepcheckout-form');

                totals.update(data.summary);

                if (update_payments) {

                    payment_methods.replace(data.payment_method);

                    $$('div.payment-methods input[name="payment\[method\]"]').invoke('observe', 'click', get_separate_save_methods_function(url));
                    $$('div.payment-methods input[name="payment\[method\]"]').invoke('observe', 'click', function () {
                        $$('div.onestepcheckout-payment-method-error').each(function (item) {
                            new Effect.Fade(item);
                        });
                    });

                    if ($RF($('onestepcheckout-form'), 'payment[method]') != null) {
                        try {
                            var payment_method = $RF(form, 'payment[method]');
                            $('container_payment_method_' + payment_method).show();
                            $('payment_form_' + payment_method).show();
                        } catch (err) {

                        }
                    }
                }
            }
        },
        parameters: parameters
    });
}

get_methods_separate = function () {
    if ($('bpost-saturday').getStorage().get('prototype_event_registry') == undefined) {
        triggerAjaxCallGetSeparateSaveMethods(window.onestepcheckout_set_methods_separate, false);
    }
}