Tutorial
========

Install Extensions via Composer
-------------------------------
The following steps are tested with TYPO3 10.4 LTS.

1. Install ods_osm

   ``composer require bobosch/ods-osm:^3.1``

2. Install tt_address

   ``composer req friendsoftypo3/tt-address``


Configure Extensions
--------------------

1. Include static template "OpenStreetMap" into your root-Template
2. Open or create an fe_users or tt_address record.
3. Enter zipcode or city and save the record (without closing it).
4. Scroll to the “Longitude” section and use the OSM logo to open a map.

   .. image:: ../Images/Coordinates.png

5. Click on the correct position.
6. Save the record again.
7. Insert a content element plugin “Openstreetmap” on your page.
8. Add your address record in “Marker to show”.

   .. image:: ../Images/MarkerToShow.png
