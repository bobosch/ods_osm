--
-- Table structure for table 'fe_users'
--
CREATE TABLE fe_users (
	tx_odsosm_lon decimal(9,6) NOT NULL DEFAULT '0.000000',
	tx_odsosm_lat decimal(8,6) NOT NULL DEFAULT '0.000000'
);

--
-- Table structure for table 'fe_groups'
--
CREATE TABLE fe_groups (
	tx_odsosm_marker int(10) unsigned NOT NULL DEFAULT '0'
);

--
-- Table structure for table 'sys_category'
--
CREATE TABLE sys_category (
	tx_odsosm_marker int(10) unsigned NOT NULL DEFAULT '0'
);

--
-- Table structure for table 'tx_odsosm_geocache'
--
CREATE TABLE tx_odsosm_geocache (
	uid int(10) unsigned NOT NULL auto_increment,
	pid int(10) unsigned NOT NULL DEFAULT '0',
	tstamp int(10) unsigned NOT NULL DEFAULT '0',
	crdate int(10) unsigned NOT NULL DEFAULT '0',
	deleted tinyint(1) unsigned NOT NULL DEFAULT '0',
	cache_hit int(10) unsigned NOT NULL DEFAULT '0',
	service_hit int(10) unsigned NOT NULL DEFAULT '0',
	search_city varchar(255) NOT NULL DEFAULT '',
	country char(128) NOT NULL DEFAULT '',
	state varchar(255) NOT NULL DEFAULT '',
	city varchar(255) NOT NULL DEFAULT '',
	zip char(5) NOT NULL DEFAULT '',
	street varchar(255) NOT NULL DEFAULT '',
	housenumber varchar(5) NOT NULL DEFAULT '',
	lon decimal(9,6) NOT NULL DEFAULT '0.000000',
	lat decimal(8,6) NOT NULL DEFAULT '0.000000',

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY zip (zip)
);

--
-- Table structure for table 'tx_odsosm_layer'
--
CREATE TABLE tx_odsosm_layer (
	uid int(10) unsigned NOT NULL auto_increment,
	pid int(10) unsigned NOT NULL DEFAULT '0',
	tstamp int(10) unsigned NOT NULL DEFAULT '0',
	crdate int(10) unsigned NOT NULL DEFAULT '0',
	sorting int(10) unsigned NOT NULL DEFAULT '0',
	deleted tinyint(1) unsigned NOT NULL DEFAULT '0',
	hidden tinyint(1) unsigned NOT NULL DEFAULT '0',
	title varchar(64) NOT NULL DEFAULT '',
	overlay tinyint(1) unsigned NOT NULL DEFAULT '0',
	javascript_include varchar(255) NOT NULL DEFAULT '',
	javascript_openlayers varchar(1024) NOT NULL DEFAULT '',
	static_url varchar(255) NOT NULL DEFAULT '',
	tile_url varchar(255) NOT NULL DEFAULT '',
	tile_https tinyint(2) unsigned NOT NULL DEFAULT '0',
	min_zoom tinyint(2) unsigned NOT NULL DEFAULT '0',
	max_zoom tinyint(2) unsigned NOT NULL DEFAULT '0',
	subdomains varchar(8) NOT NULL DEFAULT '',
	attribution varchar(300) NOT NULL default '',
	homepage varchar(255) NOT NULL DEFAULT '',

	PRIMARY KEY (uid),
	KEY parent (pid)
);

--
-- Table structure for table 'tx_odsosm_marker'
--
CREATE TABLE tx_odsosm_marker (
	uid int(10) unsigned NOT NULL auto_increment,
	pid int(10) unsigned NOT NULL DEFAULT '0',
	tstamp int(10) unsigned NOT NULL DEFAULT '0',
	crdate int(10) unsigned NOT NULL DEFAULT '0',
	deleted tinyint(1) unsigned NOT NULL DEFAULT '0',
	title tinytext NOT NULL,
	icon text NOT NULL,
	size_x smallint(10) unsigned NOT NULL DEFAULT '0',
	size_y smallint(10) unsigned NOT NULL DEFAULT '0',
	offset_x smallint(11) NOT NULL DEFAULT '0',
	offset_y smallint(11) NOT NULL DEFAULT '0',

	PRIMARY KEY (uid),
	KEY parent (pid)
);

--
-- Table structure for table 'tx_odsosm_track'
--
CREATE TABLE tx_odsosm_track (
	uid int(10) unsigned NOT NULL auto_increment,
	pid int(10) unsigned NOT NULL DEFAULT '0',
	tstamp int(10) unsigned NOT NULL DEFAULT '0',
	crdate int(10) unsigned NOT NULL DEFAULT '0',
	deleted tinyint(1) unsigned NOT NULL DEFAULT '0',
	hidden tinyint(1) unsigned NOT NULL DEFAULT '0',
	title tinytext NOT NULL,
	color varchar(10) NOT NULL DEFAULT '#37b7ff',
	width tinyint(3) unsigned NOT NULL DEFAULT '5',
	file int(11) unsigned NOT NULL DEFAULT '0',
	min_lon decimal(9,6) NOT NULL DEFAULT '0.000000',
	min_lat decimal(8,6) NOT NULL DEFAULT '0.000000',
	max_lon decimal(9,6) NOT NULL DEFAULT '0.000000',
	max_lat decimal(8,6) NOT NULL DEFAULT '0.000000',

	PRIMARY KEY (uid),
	KEY parent (pid)
);

--
-- Table structure for table 'tx_odsosm_vector'
--
CREATE TABLE tx_odsosm_vector (
	uid int(10) unsigned NOT NULL auto_increment,
	pid int(10) unsigned NOT NULL DEFAULT '0',
	tstamp int(10) unsigned NOT NULL DEFAULT '0',
	crdate int(10) unsigned NOT NULL DEFAULT '0',
	deleted tinyint(1) unsigned NOT NULL DEFAULT '0',
	hidden tinyint(1) unsigned NOT NULL DEFAULT '0',
	title tinytext NOT NULL,
	color varchar(10) NOT NULL DEFAULT '#3388ff',
	width tinyint(3) unsigned NOT NULL DEFAULT '3',
	data text NOT NULL,
	file int(11) unsigned NOT NULL DEFAULT '0',
	properties text NOT NULL,
	properties_from_file tinyint(1) unsigned NOT NULL DEFAULT '0',
	min_lon decimal(9,6) NOT NULL DEFAULT '0.000000',
	min_lat decimal(8,6) NOT NULL DEFAULT '0.000000',
	max_lon decimal(9,6) NOT NULL DEFAULT '0.000000',
	max_lat decimal(8,6) NOT NULL DEFAULT '0.000000',

	PRIMARY KEY (uid),
	KEY parent (pid)
);
