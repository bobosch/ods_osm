function mapCenter(oMap,fLat,fLon,iZoom){
	var oLonLat = new OpenLayers.LonLat(fLon,fLat).transform(new OpenLayers.Projection('EPSG:4326'), oMap.getProjectionObject());
	oMap.setCenter(oLonLat,iZoom);
}

function mapMarker(oMap,oLayer,fLat,fLon,sIcon,iSizeX,iSizeY,iOffsetX,iOffsetY,sText,iPopup,initialPopup){
	var oLonLat = new OpenLayers.LonLat(fLon,fLat).transform(new OpenLayers.Projection('EPSG:4326'), oMap.getProjectionObject());
	var oSize = new OpenLayers.Size(iSizeX,iSizeY);
	var oOffset = new OpenLayers.Pixel(iOffsetX,iOffsetY);
	var oIcon = new OpenLayers.Icon(sIcon,oSize,oOffset);
	var oMarker = new OpenLayers.Marker(oLonLat,oIcon);

	if(sText){
		var AutoSizeFramedCloud = OpenLayers.Class(OpenLayers.Popup.FramedCloud, {
			'autoSize': true
		});

		var feature = new OpenLayers.Feature(oLayer, oLonLat);
		feature.closeBox = true;
		feature.popupClass = AutoSizeFramedCloud;
		feature.data.popupContentHTML = sText;
		feature.data.overflow = 'auto';
		oMarker.feature = feature;

		if (initialPopup) {
			var popup = new OpenLayers.Popup.FramedCloud(
				"popup", oLonLat, null, sText, null, true
			);
			oMap.addPopup(popup);
			popup.show();
		}

		var mouseAction = function (evt) {
			if (this.popup == null) {
				if(iPopup==2) {
					this.popup = this.createPopup();
					oMap.addPopup(this.popup);
				} else {
					this.popup = this.createPopup(this.closeBox);
					if (iPopup==3)
						oMap.addPopup(this.popup,true);
					else
						oMap.addPopup(this.popup);
				}
				this.popup.show();
			} else {
				if(evt.type=='mousedown') {
					// exclusive uses removePopup(), so need to addPopup() again
					if (iPopup==3) {
						oMap.addPopup(this.popup,true);
						this.popup.show();
					} else {
						this.popup.toggle();
					}
				}
				if(evt.type=='mouseover') this.popup.show();
				if(evt.type=='mouseout') this.popup.hide();
			}
			currentPopup = this.popup;
			OpenLayers.Event.stop(evt);
		};
		if(iPopup==2){
			oMarker.events.register('mouseover', feature, mouseAction);
			oMarker.events.register('mouseout', feature, mouseAction);
		}else{
			oMarker.events.register('mousedown', feature, mouseAction);
		}
	}

	oLayer.addMarker(oMarker);
}

function mapGpx_new(oMap,sFilename,sTitle,sColor,iWidth){
	var ext=sFilename.split('.').pop();
	var sFormat;
	switch(ext){
		case 'gpx':
			oFormat=OpenLayers.Format.GPX;
			break;
		case 'json':
			oFormat=OpenLayers.Format.GeoJSON;
			break;
		case 'kml':
			oFormat=OpenLayers.Format.KML;
			break;
		case 'wkt':
			oFormat=OpenLayers.Format.WKT;
			break;
	}

	var oLayer = new OpenLayers.Layer.Vector(sTitle, {
		strategies: [new OpenLayers.Strategy.Fixed()],
		protocol: new OpenLayers.Protocol.HTTP({
			url: sFilename,
			format: oFormat,
			style: {
				strokeColor: sColor,
				strokeWidth: iWidth,
				strokeOpacity: 1
			},
			projection: new OpenLayers.Projection('EPSG:4326')
		})
	})
	oMap.addLayer(oLayer);
}

function mapGpx(oMap,sFilename,sTitle,sColor,iWidth,bVisible){
	var ext=sFilename.split('.').pop();
	var sFormat;
	switch(ext){
		case 'gpx':
			oFormat=OpenLayers.Format.GPX;
			break;
		case 'json':
			oFormat=OpenLayers.Format.GeoJSON;
			break;
		case 'kml':
			oFormat=OpenLayers.Format.KML;
			break;
		case 'wkt':
			oFormat=OpenLayers.Format.WKT;
			break;
	}
	var oLayer = new OpenLayers.Layer.GML(sTitle,sFilename,{
		format: oFormat,
		style: {
			strokeColor: sColor,
			strokeWidth: iWidth,
			strokeOpacity: 1
		},
		visibility: bVisible,
		projection: new OpenLayers.Projection('EPSG:4326')
	});
	oMap.addLayer(oLayer);
}

function mapVector(oMap,sTitle,aData){
	var vectors = new OpenLayers.Layer.Vector(sTitle);
	oMap.addLayer(vectors);
	var format = new OpenLayers.Format.GeoJSON({
// 		'internalProjection': new OpenLayers.Projection("EPSG:900913"),
		'internalProjection': oMap.baseLayer.projection,
		'externalProjection': new OpenLayers.Projection('EPSG:4326')
	});
	var features = format.read(aData);
// 	var bounds;
	if(features) {
/*		if(features.constructor != Array) {
			features = [features];
		}
		for(var i=0; i<features.length; ++i) {
			if (!bounds) {
				bounds = features[i].geometry.getBounds();
			} else {
				bounds.extend(features[i].geometry.getBounds());
			}

		}*/
		vectors.addFeatures(features);
/*		oMap.zoomToExtent(bounds);
		var plural = (features.length > 1) ? 's' : '';*/
	}
}

function getTileURL(bounds) {
	var res = this.map.getResolution();
	var x = Math.round((bounds.left - this.maxExtent.left) / (res * this.tileSize.w));
	var y = Math.round((this.maxExtent.top - bounds.top) / (res * this.tileSize.h));
	var z = this.map.getZoom();
	var limit = Math.pow(2, z);
	if (y < 0 || y >= limit) {
		return null;
	} else {
		x = ((x % limit) + limit) % limit;
		url = this.url;
		if(this.urlFormat){
			path = 'x=' + x + '&y=' + y + '&z=' + z;
		} else {
			path = z + '/' + x + '/' + y + '.' + this.type;
		}
		if (url instanceof Array) {
			url = this.selectUrl(path, url);
		}
		return url+path;
	}
}
