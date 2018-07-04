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
	imageScalePadding: 4,
    keyboardNavEnabled: false,
    loop: false,
    minSlideOffset: 14,
    navigateByClick: false,
    startSlideId: 0,
    sliderDrag: true,
    thumbsFitInViewport: false,
    transitionType:'move',
    visibleNearby: {
        enabled: true,
        centerArea: 0.6,
        center: false,
        breakpoint: 980,
        breakpointCenterArea: 0.8
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