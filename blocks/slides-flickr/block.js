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
			console.log("edit");
			console.log(props);

			const layout = props.context["ptws/slides-context-layout"];

			var debounceTimer = null;

			// This handles keyboard-based actions in the input field.
			// Changes to the input value are handled in inputOnChange.
			function inputOnKeyDown(event) {
				if (event.key == "Enter") {
					event.preventDefault();
//					console.log("Enter");
//					if (debounceTimer == null) {
//						console.log("debouncenull");
						//const b = data_select( 'core/block-editor' ).getBlock( props.clientId );
//						data_dispatch( 'core/block-editor' ).unsetBlockEditingMode( props.clientId );
//					}
				}
			}

            function getImage(value) {
                const flickr_id = value.trim();
				debounceTimer = null;

				if (flickr_id.length < 1) {
					props.setAttributes( { flickr_id_is_valid: false } );
					return;
				}

				var re = new RegExp('^\\d+$');
				var m = flickr_id.match(re);
				if (!m) {
					props.setAttributes( { flickr_id_is_valid: false } );
					return;
				}

                if ( this.fetching ) { return; }
                this.fetching = true;

				apiFetch({
					path: wpUrl.addQueryArgs( '/ptws/v1/image/flickrid', { id: flickr_id } )
				}).then(
                    ( flickr_record ) => {
                        this.fetching = false;

						// Extract all the info returned from the server into local block attributes.
						props.setAttributes( {
							flickr_id_is_valid: true,

							cached_time: 			flickr_record.cached_time,
							cached_time_epoch:		flickr_record.cached_time_epoch,
							description: 			flickr_record.description,
							height:					flickr_record.height,
							id:						flickr_record.id,
							large_thumbnail_height: flickr_record.large_thumbnail_height || 0,
							large_thumbnail_url: 	flickr_record.large_thumbnail_url,
							large_thumbnail_width: 	flickr_record.large_thumbnail_width || 0,
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

						const thisHeight = parseFloat(flickr_record.large_thumbnail_height || "0");
						const thisWidth = parseFloat(flickr_record.large_thumbnail_width || "0");
						// If the image we just looked up has no valid thumbnail dimensions,
						// don't bother doing any flex ratio recalculation.
						if ((thisHeight == 0) || (thisWidth == 0)) { return }

						const parentBlockIds = data_select( 'core/block-editor' ).getBlockParents( props.clientId );
						// Can't find a parent block?  Can't do any signaling, so, give up.
						if (!parentBlockIds || (parentBlockIds.length < 1)) { return; }

						// Get a list of all the child blocks of the parent.
						const parentBlock = data_select( 'core/block-editor' ).getBlock(parentBlockIds[0]);
						const childBlocks = parentBlock ? parentBlock.innerBlocks : [];
						const childCount = childBlocks.length;

						// Only one child of the parent?  Must be this block.  So it has no siblings to update.
						if (childCount < 2) { return; }

						// Filter out this block so only the siblings remain.
						const siblings = childBlocks.filter((b) => ( b.clientId != props.clientId ));

						const siblingDimensions = siblings.map(b => {
							return { clientId: b.clientId,
									 width: parseFloat(b.attributes.large_thumbnail_width),
									 height: parseFloat(b.attributes.large_thumbnail_height) };
						});

						// We're only interested in images with non-zero dimensions
						const siblingValidDimensions = siblingDimensions.filter((d) => ((d.width > 0) && (d.height > 0)));

						var imageMaxHeight = thisHeight;
						siblingValidDimensions.forEach((d) => {
							if (d.height > imageMaxHeight) { imageMaxHeight = d.height }
						});

						var imgTotalScaledWidth = (imageMaxHeight / thisHeight) * thisWidth;
						siblingValidDimensions.forEach((d) => {
							imgTotalScaledWidth += (imageMaxHeight / d.height) * d.width;
						});

						if ((imageMaxHeight > 0) && (imgTotalScaledWidth > 0)) {
							siblingValidDimensions.forEach((d) => {
								const imgScaledWidth = (imageMaxHeight / d.height) * d.width;
								const flexRatio = (childCount / imgTotalScaledWidth) * imgScaledWidth;

								console.log(`id: ${d.clientId} flex: ${flexRatio}`);

								// Problem: This is not forcing a "save" on the images it updates.
								// The values are not even getting written into attributes.
								data_dispatch( 'core/block-editor' ).updateBlockAttributes( d.clientId, {
									flex_ratio: flexRatio
								});
							});

							const imgScaledWidth = (imageMaxHeight / thisHeight) * thisWidth;
							const flexRatio = (childCount / imgTotalScaledWidth) * imgScaledWidth;

							console.log(`this flex: ${flexRatio}`);

							// Don't forget to set the flex_ratio of this block too.
							props.setAttributes( {
								flex_ratio: flexRatio,
							} );
						}
                    }

                ).catch(
                    (e) => {
						console.log("Error");
						console.log(e);
						props.setAttributes( { flickr_id_is_valid: false } );
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
				useBlockProps( {
					className: "editing"
					} ),
				renderImageDetails(attributes, true),
				attributes.flickr_id_is_valid ? (
						el( 'img', {
								src: attributes.large_thumbnail_url,
							}
						)
				) : (
					el( 'div', { className: 'emptyimage' }, "" )
				),
				el('div',
					{ className: 'settings' },
					el( PlainText, {
						tagName: 'div',
						placeholder: 'Flickr ID',
						cols: 12,
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