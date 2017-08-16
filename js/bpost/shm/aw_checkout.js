if (!window.Bpost) {
    window.Bpost = {};
};

var initialized = false;
var activeOption = "";
var carrier = "bpostshm";

Bpost.ShM = Class.create({
    initialize: function(settings, currentShippingMethod){
        this.settings = settings;
        this.container = $$('.sp-methods')[0];
        this.markers = {};

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
            size: new  google.maps.Size(24, 24),
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

        //init map if bpost carrier is selected
        if(currentShippingMethod == carrier+"_bpost_parcellocker"
            || currentShippingMethod == carrier+"_bpost_pickuppoint"
        || currentShippingMethod == carrier+"_bpost_clickcollect"){
            this.container.down('label[for="s_method_'+currentShippingMethod+'"]').insert({'after': $("bpostShm")});
            activeOption = "s_method_"+currentShippingMethod;
            $('selectPickupPoint').style.display = 'inline';
        }

        //init datepicker if bpost carrier is selected
        if(currentShippingMethod == carrier+"_bpost_homedelivery") {
            if(this.settings.datepicker_display) {
                this.container.down('label[for="s_method_' + currentShippingMethod + '"]').insert({'after': $("bpostDelivery")});
                $("bpostDelivery").style.display = 'block';

                var currMethod = currentShippingMethod.replace(carrier+'_', '');
                this.placeDates(this.settings.datepicker_days, currMethod);
            }
        }

        //set initial selected spot to false
        this.selectedspot = false;

        initialized = true;
    },
    bindEvents: function () {
        //click to open map
        $(document).on('click', "#selectPickupPoint", function (event) {
            event.preventDefault();
            this.selectPickupPointLinkClick();
        }.bind(this));

        //click on the little icon to see more information
        $(document).on('click', ".infobtn", function (event) {
            event.preventDefault();
            this.showExtraInfo();
        }.bind(this));

        $(document).on('click', ".info", function (event) {
            event.preventDefault();
            this.showExtraInfo();
        }.bind(this));

        //click on spot in list
        $(document).on('click', '.shoplistitem', function (event) {
            if (!event.target.hasClassName("selectspot")) {
                this.clickSpot((event.target.up('.shoplistitem') || event.target).id);
            }
        }.bind(this));

        //click on the filter submit
        $(document).on('click', "#filter_submit", function (event) {
            event.preventDefault();
            this.filterMarkers();
        }.bind(this));

        $(document).on('keypress', ".bpostfilter", function (event) {
            if (event.keyCode == Event.KEY_RETURN || event.which == Event.KEY_RETURN) {
                this.filterMarkers();
                Event.stop(event);
            }
        }.bind(this));

        $(document).on('click', '.selectspot', function (event) {
            this.selectSpot(this.markers[Event.element(event).readAttribute("data-shopid")].json);
            event.preventDefault();

            if(this.settings.onestepcheckout_active == true){
                $("bpost-info-wrapper").removeClassName("active");
                this.googleMapsPopup.close();
            }
        }.bind(this));

        $(document).on('click', '.close-infoBox', function (event) {
            this.closeInfobox();
            event.preventDefault();
        }.bind(this));

        $(document).on('click', '.bpost-close', function (event) {
            this.bpostClose();
            event.preventDefault();
        }.bind(this));

        $(document).on('change', 'input[type=radio][name=shipping_method]', function (event) {
            //move map to correct shipping method
            var target = event.target;
            
            this.rePosition(target);
            //show delivery date option
            this.deliveryDate(target);

            //reset saturday selection
            $('bpost-saturday').checked = false;

            //remove error messages when switching
            this.removeErrors();

            event.preventDefault();
        }.bind(this));


        $(document).on('click', '#bpost-saturday', function () {
            var currMethod = $$('input:checked[type="radio"][name="shipping_method"]').pluck('value');
            currMethod = currMethod[0].replace('bpostshm_', '');

            this.placeDates(this.settings.datepicker_days, currMethod);

        }.bind(this));

        //remove map if view gets too small
        Event.observe(((document.onresize) ? document : window), "resize", function() {
            if($('bpostShm') !== null && $('bpostShm') !== undefined && ($('bpost-info-wrapper') === undefined || $('bpost-info-wrapper') === null)) {
                var dimensions = $('bpostShm').getDimensions();
                if ($('map-canvas') !== null && $('map-canvas') !== undefined) {
                    var mapDimensions = $('map-canvas').getDimensions();

                    if(mapDimensions != null && mapDimensions.width != dimensions.width) {

                        var mapwidth = dimensions.width + 'px';
                        var inlinemapwidth = dimensions.width - 220 + 'px';

                        $('mapcontainer').setStyle({
                            width: mapwidth
                        });

                        $('map-canvas').setStyle({
                            width: inlinemapwidth
                        });
                    }
                }
            }
        });
    },
    bpostClose: function () {
        this.clearMarkers();

        if(this.settings.onestepcheckout_active == true){
            $("bpost-info-wrapper").removeClassName("active");
            this.googleMapsPopup.close();
        }

        $("bpostinfo").update("").setStyle({
            width: 'auto',
            height: 'auto'
        });

        if($('selectPickupPoint')) {
            $('selectPickupPoint').style.display = 'inline';
        }

        if (this.selectedspot === false) {
            $("bpostresult").style.display = "none";
        } else {
            $("bpostresult").style.display = "block";
        }

        this.map = null;
    },
    rePosition: function (target){
        activeOption = target.id;

        if(activeOption == "s_method_"+carrier+"_bpost_parcellocker"
            || activeOption == "s_method_"+carrier+"_bpost_pickuppoint"
            || activeOption == "s_method_"+carrier+"_bpost_clickcollect"
        ){
            //hide notifications window
            $("notifications-pick-up-point").style.display = "none";
            $("notifications-parcel-locker").style.display = "none";

            //first we close the map
            this.bpostClose();

            $(target).up().insert({'after': $("bpostShm")});
            $("bpostShm").insert({'after': $("bpostDelivery")});

            var selectPickupPointElement = document.getElementById("selectPickupPoint");

            if(activeOption == "s_method_"+carrier+"_bpost_parcellocker"){
                selectPickupPointElement.innerHTML = this.settings.select_text_parcel_locker;
                //we set point type to 4
                selectPickupPointElement.setAttribute("type", "4");
            }else if(activeOption == "s_method_"+carrier+"_bpost_clickcollect"){
                $("selectPickupPoint").setAttribute("type", 8)
                $("selectPickupPoint").update(this.settings.select_text_clickcollect);
            }
            else{
                selectPickupPointElement.innerHTML = this.settings.select_text;
                //we set point type to 3
                selectPickupPointElement.setAttribute("type", "3");
            }

            $("bpostresult").setStyle({
                display: 'none'
            });

            // reset the selected spot
            $("bpost-id").value = "";
            this.selectedspot = false;

            $("bpostShm").show();
        }else{
            if($("bpostShm")){
                $("bpostShm").hide();
            }
        }
    },
    showDates: function (target) {
        if(target.id == 's_method_'+carrier+'_bpost_homedelivery') {
            $(target).up().insert({'after': $("bpostDelivery")});
            $("bpostDelivery").style.display = 'block';
        } else if(target.id == 's_method_'+carrier+'_bpost_parcellocker'
            || target.id == 's_method_'+carrier+'_bpost_pickuppoint'
            || target.id == 's_method_'+carrier+'_bpost_clickcollect'
        ) {
            if(this.selectedspot !== false) {
                $(target).up().insert({'after': $("bpostDelivery")});
                $("bpostDelivery").style.display = 'block';
            }
        } else {
            if(this.selectedspot !== false) {
                $("bpostShm").insert({'after': $("bpostDelivery")});
                $("bpostDelivery").style.display = 'block';
            }
        }
    },
    placeDates: function (dates, currMethod) {
        $$('.bpost-saturday-delivery')[0].style.display = 'none';

        if(this.settings.datepicker_display && this.settings.datepicker_choose) {
            $$('input[name="bpost[deliverydate]"]')[0].remove();

            var pickDates = '<ul>';
            var datepickArray = dates;
            var dates = datepickArray[currMethod];
            for (var i = 0; i < dates.length; i++) {
                var date = new Date(dates[i]['date']);
                window.clickVar = "";
                if(date.getDay() == 6){
                    clickVar="window.isSaturdaySelected = true;";
                }
                else{
                    clickVar="window.isSaturdaySelected = false;";
                }
                pickDates += '<li><label for="bpost-datepicker-'+i+'"><input type="radio" name="bpost[deliverydate]" onclick="'+clickVar+'" id="bpost-datepicker-'+i+'" value="'+dates[i]['date']+'" /> '+dates[i]['date_format']+'</label></li>';
            }

            //add hidden input for validation message position
            pickDates += '<li><input type="hidden" class="validate-multiple-delivery-dates" id="bpost-datepicker-advice"/></li>';
            pickDates += '</ul>';

            var chooseDate = $$('.bpost-choose-deliverydate')[0];
            chooseDate.innerHTML = pickDates;
            chooseDate.style.display = 'block';

        } else if(this.settings.datepicker_display && !this.settings.datepicker_choose) {
            var displayDate = $$('.bpost-display-deliverydate')[0];
            var datepickArray = dates;

            displayDate.innerHTML = datepickArray[currMethod]['date_format'];
            displayDate.style.display = 'block';

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
    },
    deliveryDate: function (target) {
        $("bpostDelivery").hide();

        if(this.settings.datepicker_display) {
            var currMethod = activeOption.replace('s_method_'+carrier+'_', '');

            if(currMethod == 'bpost_homedelivery' ||
                currMethod == 'bpost_pickuppoint' ||
                currMethod == 'bpost_parcellocker' ||
                currMethod == 'bpost_clickcollect') {
                this.showDates(target);
                this.placeDates(this.settings.datepicker_days, currMethod);
            }
        }
    },
    selectPickupPointLinkClick: function () {
        //hide button & current selected spot
        $("selectPickupPoint").style.display = "none";

        //start resolving setings
        this.resolveSettings();

        //open the bpost map inline
        this.openInline();

        //hide notifications window
        $("notifications-pick-up-point").style.display = "none";
        $("notifications-parcel-locker").style.display = "none";


        //place loader and show it
        $('map-canvas').update(this.html_loading);

        var parameters = {};
        parameters["pointType"] = $("selectPickupPoint").getAttribute("type");
        this.triggerWindowAjaxRequest(parameters);
    },
    generateDeliveryDates: function (id, type) {
        var parameters = {};
        parameters['id'] = id;
        parameters['type'] = type;

        new Ajax.Request(this.settings.base_url + 'bpost/ajax/getdates', {
            method: 'post',
            parameters: parameters,
            requestHeaders: {Accept: 'application/json'},
            onSuccess: function (transport) {
                var json = transport.responseText.evalJSON(true);
                if (json.error.length == 0) {
                    //this method only applies to the pick-up points & clickcollect
                    // (nothing else can be closed on saturday)
                    var currMethod = $$('input:checked[type="radio"][name="shipping_method"]').pluck('value');
                    currMethod = currMethod[0].replace('bpostshm_', '');
                    if(currMethod == 'bpost_pickuppoint' || currMethod == 'bpost_clickcollect') {
                        this.placeDates(json.dates, currMethod);
                    }

                } else {
                    alert(json.error);
                }
            }.bind(this),
            onFailure: function () {
                var json = transport.responseText.evalJSON(true);
                alert(json.error);
                this.bpostClose();
            }
        });

        return '';
    },
    triggerWindowAjaxRequest: function(parameters){
        //AJAX!
        // type:
        //    Type 1 = Post Office
        //    Type 2 = Post Point
        //    Type 4 = bpack 24/7
        //    for example type 3 = type1 & type2
        new Ajax.Request(this.settings.base_url + 'bpost/ajax/getwindow', {
            method: 'get',
            parameters: parameters,
            requestHeaders: {Accept: 'application/json'},
            onSuccess: function (transport) {
                this.json = transport.responseText.evalJSON(true);

                if (this.json.error.length == 0) {
                    this.drawMap();
                    this.pinMarkers();
                    if(typeof this.json.poilist.Poi != 'undefined' && this.json.poilist.Poi.length > 0) {
                        this.panToLocation(this.json.poilist.Poi[0].Record.Latitude, this.json.poilist.Poi[0].Record.Longitude);
                    } else {
                        this.panToLocation();
                    }
                    this.reloadMarkers();
                } else {
                    if(this.json.poilist != undefined){
                        console.log(this.json.poilist);
                    }
                    alert(this.json.error);
                    this.bpostClose();
                }
            }.bind(this),
            onFailure: function () {
                this.json = transport.responseText.evalJSON(true);
                if(this.json.poilist != undefined){
                    console.log(this.json.poilist);
                }
                alert(this.json.error);
                this.topClose();
            },
            onComplete: function () {
                $("bpost_loading").style.display = "none";
            }.bind(this)
        });
    },
    panToLocation: function (latitude, longitude) {
        if(latitude && longitude) {
            var latLng = new google.maps.LatLng(latitude, longitude);
        } else {
            var latLng = new google.maps.LatLng(this.json.coordinates.lat, this.json.coordinates.lng);
        }
        this.map.panTo(latLng);
    },
    selectSpot: function (json) {
        //spot selected
        var image;

        //fill form
        $$('input[name^=bpost[id]]').first().value = json.Id;
        $$('input[name^=bpost[street]]').first().value = json.Street + " " + json.Number;
        $$('input[name^=bpost[city]]').first().value = json.City;
        $$('input[name^=bpost[postcode]]').first().value = json.Zip;
        $$('input[name^=bpost[name]]').first().value = json.Name;
        this.selectedspot = json.Id;

        // create notification html
        if(activeOption == "s_method_"+carrier+"_bpost_pickuppoint" || activeOption == "s_method_"+carrier+"_bpost_clickcollect"){
            $("notifications-pick-up-point").style.display = "block";
            $("pickup-point-notification-email").checked = true;
            $("pickup-point-notification-sms").checked = false;

            //create the infobox html
            var bpostresult = '<p><a href="#" class="info"><b>' + json.Name + '</b></a><a href="#" class="infobtn">?</a>' +
                '<br />' + json.Street + ' ' + json.Number + '<br />' + json.Zip + ' ' + json.City + '</p>' +
                '<ul class="infobtnvw" id="bpost-opening-hours-selected"></ul>';

            //get delivery hours based on spot's opening hour
            this.generateDeliveryDates(json.Id, json.Type);
        }else{
            $("notifications-parcel-locker").style.display = "block";
            $("notification-sms").checked = false;

            //create the infobox html
            var bpostresult = '<p><b class="no-info-btn">' + json.Name + '</b>' +
                '<br />' + json.Street + ' ' + json.Number + '<br />' + json.Zip + ' ' + json.City + '</p>';
        }

        // generate opening hours
        this.generateHours(json.Id, json.Type, false, false);

        $("bpostresult").update(bpostresult)
            .setStyle({
                display: 'block'
            });

        //if this is not true , it means that there was already a spot suggested.
        if (this.json) {
            if(activeOption == "s_method_"+carrier+"_bpost_pickuppoint"){
                //change link text
                $("selectPickupPoint").setAttribute("type", 3)
                $("selectPickupPoint").update(this.settings.change_text);

            }else if(activeOption == "s_method_"+carrier+"_bpost_clickcollect"){
                $("selectPickupPoint").setAttribute("type", 8)
                $("selectPickupPoint").update(this.settings.change_text_clickcollect);
            }
            else{
                //change link text
                $("selectPickupPoint").setAttribute("type", 4)
                $("selectPickupPoint").update(this.settings.change_text_parcel_locker);
            }
            //add delivery date option
            this.deliveryDate(false);

            //close everything
            this.bpostClose();

            //remove error advice if it exists
            this.removeErrors();
        }

        //reset
        this.active_info = null;
    },
    removeErrors: function () {
        if($('advice-validate-bpostspot-bpost-id') != undefined) {
            $('advice-validate-bpostspot-bpost-id').remove();
        }
        if($('advice-validate-parcel-bpost-id') != undefined) {
            $('advice-validate-parcel-bpost-id').remove();
        }
        if($('advice-required-entry-bpost[deliverydate]') != undefined) {
            $('advice-required-entry-bpost[deliverydate]').remove();
        }
    },
    resolveSettings: function () {
        //set dimensions
        var dimensions = $('bpostShm').getDimensions();

        this.dimensions = dimensions;
        this.mapwidth = dimensions.width + 'px';
        this.mapheight = 'auto';
        this.inlinemapwidth = dimensions.width - 220 + 'px';

        this.filterLoading = false;

        //add html
        this.html_filter = '<div class="filter"><form action="' + this.settings.base_url + 'bpost/ajax/filterspots" method="post" id="bpostspotsfilterform">' +
        '<div class="bpost-input-box">' +
        '<div class="input"><input type="text" id="bpost-gmaps-filter" class="bpostfilter input-text" name="bpost-gmaps-filter" placeholder="' + this.settings.label_postcode + '"/></div>' +
        '<div class="action"><input type="submit" class="btn-bpost" value="' + this.settings.label_filter + '" id="filter_submit" /></div>' +
        '</div>' +
        '</form></div>';
        this.html_clear = '<div style="clear:both;"></div>';
        this.html_close = '<div class="bpost-close-wrapper"><a class="bpost-close btn-bpost">'+this.settings.close_label+'</a></div>';
        this.html_list = '<ul class="list" id="bpostlist"></ul>';
        this.html_map = '<div id="map-canvas" class="map"></div>';
        this.html_loading = '<div class="bpost_loading"><span class="ajaxloading"></span><div class="image"></div><span class="bpost-please-wait">' + this.settings.label_loading + '</span></div>';

        this.iecompat = Prototype.Browser.IE6 || Prototype.Browser.IE7 || Prototype.Browser.IE8 || Prototype.Browser.IE9;
    },
    openInline: function () {
        var mapcontainer = new Element('div', { 'id': 'mapcontainer', 'class': 'mapcontainer inline', style: 'width:'+this.mapwidth+';height:'+this.mapheight+';clear:both;' })
            .insert(this.html_filter)
            .insert(this.html_list)
            .insert(this.html_map)
            .insert(this.html_clear);

        $$('.bpostspotswrapper').first().addClassName('inline');

        $("bpostinfo").update(mapcontainer.insert(this.html_close))
            .down(".filter", 0)
            .addClassName("response");

        //hide the link and result
        $("selectPickupPoint").setStyle({
            display: 'none'
        });

        $("bpostresult").setStyle({
            display: 'none'
        });

        if (this.iecompat) {
            $('bpost-gmaps-filter').value = this.settings.label_postcode;
        }

        this.insertAutocomplete();
    },
    drawMap: function () {
        $('bpostlist').setStyle({
            width: '220px'
        })

        $('map-canvas').setStyle({
            width: this.inlinemapwidth
        })

        this.map = null; //reset
        this.map = new google.maps.Map($('map-canvas'), this.mapOptions);
    },
    insertAutocomplete: function () {
        var inputEl = $('bpost-gmaps-filter');

        new google.maps.places.Autocomplete(inputEl);
    },
    generateHours: function (id, type, map, spots) {
        var parameters = {};
        parameters['id'] = id;
        parameters['type'] = type;
        parameters['spots'] = spots;
        new Ajax.Request(this.settings.base_url + 'bpost/ajax/gethours', {
            method: 'post',
            parameters: parameters,
            requestHeaders: {Accept: 'application/json'},
            onSuccess: function (transport) {
                var json = transport.responseText.evalJSON(true);

                //if only a single point is queried
                if(json.hours) {
                    if (json.error.length == 0) {
                        var openingHours = this.formatOpeningHours(json);

                        if(map) {
                            var currentInfobox = this.infowindows[id];
                            var currentInfoboxContent = currentInfobox.getContent()
                            var newContent = currentInfoboxContent.replace('<ul class="hours"></ul>', '<ul class="hours">'+openingHours+'</ul>');
                            currentInfobox.setContent(newContent);
                        } else {
                            //add openinghours to '?' information button
                            $('bpost-opening-hours-selected').update(openingHours);
                        }
                    } else {
                        alert(json.error);
                    }
                } else { //multiple points queried at once
                    for (var key in json) {
                        var openingHours = this.formatOpeningHours(json[key]);

                        //multiple points only get added to the map
                        if(map) {
                            var currentInfobox = this.infowindows[key];
                            var currentInfoboxContent = currentInfobox.getContent()
                            var newContent = currentInfoboxContent.replace('<ul class="hours"></ul>', '<ul class="hours">'+openingHours+'</ul>');
                            currentInfobox.setContent(newContent);
                        }
                    }
                }
            }.bind(this),
            onFailure: function () {
                alert("Could not contact the server, please try again");
                this.bpostClose();
            }
        });

        return '';
    },
    formatOpeningHours: function (json) {
        var days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        var daysLength = days.length;
        var currentDay = '';
        var openingHours = '';

        for (var i = 0; i < daysLength; i++) {
            currentDay = days[i].toLowerCase();

            openingHours = openingHours + "<li><span class='day'>" + this.settings[currentDay] + "</span>";
            //if no opening hours are given for a specific day we'll assume the point is closed
            if(typeof json.hours[days[i]].AMOpen === 'object' &&
                typeof json.hours[days[i]].AMClose === 'object' &&
                typeof json.hours[days[i]].PMOpen === 'object' &&
                typeof json.hours[days[i]].PMClose === 'object') {
                openingHours = openingHours + this.settings.closed;
            } else {
                //morning hours
                openingHours = openingHours + "<span class='large'>";
                //if no morning opening hour given assume closed
                if(typeof json.hours[days[i]].AMOpen !== 'object') {
                    //if a morning closing hour is given we assume they have a break at noon else just show the opening hour
                    if(typeof json.hours[days[i]].AMClose !== 'object') {
                        openingHours = openingHours + json.hours[days[i]].AMOpen + ' - ' + json.hours[days[i]].AMClose;
                    } else {
                        openingHours = openingHours + json.hours[days[i]].AMOpen;
                    }
                } else {
                    openingHours = openingHours + this.settings.closed;
                }
                openingHours = openingHours + "</span>";

                //breaktime
                if(typeof json.hours[days[i]].AMOpen === 'object' && typeof json.hours[days[i]].AMClose === 'object' && (typeof json.hours[days[i]].PMOpen !== 'object' || typeof json.hours[days[i]].PMClose !== 'object')) {
                    openingHours = openingHours + "<span class='small'>/</span>";
                } else if(typeof json.hours[days[i]].AMOpen !== 'object' && typeof json.hours[days[i]].AMClose !== 'object' && (typeof json.hours[days[i]].PMClose === 'object' || typeof json.hours[days[i]].PMClose !== 'object')) {
                    openingHours = openingHours + "<span class='small'>/</span>";
                }

                //afternoon
                openingHours = openingHours + "<span class='large'>";
                //if no morning opening hour given assume closed
                if(typeof json.hours[days[i]].PMClose !== 'object') {
                    //if a morning closing hour is given we assume they have a break at noon else just show the opening hour
                    if(typeof json.hours[days[i]].PMOpen !== 'object') {
                        openingHours = openingHours + json.hours[days[i]].PMOpen + ' - ' + json.hours[days[i]].PMClose;
                    } else {
                        openingHours = openingHours + '&nbsp;- ' + json.hours[days[i]].PMClose;
                    }
                } else {
                    openingHours = openingHours + this.settings.closed;
                }
                openingHours = openingHours + "</span>";
            }
            openingHours = openingHours + "</span></li>";
        }

        return openingHours;
    },
    pinMarkers: function () {
        this.infowindows = {};
        this.markers = {};

        //loop trough shops
        if(typeof this.json.poilist.Poi !== 'undefined') {
            var spots = [];

            //we check for array
            //if we have just one element, we force this element as array
            if( Object.prototype.toString.call(this.json.poilist.Poi) !== '[object Array]' ) {
                this.json.poilist.Poi = [this.json.poilist.Poi];
            }

            for (var i = 0, poiList = this.json.poilist.Poi.length; i < poiList; i++) {
                //google maps infobox
                this.infowindows[this.json.poilist.Poi[i].Record.Id] = new InfoBox(this.infoboxOptions);
                this.infowindows[this.json.poilist.Poi[i].Record.Id].setContent('<div class="infobox-arrow"></div><a href="#" class="close close-infoBox"></a>' +
                '<h3>' + this.json.poilist.Poi[i].Record.Name + '</h3>' +
                '<p>' + this.json.poilist.Poi[i].Record.Street + ' ' + this.json.poilist.Poi[i].Record.Number +
                '<br />' + this.json.poilist.Poi[i].Record.Zip + ' ' + this.json.poilist.Poi[i].Record.City +
                '</p><ul class="hours"></ul>' +
                '<a href="#" data-shopid="' + this.json.poilist.Poi[i].Record.Id + '" class="selectspot">'+this.settings.label_select+' &raquo;</a>');

                //add opening hours
                spots.push({
                    id: this.json.poilist.Poi[i].Record.Id,
                    type: this.json.poilist.Poi[i].Record.Type
                });

                //set icons depending on spot type
                var mapIcon = {
                    1 : this.imageOpenPostOffice,
                    2 : this.imageOpenPostPoint,
                    3 : this.imageOpenPostOffice, //bpost should never return 3
                    4 : this.imageOpenParcelLocker,
                    8 : this.imageOpenClickCollect
                }

                //google maps marker
                this.markers[this.json.poilist.Poi[i].Record.Id] = new google.maps.Marker({
                    position: new google.maps.LatLng(this.json.poilist.Poi[i].Record.Latitude, this.json.poilist.Poi[i].Record.Longitude),
                    map: this.map,
                    icon: mapIcon[this.json.poilist.Poi[i].Record.Type],
                    shape: this.shape,
                    zIndex: 1,
                    json: this.json.poilist.Poi[i].Record
                });

                google.maps.event.addListener(this.markers[this.json.poilist.Poi[i].Record.Id], 'click', (function (marker) {
                    return function () {
                        this.clickSpot(marker.json.Id);
                    }.bind(this)
                }.bind(this))(this.markers[this.json.poilist.Poi[i].Record.Id]));

                //insert points in list
                $$('ul.list').first().insert("<li class='shoplistitem' id='" + this.json.poilist.Poi[i].Record.Id + "'>" + "<span class='title'>" + this.json.poilist.Poi[i].Record.Name + "</span>" + "<span class='address'>" + this.json.poilist.Poi[i].Record.Street + " " + this.json.poilist.Poi[i].Record.Number + "</span>" + "<span class='city'>" + this.json.poilist.Poi[i].Record.Zip + " " + this.json.poilist.Poi[i].Record.City + "</span><a href='#' data-shopid='" + this.json.poilist.Poi[i].Record.Id + "' class='selectspot' >" + this.settings.label_select + " &raquo;</a></li>");
            }
            var pointType = $("selectPickupPoint").getAttribute("type");
            if(spots.length > 0 && (pointType == 3 || pointType == 8)) {
                spots = JSON.stringify(spots);
                this.generateHours(false, false, true, spots);
            }
        } else {
            //add error to list
            $$('ul.list').first().insert("<li class='shoplistitem bpost-spots-error' id='bpost-spots-error'><span class='error'>" + this.settings.no_points_found + "</span></li>");
        }
    },
    closeInfobox: function () {
        if (this.active_info != null && this.infowindows[this.active_info])
            this.infowindows[this.active_info].close();

        this.active_info = null;
    },
    showExtraInfo: function () {
        if ($$(".infobtnvw").first().style.visibility == "visible") {
            $$(".infobtnvw").first().style.visibility = "hidden";
        } else {
            $$(".infobtnvw").first().setStyle({
                left: ($$(".infobtn").first().offsetLeft + $$(".infobtn").first().getDimensions().width) + "px",
                visibility: "visible"
            });
        }
    },
    clearMarkers: function () {
        //Remove boundaries error from spotlist
        if ($('bpost-spots-error') != undefined) {
            $('bpost-spots-error').remove();
        }

        for (var key in this.markers) {
            //remove marker from map
            this.markers[key].setMap(null);
            //remove infowindow
            this.infowindows[key].close();
            //remove item from list
            if (key != null && typeof key != "undefined" && $(key) != undefined) {
                $(key).remove();
            }
        }
        this.markers = {};
        this.active_info = null;
        this.infowindows = {};
    },
    reloadMarkers: function () {
        google.maps.event.addListener(this.map, 'dragend', function () {
            //load new points based on latitude and longitude values
            if (this.filterLoading != true) {
                this.filterLoading = true;

                var mapCenter = this.map.getCenter();
                var parameters = {};
                parameters['lat'] = mapCenter.lat();
                parameters['lng'] = mapCenter.lng();
                parameters["pointType"] = $("selectPickupPoint").getAttribute("type");

                new Ajax.Request(this.settings.base_url + 'bpost/ajax/getwindow', {
                    method: 'post',
                    parameters: parameters,
                    requestHeaders: {Accept: 'application/json'},
                    onSuccess: function (transport) {
                        var json = transport.responseText.evalJSON(true);

                        if (json.error.length == 0) {
                            this.clearMarkers();
                            this.json = json;
                            this.pinMarkers();
                        } else {
                            alert(json.error);
                            this.bpostClose();
                        }
                    }.bind(this),
                    onFailure: function () {
                        alert("Could not contact the server, please try again");
                        this.bpostClose();
                    },
                    onComplete: function () {
                        //reset the click
                        this.filterLoading = false;
                    }.bind(this)
                });
            }
        }.bind(this));
    },
    clickSpot: function (spotid) {
        //move map to center of this marker
        if(this.markers[spotid] !== undefined) {
            this.map.panTo(this.markers[spotid].getPosition());

            //update the list (if enabled)
            var expanded = $$(".expanded").first();
            if (expanded != undefined) {
                expanded.removeClassName("expanded");
            }

            $(spotid).addClassName("expanded");

            $$(".list").first().scrollTop = $(spotid).addClassName("expanded").offsetTop;

            //open the infobubble
            if (this.active_info != null) {
                this.infowindows[this.active_info].close();
            }
            this.infowindows[spotid].open(this.map, this.markers[spotid]);

            //active marker is this one
            this.active_info = spotid;
        }
    },
    filterMarkers: function () {
        //disable a second click
        if (this.filterLoading != true && $("bpost-gmaps-filter").value.trim() != "") {
            this.filterLoading = true;
            $("filter_submit").addClassName("busy");

            if ($("bpost-gmaps-filter").value == this.settings.label_postcode) {
                $("bpost-gmaps-filter").value = "";
            }

            var parameters = {};
            parameters["pointType"] = $("selectPickupPoint").getAttribute("type");
            parameters["bpost-gmaps-filter"] = $("bpost-gmaps-filter").value;

            //place loader and show it
            if(this.settings.onestepcheckout_active == true) {
                var dimensions = $('mapcontainer').getDimensions();
            } else {
                var dimensions = $('bpostShm').getDimensions();
            }
            $('map-canvas').previous('ul').hide();
            $('map-canvas').setStyle({'width': dimensions.width + 'px', backgroundColor: 'inherit'});
            $('map-canvas').update(this.html_loading);

            new Ajax.Request(this.settings.base_url + 'bpost/ajax/getwindow', {
                method: 'post',
                parameters: parameters,
                requestHeaders: {Accept: 'application/json'},
                onSuccess: function (transport) {
                    var json = transport.responseText.evalJSON(true);
                    if (json.error.length == 0) {
                        this.drawMap();
                        $('map-canvas').previous('ul').show();
                        this.reloadMarkers();
                        this.clearMarkers();
                        this.json = json;
                        this.pinMarkers();
                        this.panToLocation();
                    } else {
                        if(this.settings.onestepcheckout_active == true){
                            $("bpost-info-wrapper").removeClassName("active");
                            this.googleMapsPopup.close();
                        }
                        alert(json.error);
                        this.bpostClose();
                    }
                }.bind(this),
                onFailure: function () {
                    var json = transport.responseText.evalJSON(true);
                    alert(json.error);
                    this.bpostClose();
                },
                onComplete: function () {
                    //enable the button and reset the click
                    this.filterLoading = false;
                    $("filter_submit").removeClassName("busy");
                    this.postalCodeBlur();
                }.bind(this)
            });
        }
    }
});