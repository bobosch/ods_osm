OpenLayers.Layer.OSM.Toolserver = OpenLayers.Class(OpenLayers.Layer.OSM, {
	initialize: function(name, path, options) {
		var url = [
			"http://a.www.toolserver.org/tiles/" + path + "/${z}/${x}/${y}.png", 
			"http://b.www.toolserver.org/tiles/" + path + "/${z}/${x}/${y}.png", 
			"http://c.www.toolserver.org/tiles/" + path + "/${z}/${x}/${y}.png"
		];
		options = OpenLayers.Util.extend({numZoomLevels: 19}, options);
		OpenLayers.Layer.OSM.prototype.initialize.apply(this, [name, url, options]);
	},
	CLASS_NAME: "OpenLayers.Layer.OSM.Toolserver"
});
