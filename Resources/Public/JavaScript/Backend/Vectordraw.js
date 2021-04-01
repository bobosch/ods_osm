define(['jquery', 'TYPO3/CMS/Backend/Icons', 'TYPO3/CMS/Backend/FormEngine', 'TYPO3/CMS/OdsOsm/Leaflet/Core/leaflet', 'TYPO3/CMS/OdsOsm/Leaflet/leaflet-draw/leaflet.draw'], function ($, Icons, FormEngine) {
    'use strict';

    let LeafBE = {
        $element: null,
        $gLatitude: null,
        $gLongitude: null,
        $latitude: null,
        $longitude: null,
        $fieldData: null,
        $fieldLat: null,
        $fieldLon: null,
        $fieldLatActive: null,
        $tilesUrl: null,
        $tilesCopy: null,
        $zoomLevel: 13,
        $marker: null,
        $map: null,
        $iconClose: null,
        $drawnItems: null,
        $drawControl: null
    };

    // Load icon via TYPO3 Icon-API and requireJS
    Icons.getIcon('actions-close', Icons.sizes.small).done(function (actionsClose) {
        LeafBE['$iconClose'] = actionsClose;
    });

    LeafBE.init = function (element) {
        // basic variable initialisation, uses data vars on the trigger button
        LeafBE.$element = element;
        LeafBE.$labelTitle = LeafBE.$element.attr('data-label-title');
        LeafBE.$labelClose = LeafBE.$element.attr('data-label-close');
        LeafBE.$labelImport = LeafBE.$element.attr('data-label-import');
        LeafBE.$latitude = LeafBE.$element.attr('data-lat');
        LeafBE.$longitude = LeafBE.$element.attr('data-lon');
        LeafBE.$gLatitude = LeafBE.$element.attr('data-glat');
        LeafBE.$gLongitude = LeafBE.$element.attr('data-glon');
        LeafBE.$tilesUrl = LeafBE.$element.attr('data-tiles');
        LeafBE.$tilesCopy = LeafBE.$element.attr('data-copy');
        LeafBE.$fieldLat = LeafBE.$element.attr('data-namelat');
        LeafBE.$fieldLon = LeafBE.$element.attr('data-namelon');
        LeafBE.$fieldDataName = LeafBE.$element.attr('data-fieldName');
        LeafBE.$fieldDataValue = LeafBE.$element.attr('data-fieldValue');
        LeafBE.$fieldLatActive = LeafBE.$element.attr('data-namelat-active');

        // add the container to display the map as a nice overlay
        if (!$('#t3js-location-map-wrap').length) {
            LeafBE.addMapMarkup();
            console.log(element);
        }
    };

    LeafBE.addMapMarkup = function () {
        $('body').append(
            '<div id="t3js-location-map-wrap">' +
            '<div class="t3js-location-map-title">' +
            '<div class="btn-group"><a href="#" class="btn btn-icon btn-default" title="' + LeafBE.$labelClose + '" id="t3js-ttaddress-close-map">' +
            LeafBE.$iconClose +
            '</a>' +
            '<a class="btn btn-default" href="#" title="Import marker position to form" id="t3js-ttaddress-import-position">' +
            LeafBE.$labelImport +
            '</a></div>' +
            LeafBE.$labelTitle +
            '</div>' +
            '<div class="t3js-location-map-container" id="t3js-location-map-container">' +
            '</div>' +
            '</div>'
        );
    };

    LeafBE.createMap = function () {

        // The ultimate fallback: if one of the coordinates is empty, fallback to Kopenhagen.
        // Thank you Kaspar for TYPO3 and its great community! ;)
        if (LeafBE.$latitude == null || LeafBE.$longitude == null) {
            LeafBE.$latitude = LeafBE.$gLatitude;
            LeafBE.$longitude = LeafBE.$gLongitude;
            // set zoomlevel lower for faster navigation
            LeafBE.$zoomLevel = 4;
        }
        LeafBE.$map = L.map('t3js-location-map-container', {
            center: [LeafBE.$latitude, LeafBE.$longitude],
            zoom: LeafBE.$zoomLevel
        });
        var osm = L.tileLayer(LeafBE.$tilesUrl, {
            attribution: LeafBE.$tilesCopy
        }).addTo(LeafBE.$map);

        // LeafBE.$marker = L.marker([LeafBE.$latitude, LeafBE.$longitude], {
        //     draggable: true
        // }).addTo(LeafBE.$map);

        // if (LeafBE.$fieldData) {
        //     var drawnItems = LeafBE.$fieldData;
        // } else {

        var drawnItems = new L.FeatureGroup().addTo(LeafBE.$map);

        L.control.layers({
            "osm": osm.addTo(LeafBE.$map)
        }, {'GeoJSON Data':drawnItems}, { position: 'topright', collapsed: false }).addTo(LeafBE.$map);

        LeafBE.$map.addControl(new L.Control.Draw({
            edit: {
                featureGroup: drawnItems,
                poly: {
                    allowIntersection: false
                }
            },
            draw: {
                polygon: {
                    allowIntersection: false,
                    showArea: true
                }
            }
        }));
        var myGeoJson = JSON.parse(LeafBE.$fieldDataValue.toString());
        console.log(myGeoJson);
        var geojson = L.geoJSON(myGeoJson).addTo(LeafBE.$map);

        // var geoJsonGroup = new L.geoJson(myGeoJson);
        // addNonGroupLayers(geoJsonGroup, drawnItems);

        // Would benefit from https://github.com/Leaflet/Leaflet/issues/4461
        function addNonGroupLayers(sourceLayer, targetGroup) {
            if (sourceLayer instanceof L.LayerGroup) {
                sourceLayer.eachLayer(function (layer) {
                    addNonGroupLayers(layer, targetGroup);
                });
            } else {
                targetGroup.addLayer(sourceLayer);
            }
        }

        // Generate popup content based on layer type
        // - Returns HTML string, or null if unknown object
        var getPopupContent = function(layer) {
            // Marker - add lat/long
            if (layer instanceof L.Marker || layer instanceof L.CircleMarker) {
                return strLatLng(layer.getLatLng());
            // Circle - lat/long, radius
            } else if (layer instanceof L.Circle) {
                var center = layer.getLatLng(),
                    radius = layer.getRadius();
                return "Center: "+strLatLng(center)+"<br />"
                      +"Radius: "+_round(radius, 2)+" m";
            // Rectangle/Polygon - area
            } else if (layer instanceof L.Polygon) {
                var latlngs = layer._defaultShape ? layer._defaultShape() : layer.getLatLngs(),
                    area = L.GeometryUtil.geodesicArea(latlngs);
                return "Area: "+L.GeometryUtil.readableArea(area, true);
            // Polyline - distance
            } else if (layer instanceof L.Polyline) {
                var latlngs = layer._defaultShape ? layer._defaultShape() : layer.getLatLngs(),
                    distance = 0;
                if (latlngs.length < 2) {
                    return "Distance: N/A";
                } else {
                    for (var i = 0; i < latlngs.length-1; i++) {
                        distance += latlngs[i].distanceTo(latlngs[i+1]);
                    }
                    return "Distance: "+_round(distance, 2)+" m";
                }
            }
            return null;
        };

        // Object created - bind popup to layer, add to feature group
        LeafBE.$map.on(L.Draw.Event.CREATED, function(event) {
            var layer = event.layer;
            var content = getPopupContent(layer);
            if (content !== null) {
                layer.bindPopup(content);
            }
            drawnItems.addLayer(layer);
        });

        // Object(s) edited - update popups
        LeafBE.$map.on(L.Draw.Event.EDITED, function(event) {
            var layers = event.layers,
                content = null;
            layers.eachLayer(function(layer) {
                content = getPopupContent(layer);
                if (content !== null) {
                    layer.setPopupContent(content);
                }
            });
        });



//        let position = LeafBE.$marker.getLatLng();

        // LeafBE.$marker.on('dragend', function (event) {
        //     LeafBE.$marker = event.target;
        //     position = LeafBE.$marker.getLatLng();
        // });
        // LeafBE.$map.on('click', function (event) {
        //     LeafBE.$marker.setLatLng(event.latlng);
        // });
        // import coordinates and close overlay
        $('#t3js-ttaddress-import-position').on('click', function () {
            // set visual coordinates
            // $('input[data-formengine-input-name="' + LeafBE.$fieldLat + '"]').val(LeafBE.$marker.getLatLng().lat);
            // $('input[data-formengine-input-name="' + LeafBE.$fieldLon + '"]').val(LeafBE.$marker.getLatLng().lng);
            // set hidden fields values
            // Extract GeoJson from featureGroup
            var data = drawnItems.toGeoJSON();
            console.log(data);

            // Stringify the GeoJson
            var convertedData = JSON.stringify(data);

            // fill form textarea with new GeoJSON-data
            $('textarea[data-formengine-input-name="' + LeafBE.$fieldDataName + '"]').val(convertedData);

            // mark fields as changed for re-evaluation and revalidate the form,
            // this is e.g. needed when this wizard is used on inline elements
            FormEngine.Validation.markFieldAsChanged($('input[name="' + LeafBE.$fieldDataName + '"]'));
            // FormEngine.Validation.markFieldAsChanged($('input[name="' + LeafBE.$fieldLon + '"]'));
            FormEngine.Validation.validate();

            // close map after import of coordinates.
            $('#t3js-location-map-wrap').removeClass('active');
        });
        // close overlay without any further action
        $('#t3js-ttaddress-close-map').on('click', function () {
            $('#t3js-location-map-wrap').removeClass('active');
        });
    };


    LeafBE.initializeEvents = function (element) {
        $(element).on('click', function () {
            console.log('clicked in initializeEvents');
            if (LeafBE.$map !== null) {
                LeafBE.$map.remove();
                LeafBE.$map = null;
            }
            LeafBE.init($(this));
            LeafBE.createMap();
            $('#t3js-location-map-wrap').addClass('active');
        });
    };

    // reinit when form has changes, e.g. inline relations loaded using ajax
    LeafBE.reinitialize = FormEngine.reinitialize;
    FormEngine.reinitialize = function () {
        LeafBE.reinitialize();
        if ($('.vectordrawWizard').length) {
            LeafBE.initializeEvents('.vectordrawWizard');
        }
    };
    //LeafBE.addMapMarkup();
    LeafBE.initializeEvents('.vectordrawWizard');
    return LeafBE;
});
