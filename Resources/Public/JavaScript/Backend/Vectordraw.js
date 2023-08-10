define(['jquery', 'TYPO3/CMS/Backend/Icons', 'TYPO3/CMS/Backend/FormEngine', 'TYPO3/CMS/OdsOsm/Leaflet/Core/leaflet', 'TYPO3/CMS/OdsOsm/Leaflet/leaflet-draw/leaflet.draw'], function ($, Icons, FormEngine) {
    'use strict';

    let LeafBE = {
        $element: null,
        $min_lat: null,
        $max_lat: null,
        $min_lon: null,
        $max_lon: null,
        $latitude: null,
        $longitude: null,
        $fieldData: null,
        $fieldLat: null,
        $fieldLon: null,
        $tilesUrl: null,
        $tilesCopy: null,
        $zoomLevel: 7,
        $map: null,
        $iconClose: null,
        $drawnItems: null,
        $drawControl: null
    };

    // Load icon via TYPO3 Icon-API and requireJS
    Icons.getIcon('actions-close', Icons.sizes.small, null, null).then(function(markup) {
        LeafBE['$iconClose']= markup;
    });

    LeafBE.init = function (element) {
        // basic variable initialisation, uses data vars on the trigger button
        LeafBE.$element = element;
        LeafBE.$labelTitle = LeafBE.$element.attr('data-label-title');
        LeafBE.$labelClose = LeafBE.$element.attr('data-label-close');
        LeafBE.$labelImport = LeafBE.$element.attr('data-label-import');
        LeafBE.$min_lon = LeafBE.$element.attr('data-minlon');
        LeafBE.$max_lon = LeafBE.$element.attr('data-maxlon');
        LeafBE.$min_lat = LeafBE.$element.attr('data-minlat');
        LeafBE.$max_lat = LeafBE.$element.attr('data-maxlat');
        LeafBE.$latitude = LeafBE.$element.attr('data-lat');
        LeafBE.$longitude = LeafBE.$element.attr('data-lon');
        LeafBE.$tilesUrl = LeafBE.$element.attr('data-tiles');
        LeafBE.$tilesCopy = LeafBE.$element.attr('data-copy');
        LeafBE.$fieldLat = LeafBE.$element.attr('data-namelat');
        LeafBE.$fieldLon = LeafBE.$element.attr('data-namelon');
        LeafBE.$fieldDataName = LeafBE.$element.attr('data-fieldName');
        LeafBE.$fieldDataValue = LeafBE.$element.attr('data-fieldValue');

        // add the container to display the map as a nice overlay
        if (!$('#t3js-location-map-wrap').length) {
            LeafBE.addMapMarkup();
        }
    };

    LeafBE.addMapMarkup = function () {
        $('body').append(
            '<div id="t3js-location-map-wrap">' +
            '<div class="t3js-location-map-title">' +
            '<div class="btn-group"><a href="#" class="btn btn-icon btn-default" title="' + LeafBE.$labelClose + '" id="t3js-ttaddress-close-map">' +
            LeafBE.$iconClose +
            '</a>' +
            '<a class="btn btn-default" href="#" title="' + LeafBE.$labelImport + '" id="t3js-ttaddress-import-position">' +
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

        // set to center point with default zoom level
        LeafBE.$map = L.map('t3js-location-map-container', {
            center: [LeafBE.$latitude, LeafBE.$longitude],
            zoom: LeafBE.$zoomLevel
        });

        if (LeafBE.$min_lat && LeafBE.$min_lon && LeafBE.$max_lat && LeafBE.$max_lon) {
            // if bounds are given, fit to it
            LeafBE.$map.fitBounds([
                [LeafBE.$min_lat, LeafBE.$min_lon],
                [LeafBE.$max_lat, LeafBE.$max_lon]
            ]);
        }

        L.tileLayer(LeafBE.$tilesUrl, {
            attribution: LeafBE.$tilesCopy
        }).addTo(LeafBE.$map);

        var drawnItems = new L.FeatureGroup().addTo(LeafBE.$map);

        L.control.layers({}, {'GeoJSON Data':drawnItems}, { position: 'topright', collapsed: false }).addTo(LeafBE.$map);

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
                },
                circle: false,
                circlemarker: false
            }
        }));

        if (LeafBE.$fieldDataValue) {
            // convert data from data field to JavaScript object
            var myGeoJson = JSON.parse(LeafBE.$fieldDataValue.toString());

            var geoJsonGroup = L.geoJSON(myGeoJson);

            // add feature by feature to drawnItems layer
            addNonGroupLayers(geoJsonGroup, drawnItems);

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
        }

        // Object created - add to feature group
        LeafBE.$map.on(L.Draw.Event.CREATED, function(event) {
            var layer = event.layer;
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

        // import drawn vector data and close overlay
        $('#t3js-ttaddress-import-position').on('click', function () {

            // Extract GeoJson from featureGroup
            var data = drawnItems.toGeoJSON();

            // Stringify the GeoJson
            var convertedData = JSON.stringify(data);

            // fill form textarea with new GeoJSON-data
            $('textarea[data-formengine-input-name="' + LeafBE.$fieldDataName + '"]').val(convertedData);

            // mark fields as changed for re-evaluation and revalidate the form,
            // this is e.g. needed when this wizard is used on inline elements
            FormEngine.Validation.markFieldAsChanged($('input[name="' + LeafBE.$fieldDataName + '"]'));
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
