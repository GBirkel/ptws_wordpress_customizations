// PTWS Block: Dialogue
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
			{ className: editing ? 'photodata editing' : 'photodata' },
			el('div', { className: 'flickridisvalid' },
				a.flickr_id_is_valid || "false"
			),
			el('div', { className: 'flickrid' },
				a.flickr_id || ""
			),

			el( 'div', { className: 'cachedtime' },
				a.cached_time || "",
			),
			el( 'div', { className: 'cachedtimeepoch' },
				a.cached_time_epoch || "",
			),
			el( 'div', { className: 'description' },
				a.description || "",
			),
			el( 'div', { className: 'height' },
				a.height || 0,
			),
			el( 'div', { className: 'id' },
				a.id || "",
			),
			el( 'div', { className: 'largethumbnailheight' },
				a.large_thumbnail_height || 0,
			),
			el( 'div', { className: 'largethumbnailurl' },
				a.large_thumbnail_url || "",
			),
			el( 'div', { className: 'largethumbnailwidth' },
				a.large_thumbnail_width || 0,
			),
			el( 'div', { className: 'linkurl' },
				a.link_url || "",
			),
			el( 'div', { className: 'squarethumbnailheight' },
				a.square_thumbnail_height || 0,
			),
			el( 'div', { className: 'squarethumbnailurl' },
				a.square_thumbnail_url || "",
			),
			el( 'div', { className: 'squarethumbnailwidth' },
				a.square_thumbnail_width || 0,
			),
			el( 'div', { className: 'takentime' },
				a.taken_time || "",
			),
			el( 'div', { className: 'takentimeepoch' },
				a.taken_time_epoch || 0,
			),
			el( 'div', { className: 'title' },
				a.title || "",
			),
			el( 'div', { className: 'updatedtime' },
				a.updated_time || "",
			),
			el( 'div', { className: 'updatedtimeepoch' },
				a.updated_time_epoch || 0,
			),
			el( 'div', { className: 'uploadedtime' },
				a.uploaded_time || "",
			),
			el( 'div', { className: 'uploadedtimeepoch' },
				a.uploaded_time_epoch || 0,
			),
			el( 'div', { className: 'width' },
				a.width || 0,
			)
		);
	}

	window.wp.blocks.registerBlockType( 'ptws/flickr-image', {
		title: 'PTWS: Flickr Image',
		category: 'text',
		icon: {
			background: 'rgba(224, 243, 254, 0.52)',
			src: iconptwsgallery()
		},

		edit: function ( props ) {
			var attributes = props.attributes;

			var debounceTimer = null;

			// This handles keyboard-based actions in the input field.
			// Changes to the input value are handled in inputOnChange.
			function inputOnKeyDown(event) {
				console.log("key");
				if (event.key == "Enter") {
					event.preventDefault();
					console.log("Enter");
					if (debounceTimer == null) {
						console.log("debouncenull");
						//const b = data_select( 'core/block-editor' ).getBlock( props.clientId );
						data_dispatch( 'core/block-editor' ).unsetBlockEditingMode( props.clientId );
					}
				}
			}

            function getImage(value) {
                const flickr_id = value.trim();
				debounceTimer = null;

				if (flickr_id.length < 1) {
					props.setAttributes( { flickr_id_is_valid: "false" } );
					return;
				}

                if ( this.fetching ) { return; }
                this.fetching = true;
				console.log(`Fetching ${flickr_id}`);

				apiFetch({
					path: wpUrl.addQueryArgs( '/ptws/v1/image/flickrid', { id: flickr_id } )
				}).then(
                    ( flickr_record ) => {
                        this.fetching = false;
						console.log(flickr_record);
						props.setAttributes( {
							flickr_id_is_valid: "true",

							cached_time: 			flickr_record.cached_time,
							cached_time_epoch:		flickr_record.cached_time_epoch,
							description: 			flickr_record.description,
							height:					flickr_record.height,
							id:						flickr_record.id,
							large_thumbnail_height: flickr_record.large_thumbnail_height,
							large_thumbnail_url: 	flickr_record.large_thumbnail_url,
							large_thumbnail_width: 	flickr_record.large_thumbnail_width,
							link_url: 				flickr_record.link_url,
							square_thumbnail_height: flickr_record.square_thumbnail_height,
							square_thumbnail_url: 	flickr_record.square_thumbnail_url,
							square_thumbnail_width: flickr_record.square_thumbnail_width,
							taken_time: 			flickr_record.taken_time,
							taken_time_epoch: 		flickr_record.taken_time_epoch,
							title: 					flickr_record.title,
							updated_time: 			flickr_record.updated_time,
							updated_time_epoch: 	flickr_record.updated_time_epoch,
							uploaded_time: 			flickr_record.uploaded_time,
							uploaded_time_epoch: 	flickr_record.uploaded_time_epoch,
							width: 					flickr_record.width,
						} );
                    }
                ).catch(
                    (e) => {
						console.log("Error");
						console.log(e);
						props.setAttributes( { flickr_id_is_valid: "false" } );
                        this.fetching = false;
                    }
                );
            }

			function delayedFetch(value) {
				if (debounceTimer) { clearTimeout(debounceTimer); }
				debounceTimer = (setTimeout(() => getImage(value), 300));
			}

			return el(
				'div',
				useBlockProps( { className: props.className } ),
				renderImageDetails(attributes, true),
				el( 'div', { className: 'image' },
					(attributes.flickr_id_is_valid == "true") ?
						el( 'img', { src: attributes.square_thumbnail_url } ) :
						el( 'div', { className: 'empty' }, "" )
				),
				el('div',
					{ className: 'settings' },
					el( PlainText, {
						tagName: 'div',
						placeholder: 'Flickr ID',
						value: attributes.flickr_id,
						onKeyDown: inputOnKeyDown,
						onChange: function ( value ) {
							props.setAttributes( { flickr_id: value } );
							delayedFetch(value);
						}
					} )
				)
			)
		},

		save: function ( props ) {
			var attributes = props.attributes;
			return el(
				'div',
				useBlockProps.save( { className: props.className} ),
				renderImageDetails(attributes, false),
				el( 'div', { className: 'images' },
					(!(attributes.flickr_id_is_valid == "true")) ?
						el( 'figure', { className: 'empty' }, "" ) :
						el( 'figure', { },
							el( 'a', {
									href: attributes.link_url,
									title: attributes.title
								},
								el( 'img', {
										src: attributes.large_thumbnail_url,
										style: {
											maxWidth: "800px"
										},
										'data-ptws-height': attributes.height,
										'data-ptws-width': attributes.width
									}
								)
							)
						)
				)
			);
		},
	} );
} )();