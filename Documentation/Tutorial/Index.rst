Tutorial
========

Install Extensions via Composer
-------------------------------

The following steps are tested with TYPO3 11.5 LTS.

1. Install ods_osm

   ``composer require bobosch/ods-osm:^4.2``

2. Install tt_address (optional)

   ``composer req friendsoftypo3/tt-address``


Configure Extensions
--------------------

1. Include static template "OpenStreetMap" into your root-template
2. (optional) Check settings with the TypoScript constant editor. See :ref:`typoscript-configuration`.
3. (optional) Check the extension configuration. See :ref:`extension-configuration`.


Create Record
-------------

1. Open or create an ``fe_users`` or ``tt_address`` record.
2. Enter zipcode or city and save the record (without closing it).

..  figure:: /Images/CreateNewAddressRecord.png
    :class: with-shadow
    :alt: Screenshot of form to create new address record with input fields for city, postalcode, longitute and latitude

    Example with only ZIP is inserted into tt_address form.

3. Scroll to the “Longitude” section and use the marker icon to open the map wizard.

..  figure:: /Images/EditAddressCoordinateWizard.png
    :class: with-shadow
    :alt: Screenshot of same tt_address form with coordinate wizard (map view) as modal overlay

    Change latitude and longigute with the map wizard.

4. Click on the correct position or move the marker and select "Import marker-coordinates".
5. Save the record again.

Place OSM Plugin
----------------

1. Insert a content element plugin "OpenStreetMap" on your page.
2. Add your address record in "Records to show”.

..  figure:: /Images/RecordsToShow.png
    :class: with-shadow
    :alt: Screenshot of the OSM plugin settings with selected tt_address record.

    Add tt_address record to OSM plugin field "Records to show".
