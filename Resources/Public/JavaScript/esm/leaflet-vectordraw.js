import DocumentService from "@typo3/core/document-service.js";
import Icons from "@typo3/backend/icons.js";
import FormEngine from "@typo3/backend/form-engine.js";
import  * as leafletModule from '@bobosch/ods-osm/esm/leaflet-src.esm.js';

// clone das Modul in ein echtes JS-Objekt
const L = { ...leafletModule };
window.L = L;

// jetzt darf leaflet.draw.js L verändern
await import('@bobosch/ods-osm/Leaflet/leaflet-draw/leaflet.draw.js');

// ✅ Ab hier kannst du sicher `L.Control.Draw` verwenden
console.log("Draw control:", L.Control.Draw); // 👉 sollte eine Funktion sein

class LeafletVectordrawModule {
  constructor() {
    this.element = null;
    this.min_lat = null;
    this.max_lat = null;
    this.min_lon = null;
    this.max_lon = null;
    this.latitude = null;
    this.longitude = null;
    this.fieldData = null;
    this.fieldLat = null;
    this.fieldLon = null;
    this.tilesUrl = null;
    this.tilesCopy = null;
    this.zoomLevel = 7;
    this.map = null;
    this.iconClose = null;
    this.drawnItems = null;
    this.drawControl = null;
    Icons.getIcon("actions-close", Icons.sizes.small, null, null).then(
      (markup) => {
        this.iconClose = markup;
      },
    );

    DocumentService.ready().then(() => {
      const locationMapWizard = document.querySelector(".vectordrawWizard");
      this.reinitialize = FormEngine.reinitialize;
      FormEngine.reinitialize = () => {
        this.reinitialize();
        if (locationMapWizard) {
          this.initialize(locationMapWizard);
        }
      };
      if (locationMapWizard) {
        this.initialize(locationMapWizard);
      }
    });

  };

  initialize(element) {
    element.addEventListener("click", () => {
      if (this.map !== null) {
        this.map.remove();
        this.map = null;
      }
      this.element = element;
      this.labelTitle = element.dataset.labelTitle;
      this.labelClose = element.dataset.labelClose;
      this.labelImport = element.dataset.labelImport;
      this.min_lon = element.dataset.minlon;
      this.max_lon = element.dataset.maxlon;
      this.min_lat = element.dataset.minlat;
      this.max_lat = element.dataset.maxlat;
      this.latitude = element.dataset.lat;
      this.longitude = element.dataset.lon;
      this.tilesUrl = element.dataset.tiles;
      this.tilesCopy = element.dataset.copy;
      this.geoCodeUrl = element.dataset.geocodeurl;
      this.geoCodeUrlShort = element.dataset.geocodeurlshort;
      this.fieldLat = element.dataset.namelat;
      this.fieldLon = element.dataset.namelon;
      this.fieldDataName = element.dataset.fieldname;
      this.fieldDataValue = element.dataset.fieldvalue;

      console.log(element.dataset);

      // add the container to display the map as a nice overlay
      if (!document.getElementById("t3js-location-map-wrap")) {
        this.addMapMarkup();
      }

      this.createMap();
      document.getElementById("t3js-location-map-wrap").classList.add("active");
    });
  }

  addMapMarkup() {
        const locationMapDiv = document.createElement("div");
        locationMapDiv.innerHTML = `<div id="t3js-location-map-wrap">
            <div class="t3js-location-map-title">
            <div class="btn-group">
              <a href="#" class="btn btn-icon btn-default" title="${this.labelClose}" id="t3js-ttaddress-close-map">${this.iconClose}</a>
              <a class="btn btn-default" href="#" title="${this.labelImport}" id="t3js-ttaddress-import-position">${this.labelImport}</a></div>${this.labelTitle}
            </div>
            <div class="t3js-location-map-container" id="t3js-location-map-container"></div>
          </div>`;
        document.body.appendChild(locationMapDiv);
    };

  createMap() {
    this.map = L.map("t3js-location-map-container", {
      center: [this.latitude, this.longitude],
      zoom: this.zoomLevel
    });

    if (this.min_lat && this.min_lon && this.max_lat && this.max_lon) {
      this.map.fitBounds([
        [this.min_lat, this.min_lon],
        [this.max_lat, this.max_lon]
      ]);
    }

    L.tileLayer(this.tilesUrl, {
      attribution: this.tilesCopy
    }).addTo(this.map);

    this.drawnItems = new L.FeatureGroup().addTo(this.map);

    L.control.layers({}, { "GeoJSON Data": this.drawnItems }, {
      position: "topright",
      collapsed: false
    }).addTo(this.map);

    this.map.addControl(new L.Control.Draw({
      edit: {
        featureGroup: this.drawnItems,
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

    if (this.fieldDataValue) {
      try {
        const geoJson = JSON.parse(this.fieldDataValue);
        const geoJsonGroup = L.geoJSON(geoJson);
        const addNonGroupLayers = (sourceLayer, targetGroup) => {
          if (sourceLayer instanceof L.LayerGroup) {
            sourceLayer.eachLayer(layer => addNonGroupLayers(layer, targetGroup));
          } else {
            targetGroup.addLayer(sourceLayer);
          }
        };
        addNonGroupLayers(geoJsonGroup, this.drawnItems);
      } catch (e) {
        console.error("Invalid GeoJSON:", e);
      }
    }

    this.map.on(L.Draw.Event.CREATED, (event) => {
      this.drawnItems.addLayer(event.layer);
    });

    // Add listeners for import and close
    document.getElementById("t3js-ttaddress-import-position").addEventListener("click", () => {
      const data = this.drawnItems.toGeoJSON();
      const jsonData = JSON.stringify(data);

      const targetTextarea = document.querySelector(`textarea[data-formengine-input-name="${this.fieldDataName}"]`);

      if (targetTextarea) {
        targetTextarea.value = jsonData;
      }

      FormEngine.Validation.markFieldAsChanged(document.querySelector(`input[name="${this.fieldDataName}"]`));
      FormEngine.Validation.validate();
      document.getElementById("t3js-location-map-wrap").classList.remove("active");
    });

    document.getElementById("t3js-ttaddress-close-map").addEventListener("click", () => {
      document.getElementById("t3js-location-map-wrap").classList.remove("active");
    });
  }

}

export default new LeafletVectordrawModule();

