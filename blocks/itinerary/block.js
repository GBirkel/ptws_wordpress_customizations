( function ( blocks, editor, element, components, blockEditor ) {
	var el = element.createElement;
	var RichText = blockEditor.RichText;
	var MediaUpload = blockEditor.MediaUpload;
	var useBlockProps = blockEditor.useBlockProps;

	blocks.registerBlockType( 'ptws/itinerary', {
		title: 'PTWS: Itinerary',
		icon: 'index-card',
		category: 'layout',
		attributes: {
			from: {
				type: 'array',
				source: 'children',
				selector: 'h4.from',
			},
			to: {
				type: 'array',
				source: 'children',
				selector: 'h4.to',
			},
			mediaID: {
				type: 'number',
			},
			mediaURL: {
				type: 'string',
				source: 'attribute',
				selector: 'img',
				attribute: 'src',
			},
			steps: {
				type: 'array',
				source: 'children',
				selector: '.steps',
			},
		},

		example: {
			attributes: {
				from: 'Whakatane, New Zealand',
				to: 'Rotorua Museum, Government Gardens, Queens Drive, Rotorua 3046',
				mediaID: 1,
				mediaURL:
					'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f1/2ChocolateChipCookies.jpg/320px-2ChocolateChipCookies.jpg',
				steps: [
					{ type: 'p', props: { children: [ 'turn left at the fart' ] } },
					{ type: 'p', props: { children: [ 'now face west' ] } },
					{ type: 'p', props: { children: [ 'think about direction (you idiot)' ] } },
					{ type: 'p', props: { children: [ 'oops you should have turned right' ] } },
				],
			},
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
					el( 'div', {},
						el( MediaUpload, {
							onSelect: onSelectImage,
							allowedTypes: 'image',
							value: attributes.mediaID,
							render: function ( obj ) {
								return el(
									components.Button,
									{
										className: attributes.mediaID
											? 'image-button'
											: 'button button-large',
										onClick: obj.open,
									},
									! attributes.mediaID
										? 'Upload Image'
										: el( 'img', { src: attributes.mediaURL } )
								);
							},
						} )
					),
					el( 'div', {},
						el( 'div', {},
							el( RichText, {
								tagName: 'h4',
								placeholder: 'From',
								value: attributes.from,
								onChange: function ( value ) {
									props.setAttributes( { from: value } );
								},
								className: 'from',
							} ),
							'to',
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
						el( RichText, {
							tagName: 'p',
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
					el( 'div', {},
						attributes.mediaURL &&
							el( 'img', { src: attributes.mediaURL } )
					),
					el( 'div', {},
						el( 'div', {},
							el( RichText.Content, {
								tagName: 'h4',
								value: attributes.from,
							} ),
							'to',
							el( RichText.Content, {
								tagName: 'h4',
								value: attributes.to,
							} )
						),
						el( RichText.Content, {
							tagName: 'p',
							className: 'steps',
							value: attributes.steps,
						} )
					)
				)
			);
		},
	} );
} )(
	window.wp.blocks,
	window.wp.editor,
	window.wp.element,
	window.wp.components,
	window.wp.blockEditor
);