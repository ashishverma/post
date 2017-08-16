var shippingParameters = {};

Bpost.ShM.addMethods({
    initialize: function (settings, currentShippingMethod) {
        this.settings = settings;
        this.container = $$('.sp-methods')[0];

        $$(".aw-onestepcheckout-add-loader-into-this-block")[0].insert({'before': $("bpost-info-wrapper")});

        this.googleMapsPopupContainer = $('bpost-info-wrapper');
        this.googleMapsPopup = new Control.Modal(this.googleMapsPopupContainer, {
            overlayOpacity: 0.65,
            fade: true,
            fadeDuration: 0.3,
            position: 'center',
            destroyOnClose: true,
            afterClose: function(){
                if($('control_overlay')) {
                    $('control_overlay').setStyle({
                        display: 'none'
                    });
                }
            }
        });

        if(currentShippingMethod.substring(0,14) == "bpostshm_bpost"){
            var hook = 'label[for="s_method_'+currentShippingMethod+'"]';
            this.container.down(hook).insert({'after': $("bpostDelivery")});
        }

        if(currentShippingMethod == carrier+"_bpost_parcellocker"){
            this.container.down('label[for="s_method_'+carrier+'_bpost_parcellocker"]').insert({'after': $("bpostShm")});
            activeOption = "s_method_"+carrier+"_bpost_parcellocker";
            $('selectPickupPoint').style.display = 'inline';
        }

        if(currentShippingMethod == carrier+"_bpost_pickuppoint"){
            this.container.down('label[for="s_method_'+carrier+'_bpost_pickuppoint"]').insert({'after': $("bpostShm")});
            activeOption = "s_method_"+carrier+"_bpost_pickuppoint";
            $('selectPickupPoint').style.display = 'inline';
        }

        if(currentShippingMethod == carrier+"_bpost_clickcollect"){
            this.container.down('label[for="s_method_'+carrier+'_bpost_clickcollect"]').insert({'after': $("bpostShm")});
            activeOption = "s_method_"+carrier+"_bpost_clickcollect";
            $('selectPickupPoint').style.display = 'inline';
        }

        //init datepicker if bpost carrier is selected
        if(currentShippingMethod == carrier+"_bpost_homedelivery" || currentShippingMethod == carrier+"_bpost_international")
        {
            if(this.settings.datepicker_display) {
                this.container.down('label[for="s_method_' + currentShippingMethod + '"]').insert({'after': $("bpostDelivery")});
                $("bpostDelivery").style.display = 'block';

                var currMethod = currentShippingMethod.replace(carrier+'_', '');
                this.placeDates(this.settings.datepicker_days, currMethod);
            }
        }
        this.selectPickupPointLinkClick = this.selectPickupPointLinkClick.bind(this);
        this.resolveSettings = this.resolveSettings.bind(this);
        this.openInline = this.openInline.bind(this);
        this.showDates = this.showDates.bind(this);
        this.deliveryDate = this.deliveryDate.bind(this);
        this.drawMap = this.drawMap.bind(this);
        this.showExtraInfo = this.showExtraInfo.bind(this);
        this.insertAutocomplete = this.insertAutocomplete.bind(this);
        this.pinMarkers = this.pinMarkers.bind(this);
        this.clearMarkers = this.clearMarkers.bind(this);
        this.filterMarkers = this.filterMarkers.bind(this);
        this.reloadMarkers = this.reloadMarkers.bind(this);
        this.selectSpot = this.selectSpot.bind(this);
        this.clickSpot = this.clickSpot.bind(this);
        this.closeInfobox = this.closeInfobox.bind(this);
        this.bpostClose = this.bpostClose.bind(this);

        this.imageOpenPostOffice = {
            url: this.settings.location_postoffice_default_image,
            size: new google.maps.Size(24, 24),
            origin: new google.maps.Point(0, 0),
            anchor: new google.maps.Point(24, 36)
        };
        this.imageOpenPostPoint = {
            url: this.settings.location_postpoint_default_image,
            size: new google.maps.Size(24, 24),
            origin: new google.maps.Point(0, 0),
            anchor: new google.maps.Point(24, 36)
        };
        this.imageOpenParcelLocker = {
            url: this.settings.location_parcellocker_default_image,
            size: new google.maps.Size(24, 24),
            origin: new google.maps.Point(0, 0),
            anchor: new google.maps.Point(24, 36)
        };
        if(this.settings.location_clickcollect_custom_image){
            this.imageOpenClickCollect = {
                url: this.settings.location_clickcollect_custom_image,
                size: new google.maps.Size(24, 24),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(24, 36)
            };
        }else{
            this.imageOpenClickCollect = {
                url: this.settings.location_clickcollect_default_image,
                size: new google.maps.Size(24, 24),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(24, 36)
            };
        }
        this.mapOptions = {
            zoom: 13,
            panControl: false,
            zoomControl: true,
            zoomControlOptions: {
                style: google.maps.ZoomControlStyle.SMALL,
                position: google.maps.ControlPosition.TOP_RIGHT
            },
            mapTypeControl: false,
            scaleControl: false,
            streetViewControl: false,
            overviewMapControl: false,
            center: new google.maps.LatLng(51, 4),
            styles: [
                {"featureType": "all", "elementType": "all", "stylers": [
                    {"saturation": -93},
                    {"lightness": 8}
                ]},
                {featureType: "poi", stylers: [
                    {visibility: "off"}
                ]}
            ]
        };
        this.infoboxOptions = {
            content: document.createElement("div"),
            disableAutoPan: false,
            maxWidth: 0,
            pixelOffset: new google.maps.Size(0, -10),
            zIndex: null,
            boxStyle: {
                width: "235px"
            },
            closeBoxURL: "",
            infoBoxClearance: new google.maps.Size(20, 20),
            isHidden: false,
            pane: "floatPane",
            enableEventPropagation: true
        };
        this.shape = {
            coord: [1, 1, 1, 45, 45, 45, 45, 1],
            type: 'poly'
        };

        if (typeof $(document).eventsBinded == "undefined" && initialized == false) {
            this.bindEvents();
        }

        initialized = true;
        if(this.settings.datepicker_display && this.settings.datepicker_choose) {
            window.isSaturdaySelected = false;
        }
    },
    selectPickupPointLinkClick: function () {
        //set shipping parameters
        this.setShippingParameters();
        //first check if all necessary parameters are set
        if(shippingParameters["address_id"] == null && shippingParameters["postcode"] == "" && shippingParameters["city"] == ""){
            alert(this.settings.onestepcheckout_shipping_address_error);
            return false;
        }

        //start resolving setings
        this.resolveSettings();

        var mapcontainer = new Element('div', { 'id': 'mapcontainer', 'class': 'mapcontainer'})
            .insert(this.html_filter)
            .insert(this.html_list)
            .insert(this.html_map)
            .insert(this.html_clear);

        if(this.settings.onestepcheckout_active == true){
            $("bpost-info-wrapper").addClassName("active");
        }

        this.googleMapsPopup.open();

        $("bpostinfo").update(mapcontainer).down(".filter", 0);

        if (this.iecompat) {
            $('bpost-gmaps-filter').value = this.settings.label_postcode;
        }

        //place loader and show it
        $('map-canvas').update(this.html_loading);

        //call AJAX functionality
        this.triggerWindowAjaxRequest(shippingParameters);

        this.insertAutocomplete();
    },
    insertAutocomplete: function () {
        var inputEl = $('bpost-gmaps-filter');

        new google.maps.places.Autocomplete(inputEl);
    },
    drawMap: function () {
        $('bpostlist').setStyle({
            width: '220px'
        })

        $('map-canvas').setStyle({
            width: '515px'
        })

        this.map = null;
        this.map = new google.maps.Map($('map-canvas'), this.mapOptions);
    },
    setShippingParameters: function () {

        //add extra parameters
        //we get the selected shipping address data
        //first check if use billing for shipping is enabled
        shippingParameters = {};
        shippingParameters["pointType"] = $("selectPickupPoint").getAttribute("type");

        var indexMapping = {
            "billing:city": "city",
            "shipping:city": "city",
            "billing:postcode": "postcode",
            "shipping:postcode": "postcode",
            "billing:street1": "street",
            "shipping:street1": "street"
        };

        if($('billing:use_for_shipping').checked){
            var savedBillingItems = $('billing-address-select');
            if(savedBillingItems && savedBillingItems.getValue()){
                shippingParameters["address_id"] = savedBillingItems.getValue();
            } else {
                shippingParameters["address_id"] = null;
                var items = $$('input[name^=billing]').concat($$('select[name^=billing]'));
                items.each(function(s) {
                    if(s.getStyle('display') != 'none' && s.id == "billing:city" || s.id == "billing:postcode" || s.id == "billing:street1"){
                        shippingParameters[indexMapping[s.id]] = s.getValue();
                    }
                });
            }
        }else{
            var savedShippingItems = $('shipping-address-select');
            if(savedShippingItems && savedShippingItems.getValue()){
                shippingParameters["address_id"] = savedShippingItems.getValue();
            } else {
                shippingParameters["address_id"] = null;
                var items = $$('input[name^=shipping]').concat($$('select[name^=shipping]'));
                items.each(function(s) {
                    if(s.getStyle('display') != 'none' && s.id == "shipping:city" || s.id == "shipping:postcode" || s.id == "shipping:street1"){
                        shippingParameters[indexMapping[s.id]] = s.getValue();
                    }
                });
            }
        }
    }, placeDates: function (dates, currMethod) {

        $$('.bpost-saturday-delivery')[0].style.display = 'none';
        if(this.settings.datepicker_display && this.settings.datepicker_choose) {
            $$('input[name="bpost[deliverydate]"]')[0].remove();

            var pickDates = '<ul>';
            var datepickArray = dates;
            var dates = datepickArray[currMethod];
            var saturdayPresent = false;
            for (var i = 0; i < dates.length; i++) {
                var date = new Date(dates[i]['date']);
                window.clickVar = "";
                if(date.getDay() == 6){
                    clickVar="window.isSaturdaySelected = true;";
                }
                else{
                    clickVar="window.isSaturdaySelected = false;";
                }
                pickDates += '<li><label for="bpost-datepicker-'+i+'"><input type="radio" name="bpost[deliverydate]" class="deliveryDates" onclick="'+clickVar+' triggerAjaxCallGetSeparateSaveMethods(\''+window.onestepcheckout_set_methods_separate+'\', false);" id="bpost-datepicker-'+i+'" value="'+dates[i]['date']+'" /> '+dates[i]['date_format']+'</label></li>';
            }
            //add hidden input for validation message position
            pickDates += '<li><input type="hidden" class="validate-multiple-delivery-dates" id="bpost-datepicker-advice" /></li>';
            pickDates += '</ul>';

            var chooseDate = $$('.bpost-choose-deliverydate')[0];
            chooseDate.innerHTML = pickDates;
            chooseDate.style.display = 'block';

        } else if(this.settings.datepicker_display && !this.settings.datepicker_choose) {
            var displayDate = $$('.bpost-display-deliverydate')[0];
            var datepickArray = dates;

            displayDate.innerHTML = datepickArray[currMethod]['date_format'];
            displayDate.style.display = 'block';

            //var inputElement = '<input type="hidden" name="bpost[deliverydate]" vale="'+datepickArray[currMethod]['date']+'"/>';
            $$('input[name="bpost[deliverydate]"]')[0].value = datepickArray[currMethod]['date'];

            if($('bpost-saturday-hidden') != undefined) {
                $('bpost-saturday-hidden').remove();
            }

            //saturday delivery option
            if(datepickArray[currMethod]['is_saturday']) {
                $$('.bpost-saturday-delivery')[0].style.display = 'block';
                if($('bpost-saturday').checked) {
                    displayDate.innerHTML = datepickArray[currMethod]['next_date_format'];
                    displayDate.style.display = 'block';
                    $$('input[name="bpost[deliverydate]"]')[0].value = datepickArray[currMethod]['next_date'];
                } else {
                    //create hidden input if not checked but a saturday
                    $('bpost-saturday').insert({before: '<input type="hidden" name="bpost_saturday_delivery" id="bpost-saturday-hidden" value="" />'})
                }
            }
        }
    }
})