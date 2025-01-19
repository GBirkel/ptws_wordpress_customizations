// PTWS Block: Dialogue
// Client-side Javascript portion

( function () {

	var el = window.wp.element.createElement;
	var useState = window.wp.element.useState;
	var createBlock = window.wp.blocks.createBlock;
	var dataDispatch = window.wp.data.dispatch;
	var dataSelect = window.wp.data.select;
    var InnerBlocks = window.wp.blockEditor.InnerBlocks;
    var getBlock = window.wp.blockEditor;
	var PlainText = window.wp.blockEditor.PlainText;
    var useBlockProps = window.wp.blockEditor.useBlockProps;
    var useInnerBlocksProps = window.wp.blockEditor.useInnerBlocksProps;
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

	window.wp.blocks.registerBlockType( 'ptws/slides', {
		icon: {
			background: 'rgba(224, 243, 254, 0.52)',
			src: iconptwsgallery()
		},

		edit: function ( props ) {

			const [flickrIds, setFlickrIds] = useState("");
			const [flickrIdsValid, setFlickrIdsValid] = useState(false);
			const [flickrRecords, setFlickrRecords] = useState([]);

			var debounceTimer = null;


            function resolveImages(value) {
                const flickr_id = value.trim();
				debounceTimer = null;

				if (flickr_id.length < 1) {
					setFlickrIdsValid(false);
					setFlickrRecords([]);
					return;
				}

				var re = new RegExp('^\\d+$');
				var m = flickr_id.match(re);
				if (!m) {
					setFlickrIdsValid(false);
					setFlickrRecords([]);
					return;
				}

                if ( this.fetching ) { return; }
                this.fetching = true;

				apiFetch({
					path: wpUrl.addQueryArgs( '/ptws/v1/image/flickrid', { id: flickr_id } )
				}).then(
                    ( flickr_record ) => {
                        this.fetching = false;

						setFlickrIdsValid(true);
						setFlickrRecords([flickr_record]);
                    }

                ).catch(
                    (e) => {
						console.log("Error");
						console.log(e);
						setFlickrIdsValid(false);
						setFlickrRecords([]);
                        this.fetching = false;
                    }
                );
            }


            function createImages() {
				if (!flickrIdsValid) { return; }
				if (flickrRecords.length < 1) { return; }

				// Prepare some data to work with.
				// We're going to mix the info about the blocks we haven't created yet -
				// but have fetched records for - with the blocks that already exist.
				var imageWorkingSet = flickrRecords.map((r) => {
					return {
						clientId: null,
						record: r,
						block: null,
						flexRatio: null,
						height: parseFloat(r.large_thumbnail_height || 0),
						width: parseFloat(r.large_thumbnail_width || 0)
					}
				});

				// Get a list of all the current child blocks.
				const thisBlock = dataSelect( 'core/block-editor' ).getBlock( props.clientId );
				const childBlocks = thisBlock ? thisBlock.innerBlocks : [];

				// Same data structure, but these have a 'block' instead of a 'record'.
				childBlocks.forEach((b) => {
					imageWorkingSet.push({
						clientId: b.clientId,
						record: null,
						block: b,
						flexRatio: null,
						height: parseFloat(b.attributes.large_thumbnail_height),
						width: parseFloat(b.attributes.large_thumbnail_width)
					});
				});

				console.log("Working set:");
				console.log(imageWorkingSet);

				// We're only interested in images with non-zero dimensions.
				// We will refuse to create slides that have a 0-width or 0-height thumbnail,
				// and we will refuse to update existing slides that have the same.
				const siblingValidDimensions = imageWorkingSet.filter((d) => ((d.width > 0) && (d.height > 0)));

				var imageMaxHeight = 0;
				siblingValidDimensions.forEach((b) => {
					if (b.height > imageMaxHeight) { imageMaxHeight = b.height }
				});

				var imgTotalScaledWidth = 0;
				siblingValidDimensions.forEach((b) => {
					imgTotalScaledWidth += (imageMaxHeight / b.height) * b.width;
				});

				// If we didn't get nonzero values for these (kind of impossible) give up.
				if ((imageMaxHeight == 0) || (imgTotalScaledWidth == 0)) {
					return;
				}

				siblingValidDimensions.forEach((b) => {
					const imgScaledWidth = (imageMaxHeight / b.height) * b.width;
					const flexRatio = (imageWorkingSet.length / imgTotalScaledWidth) * imgScaledWidth;

					console.log(`id: ${b.clientId} flex: ${flexRatio}`);
					// Write the new value back into the record
					b.flexRatio = flexRatio;
				});

				// Send attribute updates to all the blocks that exist already
				siblingValidDimensions.forEach((b) => {
					if (b.clientId) {
						dataDispatch( 'core/block-editor' ).updateBlockAttributes( b.clientId, {
							flex_ratio: b.flexRatio
						});
					}
				});

				// Now create and add the new blocks all at once.
				siblingValidDimensions.forEach((b) => {
					if (!b.clientId && b.record) {
						addFlickrSlide(b.record, b.flexRatio);
					}
				});

				setFlickrIds("");
				setFlickrIdsValid(false);
				setFlickrRecords([]);
			}


			// This handles keyboard-based actions in the input field.
			// Changes to the input value are handled in inputOnChange.
			function inputOnKeyDown(event) {
				if (event.key == "Enter") {
					event.preventDefault();
					createImages();
				// If the user hits delete AND the text area is empty AND there are zero slides in the layout,
				// remove this block.
				} else if (event.key == "Delete") {
					if (flickrIds === "") {
						// Get a list of all the current child blocks.
						const thisBlock = dataSelect( 'core/block-editor' ).getBlock( props.clientId );
						if (thisBlock) {
							if (thisBlock.innerBlocks.length == 0) {
                				dataDispatch( 'core/block-editor' ).removeBlock( props.clientId );
							}
						}
					}
				}
			}


			function delayedFetch(value) {
				if (debounceTimer) { clearTimeout(debounceTimer); }
				debounceTimer = (setTimeout(() => resolveImages(value), 300));
			}


            function addFlickrSlide(flickr_record, flexRatio) {

				const newProps = {
						cached_time: 			flickr_record.cached_time,
						cached_time_epoch:		flickr_record.cached_time_epoch,
						description: 			flickr_record.description,
						flex_ratio:				flexRatio,
						flickr_id:				flickr_record.flickr_id,
						flickr_id_is_valid:		true,
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
					};

                const newBlock = createBlock( 'ptws/slides-flickr', newProps );
				// The editor saves all its data in a store.
				// https://developer.wordpress.org/block-editor/reference-guides/data/data-core-editor/#getBlocks
				const b = dataSelect( 'core/block-editor' ).getBlock( props.clientId );
                dataDispatch( 'core/block-editor' ).insertBlock( newBlock, b.innerBlocks.length, props.clientId );
            };

            return el( 'div',
					useBlockProps( { className: "editing" } ),
					el( InnerBlocks,
						// https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useinnerblocksprops
						useInnerBlocksProps( {
							className: props.className,
							// https://github.com/WordPress/gutenberg/blob/HEAD/packages/block-editor/src/components/inner-blocks/README.md#renderappender
							renderAppender: false,
							orientation: "horizontal",
							template: []
						} )
					),
					el( 'div',
						{ className: "add-slide-zone" },
						el( "div", { className: "parse-status" },
							flickrIdsValid ? "\u2714" : "\u2718"
						),
						el( PlainText, {
							tagName: 'div',
							placeholder: 'Add Flickr ID(s)',
							value: flickrIds,
							onKeyDown: inputOnKeyDown,
							onChange: function ( value ) {
								setFlickrIds(value);
								delayedFetch(value);
							}
						} )
					)
			)
		},
        save: function ( props ) {
            return el( 'div',
						useBlockProps.save( { className: props.className } ),
						el( 'div',
							{ className: 'size-limiter' },
							el( InnerBlocks.Content )
						)
					);
        }
	} );
} )();