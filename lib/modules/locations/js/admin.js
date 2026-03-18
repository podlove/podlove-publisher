(function ($) {
  "use strict";

  var defaultLat = 51.505;
  var defaultLng = -0.09;
  var defaultZoom = 2;
  var locatedZoom = 15;

  var tabs = {};

  function osmTypePrefix(osmType) {
    var map = { node: "N", way: "W", relation: "R" };
    return map[osmType] || "";
  }

  function initMapForRel(rel) {
    if (tabs[rel] && tabs[rel].initialized) {
      tabs[rel].map.invalidateSize();
      return;
    }

    var containerId = "podlove-location-map-" + rel;
    var container = document.getElementById(containerId);
    if (!container) {
      return;
    }

    var latField = document.getElementById("podlove-location-lat-" + rel);
    var lngField = document.getElementById("podlove-location-lng-" + rel);
    var existingLat = latField ? parseFloat(latField.value) : NaN;
    var existingLng = lngField ? parseFloat(lngField.value) : NaN;
    var hasExisting = !isNaN(existingLat) && !isNaN(existingLng);

    var startLat = hasExisting ? existingLat : defaultLat;
    var startLng = hasExisting ? existingLng : defaultLng;
    var startZoom = hasExisting ? locatedZoom : defaultZoom;

    var map = L.map(containerId).setView([startLat, startLng], startZoom);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution:
        '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 19,
    }).addTo(map);

    var state = {
      map: map,
      marker: null,
      initialized: true,
    };

    tabs[rel] = state;

    if (hasExisting) {
      placeMarker(rel, startLat, startLng);
    }

    map.on("click", function (e) {
      placeMarker(rel, e.latlng.lat, e.latlng.lng);
      updateCoordinateFields(rel, e.latlng.lat, e.latlng.lng);
      reverseGeocode(rel, e.latlng.lat, e.latlng.lng);
    });

    setTimeout(function () {
      map.invalidateSize();
    }, 250);
  }

  function placeMarker(rel, lat, lng) {
    var state = tabs[rel];
    if (!state || !state.map) {
      return;
    }

    if (state.marker) {
      state.marker.setLatLng([lat, lng]);
    } else {
      state.marker = L.marker([lat, lng], { draggable: true }).addTo(
        state.map
      );

      state.marker.on("dragend", function (e) {
        var pos = e.target.getLatLng();
        updateCoordinateFields(rel, pos.lat, pos.lng);
        reverseGeocode(rel, pos.lat, pos.lng);
      });
    }
  }

  function updateCoordinateFields(rel, lat, lng) {
    var latField = document.getElementById("podlove-location-lat-" + rel);
    var lngField = document.getElementById("podlove-location-lng-" + rel);
    if (latField) latField.value = lat.toFixed(8);
    if (lngField) lngField.value = lng.toFixed(8);
  }

  function searchLocation(rel, query) {
    if (!query || query.trim().length < 2) {
      return;
    }

    var resultsContainer = document.getElementById(
      "podlove-location-search-results-" + rel
    );
    resultsContainer.innerHTML =
      '<div class="podlove-location-searching">Searching...</div>';

    var url =
      "https://nominatim.openstreetmap.org/search?format=json&limit=5&addressdetails=1&q=" +
      encodeURIComponent(query);

    $.ajax({
      url: url,
      dataType: "json",
      headers: {
        Accept: "application/json",
      },
      success: function (data) {
        displaySearchResults(rel, data);
      },
      error: function () {
        resultsContainer.innerHTML =
          '<div class="podlove-location-error">Search failed. Please try again.</div>';
      },
    });
  }

  function displaySearchResults(rel, results) {
    var container = document.getElementById(
      "podlove-location-search-results-" + rel
    );

    if (!results || results.length === 0) {
      container.innerHTML =
        '<div class="podlove-location-no-results">No results found.</div>';
      return;
    }

    var html = '<ul class="podlove-location-results-list">';
    for (var i = 0; i < results.length; i++) {
      var r = results[i];
      var osmId = "";
      if (r.osm_type && r.osm_id) {
        osmId = osmTypePrefix(r.osm_type) + r.osm_id;
      }
      var countryCode = "";
      if (r.address && r.address.country_code) {
        countryCode = r.address.country_code.toUpperCase();
      }

      html +=
        '<li class="podlove-location-result-item" ' +
        'data-lat="' +
        r.lat +
        '" ' +
        'data-lng="' +
        r.lon +
        '" ' +
        'data-name="' +
        escapeHtml(r.display_name) +
        '" ' +
        'data-osm="' +
        escapeHtml(osmId) +
        '" ' +
        'data-country="' +
        escapeHtml(countryCode) +
        '">' +
        escapeHtml(r.display_name) +
        "</li>";
    }
    html += "</ul>";

    container.innerHTML = html;

    $(container)
      .find(".podlove-location-result-item")
      .on("click", function () {
        var lat = parseFloat($(this).data("lat"));
        var lng = parseFloat($(this).data("lng"));
        var name = $(this).data("name");
        var osm = $(this).data("osm");
        var country = $(this).data("country");

        placeMarker(rel, lat, lng);
        updateCoordinateFields(rel, lat, lng);

        if (tabs[rel] && tabs[rel].map) {
          tabs[rel].map.setView([lat, lng], locatedZoom);
        }

        var addressField = document.getElementById(
          "podlove-location-address-" + rel
        );
        if (addressField) addressField.value = name;

        var nameField = document.getElementById(
          "podlove-location-name-" + rel
        );
        if (nameField) {
          nameField.value = name.split(",")[0].trim();
        }

        var osmField = document.getElementById("podlove-location-osm-" + rel);
        if (osmField && osm) {
          osmField.value = osm;
        }

        var countryField = document.getElementById(
          "podlove-location-country-" + rel
        );
        if (countryField && country) {
          countryField.value = country;
        }

        container.innerHTML = "";
      });
  }

  function reverseGeocode(rel, lat, lng) {
    var url =
      "https://nominatim.openstreetmap.org/reverse?format=json&addressdetails=1&lat=" +
      lat +
      "&lon=" +
      lng;

    $.ajax({
      url: url,
      dataType: "json",
      success: function (data) {
        if (data && data.display_name) {
          var addressField = document.getElementById(
            "podlove-location-address-" + rel
          );
          if (addressField) addressField.value = data.display_name;

          var nameField = document.getElementById(
            "podlove-location-name-" + rel
          );
          if (nameField) {
            nameField.value = data.display_name.split(",")[0].trim();
          }

          var osmField = document.getElementById("podlove-location-osm-" + rel);
          if (osmField) {
            osmField.value = "";
          }

          if (data.address && data.address.country_code) {
            var countryField = document.getElementById(
              "podlove-location-country-" + rel
            );
            if (countryField) {
              countryField.value = data.address.country_code.toUpperCase();
            }
          }
        }
      },
    });
  }

  function escapeHtml(text) {
    if (!text) return "";
    var div = document.createElement("div");
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
  }

  function clearLocation(rel) {
    var fields = ["name", "lat", "lng", "address", "country", "osm"];
    for (var i = 0; i < fields.length; i++) {
      var field = document.getElementById(
        "podlove-location-" + fields[i] + "-" + rel
      );
      if (field) field.value = "";
    }

    var state = tabs[rel];
    if (state && state.marker) {
      state.map.removeLayer(state.marker);
      state.marker = null;
    }

    if (state && state.map) {
      state.map.setView([defaultLat, defaultLng], defaultZoom);
    }

    var searchInput = document.getElementById(
      "podlove-location-search-" + rel
    );
    if (searchInput) searchInput.value = "";

    var resultsContainer = document.getElementById(
      "podlove-location-search-results-" + rel
    );
    if (resultsContainer) resultsContainer.innerHTML = "";
  }

  function switchTab(rel) {
    $(".podlove-location-tab").removeClass("active");
    $(".podlove-location-tab[data-tab='" + rel + "']").addClass("active");

    $(".podlove-location-tab-panel").removeClass("active");
    $(".podlove-location-tab-panel[data-tab='" + rel + "']").addClass("active");

    initMapForRel(rel);
  }

  function bindEvents() {
    $(document).on("click", ".podlove-location-tab", function (e) {
      e.preventDefault();
      var rel = $(this).data("tab");
      switchTab(rel);
    });

    $(document).on("click", ".podlove-location-search-btn", function (e) {
      e.preventDefault();
      var rel = $(this).data("rel");
      var query = $("#podlove-location-search-" + rel).val();
      searchLocation(rel, query);
    });

    $(document).on(
      "keypress",
      ".podlove-location-search-input",
      function (e) {
        if (e.which === 13) {
          e.preventDefault();
          var rel = $(this).data("rel");
          var query = $(this).val();
          searchLocation(rel, query);
        }
      }
    );

    $(document).on("click", ".podlove-location-clear-btn", function (e) {
      e.preventDefault();
      var rel = $(this).data("rel");
      clearLocation(rel);
    });

    $(document).on("postbox-toggled", function (e, postbox) {
      if (postbox.id === "podlove_podcast_locations") {
        var activeRel = $(".podlove-location-tab.active").data("tab");
        if (activeRel && tabs[activeRel] && tabs[activeRel].map) {
          setTimeout(function () {
            tabs[activeRel].map.invalidateSize();
          }, 100);
        }
      }
    });
  }

  $(document).ready(function () {
    if (document.getElementById("podlove-episode-location-wrapper")) {
      bindEvents();
      initMapForRel("subject");
    }

    if (document.getElementById("podlove-podcast-location-wrapper")) {
      bindEvents();
      initMapForRel("podcast");
    }
  });
})(jQuery);
