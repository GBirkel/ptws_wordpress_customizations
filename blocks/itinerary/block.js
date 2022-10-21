// PTWS Block: Itinerary
// Client-side Javascript portion

( function () {

	var el = window.wp.element.createElement;
	var RichText = window.wp.blockEditor.RichText;
	var MediaUpload = window.wp.blockEditor.MediaUpload;
	var useBlockProps = window.wp.blockEditor.useBlockProps;

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

	window.wp.blocks.registerBlockType( 'ptws/itinerary', {
		title: 'PTWS: Itinerary',
		icon: {
			background: 'rgba(224, 243, 254, 0.52)',
			src: iconptwsgallery()
		},

		edit: function ( props ) {
			var attributes = props.attributes;

			var onSelectImage = function ( media ) {
				return props.setAttributes( {
					mediaURL: media.url,
					mediaID: media.id,
				} );
			};

			return el(
				'div',
				useBlockProps( { className: props.className } ),
				el( 'div', {},
					el( MediaUpload, {
						onSelect: onSelectImage,
						allowedTypes: 'image',
						value: attributes.mediaID,
						render: function ( obj ) {
							if (attributes.mediaID) {
								return el( 'img', { src: attributes.mediaURL, onClick: obj.open } )
							} else {
								return el(
									window.wp.components.Button,
									{
										className: 'button button-large',
										onClick: obj.open,
									},
									'Upload Image'
								)
							}
						},
					} )
				),
				el( 'div', {},
					el( 'div', { className: 'ribbon' },
						el( RichText, {
							tagName: 'h4',
							placeholder: 'From',
							value: attributes.from,
							onChange: function ( value ) {
								props.setAttributes( { from: value } );
							},
							className: 'from',
						} ),
						el( 'p', {}, 'to'),
						el( RichText, {
							tagName: 'h4',
							placeholder: 'To',
							value: attributes.to,
							onChange: function ( value ) {
								props.setAttributes( { to: value } );
							},
							className: 'to',
						} )
					),
					el( 'div', { className: 'route' },
						el( RichText, {
							tagName: 'div',
							multiline: 'p',
							placeholder: 'Write a list of steps...',
							value: attributes.steps,
							onChange: function ( value ) {
								props.setAttributes( { steps: value } );
							},
							className: 'steps',
						} ),
					)
				)
			);
		},

		save: function ( props ) {
			var attributes = props.attributes;

			return el(
				'div',
				useBlockProps.save( { className: props.className } ),
				el( 'div', {},
					attributes.mediaURL &&
						el( 'img', { src: attributes.mediaURL } )
				),
				el( 'div', {},
					el( 'div', { className: 'ribbon' },
						el( RichText.Content, {
							tagName: 'h4',
							value: attributes.from,
							className: 'from',
						} ),
						el( 'p', {}, 'to'),
						el( RichText.Content, {
							tagName: 'h4',
							value: attributes.to,
							className: 'to',
						} )
					),
					el( 'div', { className: 'route' },
						el( RichText.Content, {
							tagName: 'div',
							className: 'steps',
							value: attributes.steps,
						} )
					)
				)
			);
		},
	} );
} )();