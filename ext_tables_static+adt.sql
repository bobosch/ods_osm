# TYPO3 Extension Manager dump 1.1
#
# Host: localhost    Database: typo3
#--------------------------------------------------------


#
# Table structure for table "tx_odsosm_layer"
#
DROP TABLE IF EXISTS tx_odsosm_layer;
CREATE TABLE tx_odsosm_layer (
  uid int(10) unsigned NOT NULL auto_increment,
  pid int(10) unsigned NOT NULL default '0',
  tstamp int(10) unsigned NOT NULL default '0',
  crdate int(10) unsigned NOT NULL default '0',
  cruser_id int(10) unsigned NOT NULL default '0',
  sorting int(10) unsigned NOT NULL default '0',
  deleted tinyint(1) unsigned NOT NULL default '0',
  hidden tinyint(1) unsigned NOT NULL default '0',
  title varchar(64) NOT NULL default '',
  overlay tinyint(1) unsigned NOT NULL default '0',
  javascript_include varchar(255) NOT NULL default '',
  javascript_leaflet varchar(1024) NOT NULL default '',
  javascript_openlayers varchar(1024) NOT NULL default '',
  javascript_openlayers3 varchar(1024) NOT NULL default '',
  static_url varchar(255) NOT NULL default '',
  tile_url varchar(64) NOT NULL default '',
  max_zoom tinyint(2) unsigned NOT NULL default '0',
  subdomains varchar(8) NOT NULL default '',
  attribution varchar(300) NOT NULL default '',
  homepage varchar(255) NOT NULL default '',
  PRIMARY KEY (uid),
  KEY parent (pid)
);


INSERT INTO tx_odsosm_layer VALUES ('1', '0', '0', '0', '0', '512', '0', '0', 'Mapnik', '0', '', '', '', '', 'http://staticmap.openstreetmap.de/staticmap.php?center=###lat###,###lon###&zoom=###zoom###&size=###width###x###height###&maptype=mapnik', 'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', '19', '', '© <a href="http://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>', 'http://www.openstreetmap.org/');
INSERT INTO tx_odsosm_layer VALUES ('3', '0', '0', '0', '0', '1024', '0', '0', 'CycleMap', '0', '', '', '', '', 'http://staticmap.openstreetmap.de/staticmap.php?center=###lat###,###lon###&zoom=###zoom###&size=###width###x###height###&maptype=cylce', 'http://{s}.tile.opencyclemap.org/cycle/{z}/{x}/{y}.png', '19', '', '© <a href="http://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>', 'http://www.opencyclemap.org/');
INSERT INTO tx_odsosm_layer VALUES ('4', '0', '0', '0', '0', '1792', '0', '0', 'Seamarks', '1', '', '', '', '', '', 'http://tiles.openseamap.org/seamark/{z}/{x}/{y}.png', '0', '', '', 'http://www.openseamap.org/');
INSERT INTO tx_odsosm_layer VALUES ('5', '0', '0', '0', '0', '1280', '0', '0', 'OpenPisteMap', '1', '', '', '', '', '', 'http://tiles.openpistemap.org/nocontours/{z}/{x}/{y}.png', '0', '', '', 'http://openpistemap.org/');
INSERT INTO tx_odsosm_layer VALUES ('6', '0', '0', '0', '0', '2048', '0', '1', 'Google Streets', '0', 'http://maps.google.com/maps/api/js?v=3&amp;sensor=false', '', 'new OpenLayers.Layer.Google(\'###TITLE###\',{\'sphericalMercator\':true,numZoomLevels:20})', '', '', '', '0', '', '', 'http://maps.google.com/');
INSERT INTO tx_odsosm_layer VALUES ('7', '0', '0', '0', '0', '2304', '0', '1', 'Google Physical', '0', 'http://maps.google.com/maps/api/js?v=3&amp;sensor=false', '', 'new OpenLayers.Layer.Google(\'###TITLE###\',{type:google.maps.MapTypeId.TERRAIN,\'sphericalMercator\':true,numZoomLevels:16})', '', '', '', '0', '', '', 'http://maps.google.com/');
INSERT INTO tx_odsosm_layer VALUES ('8', '0', '0', '0', '0', '2560', '0', '1', 'Google Satellite', '0', 'http://maps.google.com/maps/api/js?v=3&amp;sensor=false', '', 'new OpenLayers.Layer.Google(\'###TITLE###\',{type:google.maps.MapTypeId.SATELLITE,\'sphericalMercator\':true,numZoomLevels:22})', '', '', '', '0', '', '', 'http://maps.google.com/');
INSERT INTO tx_odsosm_layer VALUES ('9', '0', '0', '0', '0', '2816', '0', '1', 'Google Hybrid', '0', 'http://maps.google.com/maps/api/js?v=3&amp;sensor=false', '', 'new OpenLayers.Layer.Google(\'###TITLE###\',{type:google.maps.MapTypeId.HYBRID,\'sphericalMercator\':true,numZoomLevels:20})', '', '', '', '0', '', '', 'http://maps.google.com/');
INSERT INTO tx_odsosm_layer VALUES ('10', '0', '0', '0', '0', '3072', '0', '1', 'Bing Road', '0', '', '', 'new OpenLayers.Layer.Bing({name:\'###TITLE###\',key:\'###STATIC_SCRIPT###\',type:\'Road\'})', '', '', '', '0', '', '', 'http://www.bing.com/maps/');
INSERT INTO tx_odsosm_layer VALUES ('11', '0', '0', '0', '0', '3328', '0', '1', 'Bing Aerial', '0', '', '', 'new OpenLayers.Layer.Bing({name:\'###TITLE###\',key:\'###STATIC_SCRIPT###\',type:\'Aerial\'})', '', '', '', '0', '', '', 'http://www.bing.com/maps/');
INSERT INTO tx_odsosm_layer VALUES ('12', '0', '0', '0', '0', '3584', '0', '1', 'Bing Hybrid', '0', '', '', 'new OpenLayers.Layer.Bing({name:\'###TITLE###\',key:\'###STATIC_SCRIPT###\',type:\'AerialWithLabels\'})', '', '', '', '0', '', '', 'http://www.bing.com/maps/');
INSERT INTO tx_odsosm_layer VALUES ('13', '0', '0', '0', '0', '1536', '0', '0', 'ÖPNV Deutschland', '0', '', '', '', '', '', 'http://tile.xn--pnvkarte-m4a.de/tilegen/{z}/{x}/{y}.png', '19', '', '', 'http://www.öpnvkarte.de/');
INSERT INTO tx_odsosm_layer VALUES ('14', '0', '0', '0', '0', '1152', '0', '0', 'Hike & Bike Map', '0', '', '', '', '', '', 'http://{s}.tiles.wmflabs.org/hikebike/{z}/{x}/{y}.png', '0', '', '', 'http://www.hikebikemap.de/');
INSERT INTO tx_odsosm_layer VALUES ('15', '0', '0', '0', '0', '1592', '0', '0', 'Hillshading (NASA SRTM3 v2)', '1', '', '', '', '', '', 'http://{s}.tiles.wmflabs.org/hillshading/{z}/{x}/{y}.png', '0', '', '', '');
INSERT INTO tx_odsosm_layer VALUES ('16', '0', '0', '0', '0', '1600', '0', '0', 'By Night', '1', '', '', '', '', '', 'http://{s}.tiles.wmflabs.org/lighting/{z}/{x}/{y}.png', '0', '', '', '');
INSERT INTO tx_odsosm_layer VALUES ('17', '0', '0', '0', '0', '1920', '0', '0', 'Hiking routes', '1', '', '', '', '', '', 'http://tile.waymarkedtrails.org/hiking/{z}/{x}/{y}.png', '0', '', '', 'http://hiking.waymarkedtrails.org/');
INSERT INTO tx_odsosm_layer VALUES ('18', '0', '0', '0', '0', '640', '0', '0', 'Mapnik BW', '0', '', '', '', '', '', 'http://{s}.tiles.wmflabs.org/bw-mapnik/{z}/{x}/{y}.png', '19', '', '© <a href="http://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>', 'http://toolserver.org/~osm/styles/');
INSERT INTO tx_odsosm_layer VALUES ('19', '0', '0', '0', '1', '1568', '0', '0', 'MapSurfer.Net Road', '0', '', '', 'new OpenLayers.Layer.TMS(\'###TITLE###\',\'http://tiles1.mapsurfer.net/tms_r.ashx?\',{type:\'png\',getURL:getTileURL,urlFormat:1,displayOutsideMaxExtent:true})', '', '', '', '0', '', '', 'http://www.mapsurfer.net/');
INSERT INTO tx_odsosm_layer VALUES ('20', '0', '0', '0', '1', '1584', '0', '0', 'MapSurfer.Net Topographic', '0', '', '', 'new OpenLayers.Layer.TMS(\'###TITLE###\',\'http://tiles2.mapsurfer.net/tms_t.ashx?\',{type:\'png\',getURL:getTileURL,urlFormat:1,displayOutsideMaxExtent:true})', '', '', '', '0', '', '', 'http://www.mapsurfer.net/');
INSERT INTO tx_odsosm_layer VALUES ('21', '0', '0', '0', '1', '1588', '0', '0', 'MapSurfer.Net Hybrid', '1', '', '', 'new OpenLayers.Layer.TMS(\'###TITLE###\',\'http://tiles3.mapsurfer.net/tms_h.ashx?\',{numZoomLevels:19,isBaseLayer:false,type:\'png\',getURL:getTileURL,urlFormat:1,displayOutsideMaxExtent:true,###VISIBLE###})', '', '', '', '0', '', '', 'http://www.mapsurfer.net/');
INSERT INTO tx_odsosm_layer VALUES ('22', '0', '0', '0', '1', '3840', '0', '1', 'Yahoo Map', '0', 'http://api.maps.yahoo.com/ajaxymap?v=3.0&appid=.bYax5fV34E6Z8bDZ95xJr9KdShYAh.o8Ajkx3uULLEwur9ASwZS52kkIgj.__6dvH4-', '', 'new OpenLayers.Layer.Yahoo(\'###TITLE###\',{sphericalMercator:true})', '', '', '', '0', '', '', 'http://maps.yahoo.com/');
INSERT INTO tx_odsosm_layer VALUES ('23', '0', '0', '0', '1', '4096', '0', '1', 'Yahoo Satellite', '0', 'http://api.maps.yahoo.com/ajaxymap?v=3.0&appid=.bYax5fV34E6Z8bDZ95xJr9KdShYAh.o8Ajkx3uULLEwur9ASwZS52kkIgj.__6dvH4-', '', 'new OpenLayers.Layer.Yahoo(\'###TITLE###\',{type:YAHOO_MAP_SAT,sphericalMercator:true})', '', '', '', '0', '', '', 'http://maps.yahoo.com/');
INSERT INTO tx_odsosm_layer VALUES ('24', '0', '0', '0', '1', '4352', '0', '1', 'Yahoo Hybrid', '0', 'http://api.maps.yahoo.com/ajaxymap?v=3.0&appid=.bYax5fV34E6Z8bDZ95xJr9KdShYAh.o8Ajkx3uULLEwur9ASwZS52kkIgj.__6dvH4-', '', 'new OpenLayers.Layer.Yahoo(\'###TITLE###\',{type:YAHOO_MAP_HYB,sphericalMercator:true})', '', '', '', '0', '', '', 'http://maps.yahoo.com/');
INSERT INTO tx_odsosm_layer VALUES ('25', '0', '0', '0', '0', '1088', '0', '0', 'TransportMap', '0', '', '', '', '', '', 'http://{s}.tile2.opencyclemap.org/transport/{z}/{x}/{y}.png', '19', '', '', 'http://wiki.openstreetmap.org/wiki/User:Stanton/OSM_Transport_Map');
INSERT INTO tx_odsosm_layer VALUES ('26', '0', '0', '0', '0', '1056', '0', '0', 'MapQuest', '0', '', '', '', '', '', 'http://otile{s}.mqcdn.com/tiles/1.0.0/osm/{z}/{x}/{y}.png', '19', '1234', 'Tiles courtesy of <a href="http://www.mapquest.com/" target="_blank">MapQuest</a> <img src="http://developer.mapquest.com/content/osm/mq_logo.png">', 'http://www.mapquest.com/');
INSERT INTO tx_odsosm_layer VALUES ('27', '0', '0', '0', '0', '1056', '0', '0', 'MapQuest Open Aerial', '0', '', '', '', '', '', 'http://otile{s}.mqcdn.com/tiles/1.0.0/sat/{z}/{x}/{y}.png', '12', '1234', 'Tiles courtesy of <a href="http://www.mapquest.com/" target="_blank">MapQuest</a> <img src="http://developer.mapquest.com/content/osm/mq_logo.png">', 'http://www.mapquest.com/');
INSERT INTO tx_odsosm_layer VALUES ('28', '0', '0', '0', '0', '1984', '0', '0', 'Cycling routes', '1', '', '', '', '', '', 'http://tile.waymarkedtrails.org/cycling/{z}/{x}/{y}.png', '0', '', '', 'http://cycling.waymarkedtrails.org/');
INSERT INTO tx_odsosm_layer VALUES ('29', '0', '0', '0', '0', '1552', '0', '0', 'Stamen Toner', '0', '', '', '', '', '', 'http://{s}.tile.stamen.com/toner/{z}/{x}/{y}.png', '0', 'abc', 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, under <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a>. Data by <a href="http://openstreetmap.org">OpenStreetMap</a>, under <a href="http://creativecommons.org/licenses/by-sa/3.0">CC BY SA</a>.', 'http://maps.stamen.com/#toner/');
INSERT INTO tx_odsosm_layer VALUES ('30', '0', '0', '0', '0', '1553', '0', '0', 'Stamen Watercolor', '0', '', '', '', '', '', 'http://{s}.tile.stamen.com/watercolor/{z}/{x}/{y}.png', '0', 'abc', 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, under <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a>. Data by <a href="http://openstreetmap.org">OpenStreetMap</a>, under <a href="http://creativecommons.org/licenses/by-sa/3.0">CC BY SA</a>.', 'http://maps.stamen.com/#watercolor/');
INSERT INTO tx_odsosm_layer VALUES ('31', '0', '0', '0', '0', '2016', '0', '0', 'Public Transport Lines', '1', '', '', '', '', '', 'http://www.openptmap.org/tiles/{z}/{x}/{y}.png', '0', '', '', 'http://www.openptmap.org/');
INSERT INTO tx_odsosm_layer VALUES ('32', '0', '0', '0', '0', '1555', '0', '0', 'Stamen Terrain Labels', '1', '', '', '', '', '', 'http://{s}.tile.stamen.com/terrain-labels/{z}/{x}/{y}.png', '0', 'abc', 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, under <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a>. Data by <a href="http://openstreetmap.org">OpenStreetMap</a>, under <a href="http://creativecommons.org/licenses/by-sa/3.0">CC BY SA</a>.', 'http://maps.stamen.com/');
