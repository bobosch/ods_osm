
OpenLayers.Layer.OPM = OpenLayers.Class(OpenLayers.Layer.OSM, {
	initialize: function(name, url, options, url_args) {
		this.url_args = url_args;
		var newArguments = [name, url, options];
		OpenLayers.Layer.OSM.prototype.initialize.apply(this, newArguments);
	},

	getURL: function (bounds) {
		var newArguments = [bounds];
		if (this.url_args == undefined) url_args = "";
		else url_args = this.url_args;
		return OpenLayers.Layer.OSM.prototype.getURL.apply(this, newArguments) + url_args;
	}
});

OpenLayers.Layer.OSM.opm = OpenLayers.Class(OpenLayers.Layer.OPM, {
    initialize: function(name, options, args) {
        var url = [
            "http://tiles.openpistemap.org/contours/${z}/${x}/${y}.png"
        ];
        options = OpenLayers.Util.extend({ numZoomLevels: 18 }, options);
        var newArguments = [name, url, options, args];
        OpenLayers.Layer.OPM.prototype.initialize.apply(this, newArguments);
    },

    CLASS_NAME: "OpenLayers.Layer.OSM.opm"
});

OpenLayers.Layer.OSM.opm_nocontours = OpenLayers.Class(OpenLayers.Layer.OPM, {
    initialize: function(name, options, args) {
        var url = [
            "http://tiles.openpistemap.org/nocontours/${z}/${x}/${y}.png"
        ];
        options = OpenLayers.Util.extend({ numZoomLevels: 18 }, options);
        var newArguments = [name, url, options, args];
        OpenLayers.Layer.OPM.prototype.initialize.apply(this, newArguments);
    },

    CLASS_NAME: "OpenLayers.Layer.OSM.opm_nocontours"
});

OpenLayers.Layer.OSM.opm_debug_contours = OpenLayers.Class(OpenLayers.Layer.OPM, {
    initialize: function(name, options, args) {
        var url = [
            "http://tiles.dev.openpistemap.org/contours/${z}/${x}/${y}.png"
        ];
        options = OpenLayers.Util.extend({ numZoomLevels: 18 }, options);
        var newArguments = [name, url, options, args];
        OpenLayers.Layer.OPM.prototype.initialize.apply(this, newArguments);
    },

    CLASS_NAME: "OpenLayers.Layer.OSM.opm"
});


