function openPopup(url, popupWidth, popupHeight) {
    if ($('bpost_info_window') && typeof(Windows) != 'undefined') {
        Windows.focus('bpost_info_window');
        return;
    }

    Dialog.info(null, {
        closable: true,
        resizable: false,
        draggable: false,
        className: 'magento',
        windowClassName: 'popup-window',
        title: 'Bpost Shipping Manager',
        width: popupWidth,
        height: popupHeight,
        zIndex: 1000,
        recenterAuto: true,
        hideEffect: Element.hide,
        showEffect: Element.show,
        id: 'bpost_info_window',
        url: url
    });
}

function generateAndComplete(massActionObjectName, gridObjectName) {
    if ($('bpost_info_window') && typeof(Windows) != 'undefined') {
        Windows.focus('bpost_info_window');
        return;
    }

    massActionObjectName = window[massActionObjectName];
    gridObjectName = window[gridObjectName];

    var item = massActionObjectName.getSelectedItem();
    var url = item.url;
    var fieldName = (item.field ? item.field : massActionObjectName.formFieldName);

    if (url == "undefined") {
        return;
    }

    if(varienStringArray.count(massActionObjectName.checkedString) == 0) {
        alert(massActionObjectName.errorText);
        return;
    }

    massActionObjectName.formHiddens.update('');
    new Insertion.Bottom(massActionObjectName.formHiddens, massActionObjectName.fieldTemplate.evaluate({name: fieldName, value: massActionObjectName.checkedString}));
    new Insertion.Bottom(massActionObjectName.formHiddens, massActionObjectName.fieldTemplate.evaluate({name: 'massaction_prepare_key', value: fieldName}));

    if(massActionObjectName.select.value != "generateAndComplete"){
        //check for action
        massActionObjectName.form.action = item.url;
        massActionObjectName.form.submit();
    }else{
        if(!massActionObjectName.validator.validate()) {
            return;
        }

        new Ajax.Request(url, {
            'method': 'post',
            'parameters': massActionObjectName.form.serialize(true),
            onCreate: function(request) {
                //Ajax.Responders.unregister(varienLoaderHandler.handler);
            },
            onSuccess: function(transport){
                massActionObjectName.unselectAll();
                gridObjectName.reload();

                var responseArray = transport.responseText.evalJSON();
                var messageHtml = "";

                messageHtml = '<ul class="messages">';

                if (typeof responseArray["messages"]["error"] !== 'undefined' && responseArray["messages"]["error"].length > 0) {
                    messageHtml += '<li class="error-msg">';
                    for (var i = 0; i < responseArray["messages"]["error"].length; i++) {
                        messageHtml += responseArray["messages"]["error"][i]+"<br/>";
                    }
                    messageHtml += '</li>';
                }

                if (typeof responseArray["messages"]["success"] !== 'undefined' && responseArray["messages"]["success"].length > 0) {
                    messageHtml += '<li class="success-msg">';
                    for (var i = 0; i < responseArray["messages"]["success"].length; i++) {
                        messageHtml += responseArray["messages"]["success"][i]+"<br/>";
                    }
                    messageHtml += '</li>';
                }

                if (typeof responseArray["messages"]["notice"] !== 'undefined' && responseArray["messages"]["notice"].length > 0) {
                    messageHtml += '<li class="notice-msg">';
                    for (var i = 0; i < responseArray["messages"]["notice"].length; i++) {
                        messageHtml += responseArray["messages"]["notice"][i]+"<br/>";
                    }
                    messageHtml += '</li>';
                }

                messageHtml += '</ul>';

                //fist empty the messages box
                $("messages").innerHTML = "";
                //fill back again
                $("messages").insert(messageHtml);
            }
        });
    }
}


