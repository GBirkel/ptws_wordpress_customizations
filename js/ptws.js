var ptws = {

	fakeScroll: function() {
		var x = window.scrollX;
		var y = window.scrollY;
		window.scrollTo(x, y+1);
		window.scrollTo(x, y);
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
	var b = jQuery(e.target).closest('span');
	var f = b.attr('filter');
	// After the slide we trigger a fake scroll event,
	// so the lazy-load images actually appear
	if (f) {
		jQuery("div.gear[trips~='" + f + "']").slideDown(
			null, function() {ptws.fakeScroll()});
		jQuery("div.gear:not([trips~='" + f + "'])").slideUp();
	} else {
		jQuery("div.gear").slideDown(
			null, function() {ptws.fakeScroll()});
	}
	jQuery("div.gearFilterButtons").children().removeClass('active');
	b.addClass('active');	
}


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
	$.rsModules.globalCaption = $.rsProto._initGlobalCaption;

  $('div.royalSlider').royalSlider({
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
    transitionType:'move',
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
});
