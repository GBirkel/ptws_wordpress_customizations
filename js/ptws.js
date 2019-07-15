var ptws = {

	fakeScroll: function() {
		var x = window.scrollX;
		var y = window.scrollY;
		window.scrollTo(x, y+1);
		window.scrollTo(x, y);
	},

	// Seek out and init RoyalSlider instances on the page, marking them as inited as we go.
	// Note: Currently not used.
	findAndInitRoyalsliders: function () {
		jQuery('div.royalSlider').each(function (index, item) {
			ptws.initRoyalslider(item);
		});
	},


	// Init the RoyalSlider instance, marking it inited afterwards.
	// (Should be called only when we've finished overloading the global caption module.)
	initRoyalslider: function (item) {
		var sDiv = jQuery(item);
		if (sDiv.attr('ptwsinitialized')) { return; }
		sDiv.attr('ptwsinitialized', 1);
		if (sDiv.royalSlider === undefined) {
			console.log("PTWS: Royalslider plugin is not present/activated.");
			return;
		}
		sDiv.royalSlider({
			addActiveClass: true,
			arrowsNav: true,
			arrowsNavAutoHide: false,
			autoPlay: false,
			autoScaleSlider: false,
			controlNavigation: 'bullets',
			controlsInside: false,
			globalCaption: true,
			imageScaleMode: 'fit',
			imageScalePadding: 0,
			imageAlignCenter: true,
			slidesSpacing: 0,
			keyboardNavEnabled: false,
			loop: false,
			minSlideOffset: 0,
			navigateByClick: false,
			startSlideId: 0,
			sliderDrag: true,
			thumbsFitInViewport: false,
			transitionType: 'move',
			visibleNearby: {
				enabled: true,
				centerArea: 0.6,
				center: true,
				breakpoint: 1124,
				breakpointCenterArea: 0.7
			},
			deeplinking: {
				enabled: false,
				change: false
			}
			/* size of all images http://help.dimsemenov.com/kb/royalslider-jquery-plugin-faq/adding-width-and-height-properties-to-images */
			/* imgWidth: 1400, */
			/* imgHeight: 680 */
		});
	},


	// Seek out and init GPS log data chunks on the page, marking them as inited as we go.
	findAndInitGPSLogDisplays: function () {
		jQuery('div.ptws-ride-log').each(function (index, item) {
			var jqRideLogDiv = jQuery(item);
			var rideLogId = jqRideLogDiv.attr('rideid');
			if (jqRideLogDiv.attr('ptwsinitialized')) { return; }
			jqRideLogDiv.attr('ptwsinitialized', 1);

			var rawDataStr = jQuery(jqRideLogDiv).children().first().text()
			rawDataStr = rawDataStr.replace(/\r?\n|\r/g, " ");
			var rawdata = JSON.parse(rawDataStr);
			// Check and see if we got JSON with every needed attribute,
			// making a full set of data for graphing.
			var classesToCheck = ['lat', 'lon', 'el', 't', 'spd'];
			var hasAll = classesToCheck.filter(function (a) { return rawdata[a]; });
			if (hasAll.length != classesToCheck.length) { return; } // Can't use incomplete data sets

			// Convert the arrays into a series of point objects, like a minimal version of GPX.

			var points = [];
			var i = 0;
			while (i < rawdata['t'].length) {
				var point = {
					't': rawdata['t'][i],
					// Date parsed as a real JS Date object, for use in further processing.
					// If we start working with questionable data, this may throw errors.
					// The strings we are parsing will look like "2011-10-21T05:44:53+00:00",
					// which is known as SOAP format.
					't_d': new Date(rawdata['t'][i]),
					'lat': rawdata['lat'][i],
					'lon': rawdata['lon'][i],
					'el': rawdata['el'][i],
					'spd': rawdata['spd'][i]
				};
				points.push(point);
				i++;
			}

			// Smooth points using their predecessors within a 7 second range.
			// (Helps to prevent GPS hairballs from poor signal.)

			var smoothedPoints = [];
			// A pool of all previously seen points that are within 6.01 seconds
			// of the current point (including the current point).
			var pointPool = [];
			var nextPointIndex = 0;
			// We will be handling all these attributes the same way
			var typesToSmooth = ['lat', 'lon', 'el', 'spd'];
			while (nextPointIndex < points.length) {
				var currentPoint = points[nextPointIndex];
				var thisT = currentPoint['t_d'];
				pointPool.push(currentPoint);
				// Drop any point older than 6.01 seconds.
				// This way, large gaps in the recorded data halt the smoothing effect.
				pointPool = pointPool.filter(function (pt) { return (thisT - pt['t_d']) < 6010 });
				// Start with a template point that has all the
				// attributes we wish to smooth zeroed out.
				var smoothedPoint = {
					't': currentPoint['t'],
					't_d': thisT,
					'lat': 0.0,
					'lon': 0.0,
					'el': 0.0,
					'spd': 0.0,
				};
				var totalMultiplier = 0;
				// Add each point's attributes to the template point, multiplying them
				// first by a 'force multiplier' based on the distance in time from the current point.
				// The more distant the time (up to 6.01 seconds) the lower the force multiplier.
				pointPool.forEach(function (pt) {
					var thisMultiplier = 7000 - (thisT - pt['t_d']);
					totalMultiplier += thisMultiplier
					typesToSmooth.forEach(function (tts) {
						smoothedPoint[tts] += pt[tts] * thisMultiplier;
					});
				});
				// Divide the template attributes by the total force multiplier applied,
				// to get values that make sense.  Basically, the new current point is like the
				// old current point except it has ~6 seconds of "drag" applied to it.
				typesToSmooth.forEach(function (tts) {
					smoothedPoint[tts] = smoothedPoint[tts] / totalMultiplier;
				});
				smoothedPoints.push(smoothedPoint);
				nextPointIndex++;
			}

			// Reduce the set to a maximum of 640 for the elevation/speed graphs

			var sparsifiedPoints = [];
			if (smoothedPoints.length < 645) {
				sparsifiedPoints = smoothedPoints;
			} else {
				sparsifiedPoints.push(smoothedPoints[0]);
				var nextWholeNumber = 1;
				var step = 640.0 / smoothedPoints.length;
				var unsparseIndex = 1;
				while (unsparseIndex < smoothedPoints.length) {
					if ((unsparseIndex * step) > sparsifiedPoints.length) {
						sparsifiedPoints.push(smoothedPoints[unsparseIndex]);
					}
					unsparseIndex++;
				}
			}

			// Build and embed the map

			// Container and div for the embedded Google map with the route.
			var mapFrame = jQuery("<div/>").attr("class", "ptws-routemap").appendTo(jqRideLogDiv);
			var mapContainer = jQuery("<div/>").appendTo(mapFrame);

			var map = L.map(mapContainer.get(0));

			L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token=pk.eyJ1IjoibWlsZTQyIiwiYSI6ImNqbGgyY2l0NDFkcm8zcWxxMWJrd2RvaXEifQ.uMQoOnrPBsLbLV2v4COFjA', {
				maxZoom: 18,
				attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, ' +
					'<a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
					'Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
				id: 'mapbox.outdoors'
			}).addTo(map);

			var routeLeafletPoints = smoothedPoints.map(function (pt) { return [pt['lat'], pt['lon']]; });
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
			var polylineOuter = L.polyline(routeLeafletPoints, routeOuterStyle).addTo(map);
			var polylineInner = L.polyline(routeLeafletPoints, routeInnerStyle).addTo(map);

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

			L.marker(routeLeafletPoints[0], { icon: startFlag }).addTo(map);
			L.marker(routeLeafletPoints[routeLeafletPoints.length-1], { icon: finishFlag }).addTo(map);

			// Calculate the center point and the outer bounds for the route.
			var latMin = Math.min.apply(0, rawdata['lat']);
			var latMax = Math.max.apply(0, rawdata['lat']);
			var lonMin = Math.min.apply(0, rawdata['lon']);
			var lonMax = Math.max.apply(0, rawdata['lon']);
			map.fitBounds([[latMin, lonMin], [latMax, lonMax]]);

			// Build and embed the chart

			// Container and canvas for the Chart.js speed/elevation graph.
			var chartFrame = jQuery("<div/>").attr("class", "ptws-elevation-chart").appendTo(jqRideLogDiv);
			var chartContainer = jQuery("<canvas/>").attr("width", "640").attr("height", "140").appendTo(chartFrame);

			// Format the elevation and speed data for Chart.js .
			var elevData = sparsifiedPoints.map(function (pt) { return { x: pt['t'], y: pt['el'] }; });
			var spdData = sparsifiedPoints.map(function (pt) { return { x: pt['t'], y: pt['spd'] }; });

			var chartContainerEl = chartContainer.get(0);
			var ctx = chartContainerEl.getContext('2d');
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

			var newChart = Chart.Line(ctx, {
				data: lineChartData,
				options: {
					responsive: true,
					// Display all values that fall on the hovered vertical index
					hoverMode: 'index',
					stacked: false,
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
								return data.datasets[tooltipItems.datasetIndex].label + ': ' + val + ' ' + data.datasets[tooltipItems.datasetIndex].units;
							}
						}
					},
					scales: {
						xAxes: [{
							type: 'time',
							time: {
								distribution: 'linear',
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
	},


	lazyLoadImage: function(img) {
		ptws.handleLazyLoadImage(img);
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
				ptws.handleLazyLoadImage(additonalImage);
			});
			ptws.initRoyalslider(oneRSContainer);
		});
	},


	handleLazyLoadImage: function(img) {
		var jqImg = jQuery(img);
		var src = jqImg.attr('data-lazy-src');

		if (!src || 'undefined' === typeof (src)) { return; }

		jqImg.unbind('scrollin') // remove event binding
			.hide()
			.removeAttr('data-lazy-src')
			.attr('data-lazy-loaded', 'true');

		img.src = src;
		jqImg.fadeIn();
	},


	lazyLoadInit: function() {
		jQuery('img[data-lazy-src]').bind('scrollin', { distance: 200 }, function () {
			ptws.lazyLoadImage(this);
		});

		// We need to force load gallery images in Jetpack Carousel and give up lazy-loading otherwise images don't show up correctly
		jQuery('[data-carousel-extra]').each(function () {
			jQuery(this).find('img[data-lazy-src]').each(function () {
				ptws.lazyLoadImage(this);
			});
		});
	}

};


// For toggling the 'question and answer' disclosures

function QAToggle(node) {
	var n = node;
	var jqn = jQuery(node);
	do {n = n.nextSibling;} while (n && n.nodeType != 1);

	if (jqn.hasClass('disclosed')) {
		jqn.removeClass('disclosed');
		jQuery(n).slideUp(
			null, function() {
				n.style.display = 'none';
				ptws.fakeScroll();
			}
		);
	} else {
		jqn.addClass('disclosed');
		jQuery(n).slideDown(null, function() {ptws.fakeScroll()});
	}
}


function QAList(e) {
	var b = jQuery(e.target);
	if ( b.is( "span.qaq" ) ) {
		b.closest('li.qaq').toggleClass("disclosed");
	}
}


// For the buttons on the 'Gear' page

function showGear(e) {
	// Walk up to the container for only this set of buttons,
	// and remove every 'active' class from the buttons within.
	jQuery(e.target).closest('div.gearFilterButtons').children().removeClass('active');
	// Add an 'active' class to the specific button pressed.
	jQuery(e.target).closest('span').addClass('active');

	var activeFilters = jQuery("div.gearFilterButtons").children().filter(".active").filter("[filter]");
	var filters = activeFilters.map(function () { return jQuery(this).attr('filter'); }).get();

	// After the slide we trigger a fake scroll event,
	// so the lazy-load images actually appear
	if (filters.length > 0) {
		var selectors = filters.map(function (a) { return "[trips~='" + a + "']" }).join("");
		jQuery("div.gear" + selectors).slideDown(
			null, function() {ptws.fakeScroll()});
		jQuery("div.gear:not(" + selectors + ")").slideUp();
	} else {
		jQuery("div.gear").slideDown(
			null, function() {ptws.fakeScroll()});
	}
}


// http://learn.jquery.com/using-jquery-core/document-ready/
jQuery(document).ready(function($) {

	/**
	 *
	 * Override the "global caption module" extension to RoyalSlider
	 * with our own version, which uses "insertAfter" rather than "appendTo",
	 * effectively moving the global caption div outside the relatively-positioned slideshow,
	 * so our captions can have a height independent of the slides.
	 * Risky but effective.
	 */
	$.extend($.rsProto, {
		_initGlobalCaption: function () {
			var self = this;
			if (self.st.globalCaption) {
				var setCurrCaptionHTML = function () {

					var gcc = self.ptwsGlobalCaptionContainer.get(0);
					// Destroy any lingering/unfinished transitions
					gcc.removeAttribute("style");
					// Destroy any lingering/unfinished event listeners
					self.ptwsGlobalCaptionContainer.unbind("transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd");
					// Get the height of the caption box as-is
					var start_h = gcc.offsetHeight;
					// Fill the caption box with the new content
					self.globalCaption.html(self.currSlide.caption || '');
					// Force a calculation of the new height based on the new content
					var end_h = gcc.offsetHeight;
					// Set the caption box to the _old_ height explicitly 
					gcc.style.height = start_h + 'px';
					// Make sure the styling is recalculated as a static height (which we just set), rather than auto (which it was)
					gcc.offsetHeight;
					// Start a transition to the _new_ height explicitly
					gcc.style.transition = "all 0.3s ease-out";
					gcc.style.height = end_h + 'px';
					// Set up a post-transition event to remove the explicit height
					self.ptwsGlobalCaptionContainer.on("transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd", function () {
						self.ptwsGlobalCaptionContainer.unbind("transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd");
						var gcc = self.ptwsGlobalCaptionContainer.get(0);
						// Blowing away the entire inline style ensures we return to CSS defaults,
						// which effectively apply 'auto' for width and height.
						// This is so the container can properly respond to changes in window/content size.
						gcc.removeAttribute("style");
						//  Trigger a fake "scroll" event so our lazy image loader can act on newly-visible images
						// (This is a bit of a hack, placing it here in the caption module.)
						ptws.fakeScroll();
					});
				};
				self.ev.on('rsAfterInit', function () {
					// Create our own custom container, outside the slideshow container (just after it in the DOM),
					// and create the regular global caption container inside it.  The custom container is what we will use
					// for dynamic resizing in setCurrCaptionHTML .
					self.ptwsGlobalCaptionContainer = $('<div class="ptwsGCaptionContainer"></div>').insertAfter(!self.st.globalCaptionInside ? self.slider : self._sliderOverflow);
					self.globalCaption = $('<div class="ptwsGCaption"></div>').appendTo(self.ptwsGlobalCaptionContainer);
					setCurrCaptionHTML();
				});
				self.ev.on('rsBeforeAnimStart', function () {
					setCurrCaptionHTML();
				});
			}
		}
	});
	if ($.rsModules) {
		$.rsModules.globalCaption = $.rsProto._initGlobalCaption;
	}

	//ptws.findAndInitRoyalsliders();
	ptws.findAndInitGPSLogDisplays();

	ptws.lazyLoadInit();
	$('body').bind('post-load', ptws.lazyLoadInit); // Work with WP.com infinite scroll
});
