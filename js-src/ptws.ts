import * as L from 'leaflet';
import { Chart } from "chart.js";
import "./jquery.sonar.min.js";
import { Util } from "./utilities";
import { Slideshow } from "./slideshow";


// Seek out and init GPS log data chunks on the page, marking them as inited as we go.
export function findAndInitGPSLogDisplays() {

	interface SmoothedPoint {
		't': any;
		't_d': any;
		'lat': number;
		'lon': number;
		'el': number;
		'spd': number;
	};


	const mapbox_token = 'HURRHURR';

	jQuery('div.ptws-ride-log').each(function (index, item) {
		var jqRideLogDiv = jQuery(item);
		var rideLogId = jqRideLogDiv.attr('rideid');
		if (jqRideLogDiv.attr('ptwsinitialized')) { return; }
		jqRideLogDiv.attr('ptwsinitialized', 1);

		var rawDataStr = jQuery(jqRideLogDiv).children().first().text()
		rawDataStr = rawDataStr.replace(/\r?\n|\r/g, " ");
		var rawdata;
		try {
			rawdata = JSON.parse(rawDataStr);
		} catch (e) {
			console.log("PTWS: Failed to parse ride log JSON for ride " + rideLogId + ":");
			console.log(e);
			console.log(item);
			return;
		}
		// Check and see if we got JSON with every needed attribute,
		// making a full set of data for graphing.
		var legs = [rawdata];
		if (rawdata.hasOwnProperty('legs')) {
			legs = rawdata['legs'];
		}
		const classesToCheck = ['lat', 'lon', 'el', 't', 'spd'];
		const hasAll = legs.every(leg => classesToCheck.every(cls => leg.hasOwnProperty(cls)));
		if (!hasAll) {
 			// Can't use incomplete data sets
 			console.log("PTWS: Cannot init ride log with incomplete data sets:");
 			console.log(item);
			return;
		}

		// Convert the arrays into a series of point objects, like a minimal version of GPX.

		var legsAsPoints = legs.map(leg => {
			var points = [];
			var i = 0;
			while (i < leg['t'].length) {
				var point = {
					't': leg['t'][i],
					// Date parsed as a real JS Date object, for use in further processing.
					// If we start working with questionable data, this may throw errors.
					// The strings we are parsing will look like "2011-10-21T05:44:53+00:00",
					// which is known as SOAP format.
					't_d': new Date(leg['t'][i]),
					'lat': leg['lat'][i],
					'lon': leg['lon'][i],
					'el': leg['el'][i],
					'spd': leg['spd'][i]
				};
				points.push(point);
				i++;
			}
			return points;
		});

		// Reduce the set to a maximum of 1024 for the elevation/speed graphs

		var sparsifiedLegs = legsAsPoints.map(leg => {
			if (leg.length < 1025) {
				return leg;
			}
			const step = 1024.0 / leg.length;
			var sparsifiedPoints = [];
			sparsifiedPoints.push(leg[0]);
			var unsparseIndex = 1;
			while (unsparseIndex < leg.length) {
				sparsifiedPoints.push(leg[Math.floor(unsparseIndex)]);
				unsparseIndex += step;
			}
			return sparsifiedPoints;
		});

		// Build and embed the map

		// Container and div for the embedded Google map with the route.
		var mapFrame = jQuery("<div/>").attr("class", "ptws-routemap").appendTo(jqRideLogDiv);
		var mapContainer = jQuery("<div/>").appendTo(mapFrame);

		var map = L.map(mapContainer.get(0));

		(<any>L).tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token=' + mapbox_token, {
			maxZoom: 18,
			attribution: '&copy; <a href="https://www.mapbox.com/about/maps/">Mapbox</a> ' +
						 '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> ' +
						 '<strong><a href="https://www.mapbox.com/map-feedback/" target="_blank">Improve this map</a></strong>',
			tileSize: 512,
			zoomOffset: -1,
			id: 'mapbox/outdoors-v11'
		}).addTo(map);

		var firstPoint = null;
		var lastPoint = null;

		var latMin = null;
		var latMax = null;
		var lonMin = null;
		var lonMax = null;

		var routeInnerStyle = {
			weight: 4,
			opacity: 1,
			stroke: true,
			fill: false,
			color: '#d0ff57'
		};
		var routeOuterStyle = {
			weight: 7,
			opacity: 1,
			stroke: true,
			fill: false,
			color: '#78a120'
		};
		var legLinkStyle = {
			weight: 2,
			opacity: 1,
			stroke: true,
			fill: false,
			dashArray: '4',
			color: '#78a120'
		};

		legsAsPoints.forEach(leg => {

			var routeLeafletPoints = leg.map(function (pt) { return <L.LatLngExpression>[pt['lat'], pt['lon']]; });

			if (routeLeafletPoints.length > 0) {
				if (lastPoint != null) {
					L.polyline([lastPoint, routeLeafletPoints[0]], legLinkStyle).addTo(map);
				}
				if (firstPoint == null) {
					firstPoint = routeLeafletPoints[0];
				}
				lastPoint = routeLeafletPoints[routeLeafletPoints.length - 1];
			}

			L.polyline(routeLeafletPoints, routeOuterStyle).addTo(map);
			L.polyline(routeLeafletPoints, routeInnerStyle).addTo(map);

			// Calculate the center point and the outer bounds for the route.
			leg.forEach(function (pt) {
				if (latMin === null || pt['lat'] < latMin) { latMin = pt['lat']; }
				if (latMax === null || pt['lat'] > latMax) { latMax = pt['lat']; }
				if (lonMin === null || pt['lon'] < lonMin) { lonMin = pt['lon']; }
				if (lonMax === null || pt['lon'] > lonMax) { lonMax = pt['lon']; }
			});
		});

		var startFlag = L.icon({
			iconSize: [15, 18],
			iconAnchor: [2, 17],
			iconUrl: 'https://mile42.net/wp-content/plugins/ptws/images/start_flag.png'
		});

		var finishFlag = L.icon({
			iconSize: [15, 18],
			iconAnchor: [2, 17],
			iconUrl: 'https://mile42.net/wp-content/plugins/ptws/images/checkered_flag.png'
		});

		L.marker(firstPoint, { icon: startFlag }).addTo(map);
		L.marker(lastPoint, { icon: finishFlag }).addTo(map);

		map.fitBounds([[latMin, lonMin], [latMax, lonMax]]);

		// Build and embed the chart

		// Container and canvas for the Chart.js speed/elevation graph.
		var chartFrame = jQuery("<div/>").attr("class", "ptws-elevation-chart").appendTo(jqRideLogDiv);
		var chartContainer = jQuery("<canvas/>").attr("width", "640").attr("height", "140").appendTo(chartFrame);

		// Format the elevation and speed data for Chart.js .
		var elevData = sparsifiedLegs.flatMap(leg => leg.map(pt => { return { x: pt['t'], y: pt['el'] }; }));
		var spdData = sparsifiedLegs.flatMap(leg => leg.map(pt => { return { x: pt['t'], y: pt['spd'] }; }));

		var chartContainerEl = chartContainer.get(0);
		var ctx = (<any>chartContainerEl).getContext('2d');
		// A vertical gradient with two color stops, to fill in the
		// background underneath the elevation line.
		var bgGradient = ctx.createLinearGradient(0, 0, 0, 140);
		bgGradient.addColorStop(0, 'rgba(181, 255, 16, 0.5)');
		bgGradient.addColorStop(1, 'rgba(181, 255, 16, 0.0)');

		var lineChartData = {
			datasets: [{
				label: 'Elevation',
				// 'units' is not part of the Chart.js spec.
				// Kept here to be carried along and used by the tooltip UI.
				units: 'm',
				borderColor: 'rgb(130,171,42)',
				pointBackgroundColor: 'rgb(130,171,42)',
				backgroundColor: bgGradient,
				borderWidth: 2,
				pointRadius: 0, // No points
				// Virtual point size for mouseovers, used even when points are not rendered.
				pointHitRadius: 3,
				fill: true,
				data: elevData,
				yAxisID: 'y-axis-1',
			}, {
				label: 'Speed',
				units: 'm/s',
				borderColor: 'rgb(205, 197, 163)',
				pointBackgroundColor: 'rgb(205, 197, 163)',
				backgroundColor: 'rgb(205, 197, 163)',
				borderWidth: 2,
				pointRadius: 0,
				pointHitRadius: 3,
				fill: false,
				data: spdData,
				yAxisID: 'y-axis-2'
			}]
		};

		var newChart = new Chart(ctx, {
			type: 'line',
			data: lineChartData,
			options: {
				responsive: true,
				// Display all values that fall on the hovered vertical index
				hover: {
					mode: 'index'
				},
				// No title, no legend.
				title: { display: false },
				legend: { display: false },
				tooltips: {
					backgroundColor: 'rgba(255,255,255,255.8)',
					titleFontColor: '#000',
					bodyFontColor: '#000',
					mode: 'index',
					intersect: false,
					// Custom callback to alter the amounts shown in the tooltip,
					// specifically to tack on the units indicator.
					callbacks: {
						label: function (tooltipItems, data) {
							// Truncate to two decimal places
							var re = new RegExp('^-?\\d+(?:\.\\d{0,2})?');
							var val = tooltipItems.yLabel;
							var m = val.toString().match(re);
							if (m) { val = m[0]; }
							return data.datasets[tooltipItems.datasetIndex].label + ': ' + val + ' ' + (<any>data.datasets[tooltipItems.datasetIndex]).units;
						}
					}
				},
				scales: {
					xAxes: [{
						type: 'time',
						time: {
							//distribution: 'linear',
							tooltipFormat: 'h:mm a'
						},
						scaleLabel: { display: false },
						// Grid lines would just clutter a graph this small.
						gridLines: { display: false }
					}],
					yAxes: [
						{
							type: 'linear',
							display: true,
							position: 'left',
							id: 'y-axis-1',
							gridLines: { display: false },
						},
						{
							type: 'linear',
							display: true,
							position: 'right',
							id: 'y-axis-2',
							gridLines: { display: false },
						}
					],
				}
			}
		});
	});
}


export function handleLazyLoadImage(img) {
	var jqImg = jQuery(img);
	var src = jqImg.attr('data-lazy-src');

	if (!src || 'undefined' === typeof (src)) {
		console.log("PTWS: Asked to lazyload image with no data-lazy-src:");
		console.log(img);
		return;
	}

	jqImg.off('scrollin') // remove event binding
		.hide()
		.removeAttr('data-lazy-src')
		.attr('data-lazy-loaded', 'true');

	img.src = src;
	jqImg.fadeIn();
}


export function lazyLoadImage(img) {
	handleLazyLoadImage(img);
	var jqImg = jQuery(img);

	// If this picture is inside a RoyalSlider div, initialize the RoyalSlider,
	// and immediately load all the related images without triggering a recursive RoyalSlider check.
	var insideRoyalSlider = jqImg.closest('div.royalSlider');
	insideRoyalSlider.each(function (i, oneRSContainer) {
		// Once a RoyalSlider is inited, users can scroll left and right through the slides,
		// bringing them into view even though they are not technically made visible by a scroll event.
		// This breaks lazy loading.
		var otherImages = jQuery(oneRSContainer).find('img[data-lazy-src]:not([data-lazy-loaded])');
		// Also RoyalSlider creates a stack of slides that does not include all the slides in the oroginal
		// set, and modifies the stack as it scrolls.  So we need to find all the other images
		// before calling the RoyalSlider init.
		otherImages.each(function (i, additonalImage) {
			handleLazyLoadImage(additonalImage);
		});
		Slideshow.initRoyalslider(oneRSContainer);
	});
}


export function lazyLoadInit() {
	jQuery('img[data-lazy-src]').on('scrollin', { distance: 200 }, function () {
		lazyLoadImage(this);
	});

	// We need to force load gallery images in Jetpack Carousel and give up lazy-loading otherwise images don't show up correctly
	jQuery('[data-carousel-extra]').each(function () {
		jQuery(this).find('img[data-lazy-src]').each(function () {
			lazyLoadImage(this);
		});
	});
}


// For toggling the 'question and answer' disclosures
export function QAToggle(node) {
	var n = node;
	var jqn = jQuery(node);
	do {n = n.nextSibling;} while (n && n.nodeType != 1);

	if (jqn.hasClass('disclosed')) {
		jqn.removeClass('disclosed');
		jQuery(n).slideUp(
			null, function() {
				n.style.display = 'none';
				Util.fakeScroll();
			}
		);
	} else {
		jqn.addClass('disclosed');
		jQuery(n).slideDown(null, function() { Util.fakeScroll() });
	}
}


export function QAList(e) {
	var b = jQuery(e.target);
	if ( b.is( "span.qaq" ) ) {
		b.closest('li.qaq').toggleClass("disclosed");
	}
}


// Sectional filter buttons used on the 'Gear' page and elsewhere
export function ptwsFilterButton(e) {
	// Walk up to the container for only this set of buttons.
	var filterContainer = jQuery(e.target).closest('div.ptwsFilterButtons');
	var n = filterContainer.attr('ptwsFilterName');
	// Remove every 'active' class from the buttons within.
	filterContainer.children().removeClass('active');
	// Add an 'active' class to the specific button pressed.
	jQuery(e.target).closest('span').addClass('active');

	var activeFilters = jQuery("div.ptwsFilterButtons[ptwsFilterName='" + n + "']").children().filter(".active").filter("[filter]");
	var filters = activeFilters.map(function () { return jQuery(this).attr('filter'); }).get();

	// After the slide we trigger a fake scroll event,
	// so the lazy-load images actually appear
	if (filters.length > 0) {
		var selectors = filters.map(function (a) { return "[" + n + "~='" + a + "']" }).join("");
		jQuery("." + n + selectors).slideDown(
			null, function() { Util.fakeScroll() });
		jQuery("." + n + ":not(" + selectors + ")").slideUp();
	} else {
		jQuery("." + n).slideDown(
			null, function() { Util.fakeScroll() });
	}
}


// Seek out and init GPS log data chunks on the page, marking them as inited as we go.
export function runPostBodyLoad() {
	lazyLoadInit();
	Slideshow.findAndInitRoyalsliders();
	findAndInitGPSLogDisplays();
}


// http://learn.jquery.com/using-jquery-core/document-ready/
jQuery(function() {

	Slideshow.addGlobalCaptionOverride();

	//BlockItinerary.register();
	lazyLoadInit();
	findAndInitGPSLogDisplays();
	Slideshow.findAndInitRoyalsliders();

	// https://connekthq.com/plugins/ajax-load-more/docs/callback-functions/
	(<any>window).almComplete = function(alm) {
		Slideshow.findAndInitRoyalsliders();
		findAndInitGPSLogDisplays();
	};

	// Work with WP.com infinite scroll
	jQuery('body').on('post-load', runPostBodyLoad);
});