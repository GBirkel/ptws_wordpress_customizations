// PTWS Block: Flickr Slide
// Client-side Javascript portion

( function () {

	var el = window.wp.element.createElement;
	var RichText = window.wp.blockEditor.RichText;
	var data_select = window.wp.data.select;
	var data_dispatch = window.wp.data.dispatch;
	var PlainText = window.wp.blockEditor.PlainText;
	var useBlockProps = window.wp.blockEditor.useBlockProps;
	var apiFetch = window.wp.apiFetch;
	var wpUrl = window.wp.url;

	function iconptwsgallery() {
		return el(
			'svg', 
			{ width: '20px', height: '20px', viewBox: '0 0 100 100', xmlns: 'http://www.w3.org/2000/svg' },
			el('path', { d: 'm50 20c4.1367 0 7.5-3.3633 7.5-7.5s-3.3633-7.5-7.5-7.5-7.5 3.3633-7.5 7.5 3.3633 7.5 7.5 7.5zm0-10c1.3789 0 2.5 1.1211 2.5 2.5s-1.1211 2.5-2.5 2.5-2.5-1.1211-2.5-2.5 1.1211-2.5 2.5-2.5z' }),
			el('path', { d: 'm53.535 42.93l-3.5352 3.5352-3.5352-3.5352-3.5352 3.5352 3.5352 3.5352-3.5352 3.5352 3.5352 3.5352 3.5352-3.5352 3.5352 3.5352 3.5352-3.5352-3.5352-3.5352 3.5352-3.5352z' }),
			el('path', { d: 'm48.75 87.715l-3.2305-3.2344-3.5391 3.5391 6.7695 6.7656 9.2695-9.2656-3.5391-3.5391z' }),
			el('path', { d: 'm67.5 12.5c0 3.332-5 3.332-5 0s5-3.332 5 0' }),
			el('path', { d: 'm77.051 12.5c0 3.332-5 3.332-5 0s5-3.332 5 0' }),
			el('path', { d: 'm86.109 15.281c0 3.332-5 3.332-5 0 0-3.3359 5-3.3359 5 0' }),
			el('path', { d: 'm92.617 22.098c0 3.332-5 3.332-5 0 0-3.3359 5-3.3359 5 0' }),
			el('path', { d: 'm95 31.23c0 3.332-5 3.332-5 0s5-3.332 5 0' }),
			el('path', { d: 'm92.617 40.371c0 3.3359-5 3.3359-5 0 0-3.332 5-3.332 5 0' }),
			el('path', { d: 'm86.109 47.199c0 3.3359-5 3.3359-5 0 0-3.332 5-3.332 5 0' }),
			el('path', { d: 'm77.051 49.98c0 3.332-5 3.332-5 0 0-3.3359 5-3.3359 5 0' }),
			el('path', { d: 'm67.5 50.02c0 3.332-5 3.332-5 0s5-3.332 5 0' }),
			el('path', { d: 'm37.5 50c0 3.332-5 3.332-5 0s5-3.332 5 0' }),
			el('path', { d: 'm27.91 50c0 3.332-5 3.332-5 0s5-3.332 5 0' }),
			el('path', { d: 'm18.922 52.797c0 3.332-5 3.332-5 0s5-3.332 5 0' }),
			el('path', { d: 'm12.363 59.629c0 3.332-5 3.332-5 0 0-3.3359 5-3.3359 5 0' }),
			el('path', { d: 'm10 68.77c0 3.332-5 3.332-5 0s5-3.332 5 0' }),
			el('path', { d: 'm12.383 77.902c0 3.332-5 3.332-5 0 0-3.332 5-3.332 5 0' }),
			el('path', { d: 'm18.922 84.719c0 3.3359-5 3.3359-5 0 0-3.332 5-3.332 5 0' }),
			el('path', { d: 'm27.949 87.5c0 3.332-5 3.332-5 0s5-3.332 5 0' }),
			el('path', { d: 'm37.5 87.516c0 3.332-5 3.332-5 0s5-3.332 5 0' })
		); 
	}

	function renderImageDetails(a, editing) {
		return el('div',
			{	className: editing ? 'photodata editing' : 'photodata',
				'data-ptws-flickr-id': a.flickr_id,
				'data-ptws-flickr-id-is-valid': a.flickr_id_is_valid,
				'data-ptws-cached-time': a.cached_time || "",
				'data-ptws-cached-time-epoch': a.cached_time_epoch || "0",
				'data-ptws-flex-ratio': a.flex_ratio || "1",
				'data-ptws-height': a.height || "0",
				'data-ptws-id': a.id || "",
				'data-ptws-large-thumbnail-height': a.large_thumbnail_height || "0",
				'data-ptws-large-thumbnail-width': a.large_thumbnail_width || "0",
				'data-ptws-square-thumbnail-height': a.square_thumbnail_height || "0",
				'data-ptws-square-thumbnail-width': a.square_thumbnail_width || "0",
				'data-ptws-taken-time': a.taken_time || "",
				'data-ptws-taken-time-epoch': a.taken_time_epoch || "0",
				'data-ptws-updated-time': a.updated_time || "",
				'data-ptws-updated-time-epoch': a.updated_time_epoch || "0",
				'data-ptws-uploaded-time': a.uploaded_time || "",
				'data-ptws-uploaded-time-epoch': a.uploaded_time_epoch || "0",
				'data-ptws-width': a.width || "0",
 			},

			el( 'div', { className: 'description' },
				a.description || "",
			),
			el( 'div', { className: 'largethumbnailurl' },
				a.large_thumbnail_url || "",
			),
			el( 'div', { className: 'linkurl' },
				a.link_url || "",
			),
			el( 'div', { className: 'squarethumbnailurl' },
				a.square_thumbnail_url || "",
			),
			el( 'div', { className: 'title' },
				a.title || "",
			)
		);
	}

	window.wp.blocks.registerBlockType( 'ptws/slides-flickr', {
		title: 'PTWS: Flickr Slide',
		category: 'text',
		usesContext: ["ptws/slides-context"],
		icon: {
			background: 'rgba(224, 243, 254, 0.52)',
			src: iconptwsgallery()
		},

		edit: function ( props ) {
			var attributes = props.attributes;

			const layout = props.context["ptws/slides-context-layout"];

			return el(
				'div',
				useBlockProps( {
					className: "editing"
					} ),
				renderImageDetails(attributes, true),
				attributes.flickr_id_is_valid ? (
						el( 'img', {
								src: attributes.large_thumbnail_url,
								title: attributes.title
							}
						)
				) : (
					el( 'div', { className: 'emptyimage' }, "" )
				),
				el('div',
					{ className: 'metadata' },
					el( 'div', { className: 'flickr-id' }, attributes.flickr_id )
				)
			)
		},

		save: function ( props ) {
			var attributes = props.attributes;
			return el(
				'div',
				useBlockProps.save( {
					className: props.className,
					style: {
						flex: `${attributes.flex_ratio}`	// Force a string
					},
				} ),
				renderImageDetails(attributes, false),
				attributes.flickr_id_is_valid ? (
					el( 'a', {
							href: attributes.link_url,
							title: attributes.title
						},
						el( 'img', {
								src: attributes.large_thumbnail_url,
								'data-ptws-height': attributes.large_thumbnail_height,
								'data-ptws-width': attributes.large_thumbnail_width
							}
						)
					)
				) : (
					// If the flickr info is not valid, render an empty image placeholder
					el( 'div', { className: 'emptyimage' }, "" )
				),
				(attributes.flickr_id_is_valid && attributes.description) ? (
					el( 'div',
						{ className: 'imgComment' },
						el( 'p', {}, attributes.description )
					)
				) : undefined
			);
		},
	} );
} )();