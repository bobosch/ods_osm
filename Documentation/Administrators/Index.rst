==============
Administration
==============

.. _extension-configuration:

Extension Configuration
=======================

Some global settings of the extension may be configured in `Settings -> Extension Configuration -> ods_osm`.

Screenshot
----------

..  figure:: /Images/ExtensionConfiguration.png
    :class: with-shadow
    :alt: Screenshot with first part of the ods_osm settings in the extension configuration

    Screenshot of the ods_osm Extension Configuration

Reference
---------

+----------------------------+------------------------------------------------+-------------+
|           Option           |                   Description                  | Default     |
+============================+================================================+=============+
| Autocomplete longitude     | You can enable to search the coordinates on    | 1: If no    |
| and latitude               | geonames.org when saving an fe_users or        | coordinates |
|                            | tt_address element.                            | are set     |
+----------------------------+------------------------------------------------+-------------+
| Use service to find        | Use this service to get the coordinates of an  | 2:          |
| coordinates                | address. If you select “Only cache” you have to| Nominatim   |
|                            | fill the cache table manually. If you select a |             |
|                            | provider here, be aware that the fields “zip”, |             |
|                            | “city” and “country” of every address record   |             |
|                            | you save is sent to the provider.              |             |
+----------------------------+------------------------------------------------+-------------+
| Geo service contact email  | Enter a contact email address for the service  |             |
|                            | provider here. If not specified, email address |             |
|                            | of server admin is used.                       |             |
+----------------------------+------------------------------------------------+-------------+
| Geo service user name      | The GeoNames service requires a username       |             |
+----------------------------+------------------------------------------------+-------------+
| Default country            | Two letter countrycode used in search if no    | DE          |
|                            | country is specified.                          |             |
+----------------------------+------------------------------------------------+-------------+
| Enable address search cache| The result of the geo service is stored in     | 1           |
|                            | tx_odsosm_geocache if activated.               |             |
+----------------------------+------------------------------------------------+-------------+
| Use local javascripts and  | Activate this if you don't like to use the     | 1           |
| images                     | javascript files from CDNs but the local ones  |             |
|                            | from this extension.                           |             |
+----------------------------+------------------------------------------------+-------------+
| Default longitude          |                                                | 10.41       |
+----------------------------+------------------------------------------------+-------------+
| Default latitude           |                                                | 51.27       |
+----------------------------+------------------------------------------------+-------------+
| Default zoom               |                                                | 8           |
+----------------------------+------------------------------------------------+-------------+


.. _typoscript-configuration:

TypoScript Configuration
========================

It is mandatory to include the ods_osm TypoScript template "Template OpenStreetMap".
This template sets some default values for the ods_osm plugin. The defaults may be configured
with the TypoScript Constant Editor.

With these TypoScript defaults, the editor does not need to make a detailed configuration of the
ods_osm plugin. Selecting the wanted marker is suffician.

The dimenions of the map, the right JavaScript library and zoom level are set by the defaults.
But editors may set and overwrite the defaults in the plugin settings.


Reference
---------

.. |mpi| replace:: marker_popup_initial
.. |sls| replace:: show_layerswitcher
.. |uconm| replace:: use_coords_only_nomarker

.. |ol| replace:: openlayers

+-----------------+-----------+-------------------------------------+---------+
|     Property    | Data type |             Description             | Default |
+=================+===========+=====================================+=========+
| cluster         | boolean   | Cluster marker at lower map zoom.   | 0       |
+-----------------+-----------+-------------------------------------+---------+
| cluster_radius  | integer   | Cluster marker in given radius.     | 80      |
+-----------------+-----------+-------------------------------------+---------+
| external_control| boolean   || Allow control with GET or POST     | 0       |
|                 |           || lon: Map center longitude          |         |
|                 |           || lat: Map center latitudezoom: Map  |         |
|                 |           | zoom level                          |         |
|                 |           || layers: Comma separated list of    |         |
|                 |           | tx_odsosm_layer uid's               |         |
|                 |           || records: Comma separated list of   |         |
|                 |           | markers                             |         |
|                 |           || Don't forget to set no_cache=1     |         |
+-----------------+-----------+-------------------------------------+---------+
| height          | integer   | Height of the map div block. This   | 80vh    |
|                 |           | depends on your webdesign. The      |         |
|                 |           | default value is 80vh which means   |         |
|                 |           | 80% from current view port.         |         |
|                 |           |                                     |         |
|                 |           | You can set other values like       |         |
|                 |           | "80%", 480, 480px                   |         |
+-----------------+-----------+-------------------------------------+---------+
| icon            | IMAGE or  | Default marker image                | Library |
|                 | TEXT      |                                     | default |
|                 | object    |                                     |         |
+-----------------+-----------+-------------------------------------+---------+
| JSlibrary       | string    | JavaScript library: none / jquery   | none    |
+-----------------+-----------+-------------------------------------+---------+
| layer           | integer   || Comma separated list of            | 1       |
|                 | list      | tx_odsosm_layer uid's.              |         |
|                 |           || 1: Mapnik                          |         |
|                 |           || 2: SLUB Renderer                   |         |
|                 |           || 3: CycleMap                        |         |
|                 |           || 13: ÖPNV Deutschland               |         |
|                 |           || 14: Hike & Bike Map                |         |
|                 |           || 15: Hillshading (NASA SRTM3 v2)    |         |
|                 |           || 17: Hiking routes                  |         |
|                 |           || 18: Mapnik BW                      |         |
|                 |           || 19: MapSurfer.Net Road             |         |
|                 |           || 20: MapSurfer.Net Topographic      |         |
|                 |           || 21: MapSurfer.Net Hybrid           |         |
|                 |           || 25: TransportMap                   |         |
|                 |           || 28: Cycling routes                 |         |
|                 |           || 29: Stamen Toner                   |         |
|                 |           || 30: Stamen Watercolor              |         |
|                 |           || 31: Public Transport Lines         |         |
|                 |           || 32: Stamen Terrain Labels          |         |
|                 |           || 33: Railway Infrastructure         |         |
+-----------------+-----------+-------------------------------------+---------+
| library         | string    | Library: leaflet / openlayers /     | |ol|    |
|                 |           | openlayers3 / static                |         |
+-----------------+-----------+-------------------------------------+---------+
| marker          | array with| Tablenames and a comma separated    | see m_  |
|                 | table name| list of record ids.                 |         |
|                 | and       |                                     |         |
|                 | integer   |                                     |         |
|                 | list      |                                     |         |
+-----------------+-----------+-------------------------------------+---------+
| |mpi|           | integer   | Open popup of this marker           |         |
+-----------------+-----------+-------------------------------------+---------+
| no_marker       | boolean   || If no marker is set:               | 1       |
|                 |           || 0: Hide map                        |         |
|                 |           || 1: Show map                        |         |
+-----------------+-----------+-------------------------------------+---------+
| popup           | TS object | There are two additional fields:    | see p_  |
|                 |           | “group_title” and                   |         |
|                 |           | “group_description” filled with     |         |
|                 |           | group information.                  |         |
+-----------------+-----------+-------------------------------------+---------+
| position        | boolean   | Get current user postion from       | 0       |
|                 |           | browser to center the map.          |         |
+-----------------+-----------+-------------------------------------+---------+
| |sls|           | boolean   |                                     | 0       |
+-----------------+-----------+-------------------------------------+---------+
| show_popups     | boolean   || 0:No                               | 0       |
|                 |           || 1:Click                            |         |
|                 |           || 2:Hover                            |         |
+-----------------+-----------+-------------------------------------+---------+
| show_scalebar   | boolean   | Show a scale line on the map.       | 0       |
+-----------------+-----------+-------------------------------------+---------+
| |uconm|         | boolean   | Use the default coordinates only if | 0       |
|                 |           | no marker exists.                   |         |
+-----------------+-----------+-------------------------------------+---------+
| width           | integer   | Height of the map div block. This   | 80vw    |
|                 |           | depends on your webdesign. The      |         |
|                 |           | default value is 80vw which means   |         |
|                 |           | 80% from current view port.         |         |
|                 |           |                                     |         |
|                 |           | You can set other values like       |         |
|                 |           | "100%", 640, 640px                  |         |
+-----------------+-----------+-------------------------------------+---------+

Examples
--------

::

	plugin.tx_odsosm_pi1 {
		width = 800
		height = 600
		mouse_position = 1
	}

.. _m:

Markers
```````

..  code-block:: typoscript

	plugin.tx_odsosm_pi1 {
		marker {
			pages =
			fe_users =
			fe_groups =
			tx_odsosm_track =
		}
	}

.. _p:

Popups
``````

..  code-block:: typoscript

	plugin.tx_odsosm_pi1 {
		popup {
			fe_users = COA
			fe_users {
				9 = FILES
				9 {
					references {
						table = fe_users
						fieldName = image
					}
					renderObj = IMAGE
					renderObj {
						file {
							import.data = file:current:uid
							treatIdAsReference = 1
							width = 150c
							height = 150c
						}
						altText.data = file:current:alternative
						titleText.data = file:current:title
						stdWrap.typolink.parameter.data = file:current:link
					}
				}
				10 = TEXT
				10 {
					field = name
					wrap = <h2>|</h2>
					override = {field:first_name} {field:middle_name} {field:last_name}
					override.insertData = 1
					override.if.isFalse.field = name
				}
				20 = TEXT
				20.field = description
				20.htmlSpecialChars = 1
			}
			tt_address = COA
			tt_address {
				9 = FILES
				9 {
					references {
						table = tt_address
						fieldName = image
					}
					renderObj = IMAGE
					renderObj {
						file {
							import.data = file:current:uid
							treatIdAsReference = 1
							width = 150c
							height = 150c
						}
						altText.data = file:current:alternative
						titleText.data = file:current:title
						stdWrap.typolink.parameter.data = file:current:link
					}
				}
				10 = TEXT
				10.field = name
				10.wrap = <h2>|</h2>
				20 = TEXT
				20.field = description
				20.htmlSpecialChars = 0
			}
		}
	}



Icon Property
`````````````

..  code-block:: typoscript

	plugin.tx_odsosm_pi1 {
		icon {
			# IMAGE example
			fe_users = IMAGE
			fe_users {
				file = fileadmin/icon.png
				file.width = 60px
			}

			# HTML example
			fe_users = TEXT
			fe_users {
				value = <span>X</span>
				size_x=20
				size_y=30
				offset_x=10
				offset_y=15
			}
		}
	}
