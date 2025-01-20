// PTWS Block: Dialogue
// Client-side Javascript portion

( function () {

	var apiFetch = window.wp.apiFetch;

	var dataDispatch = window.wp.data.dispatch;
	var dataSelect = window.wp.data.select;
	var useSelect = window.wp.data.useSelect;

	var createBlock = window.wp.blocks.createBlock;

    var InnerBlocks = window.wp.blockEditor.InnerBlocks;
	var PlainText = window.wp.blockEditor.PlainText;
    var useBlockProps = window.wp.blockEditor.useBlockProps;
    var useInnerBlocksProps = window.wp.blockEditor.useInnerBlocksProps;

	var el = window.wp.element.createElement;
	var useEffect = window.wp.element.useEffect;
	var useMemo = window.wp.element.useMemo;
	var useState = window.wp.element.useState;

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

			const [idsInputValue, setIdsInputValue] = useState("");
			const [flickrIdsValid, setFlickrIdsValid] = useState(false);
			const [flickrRecords, setFlickrRecords] = useState([]);

			const [inputDebounceTimer, setInputDebounceTimer] = useState(null);
			const [updateFlexRatioDebounceTimer, setUpdateFlexRatioDebounceTimer] = useState(null);

			var attributes = props.attributes;

			// useSelect to watch the child blocks of this one for any changes.
			const { watchedInnerBlocks } = useSelect( ( select ) => {
				const thisBlock = select( 'core/block-editor' ).getBlock( props.clientId );
				return { watchedInnerBlocks: thisBlock ? thisBlock.innerBlocks : [] }
			}, [] );

			// useMemo to restrict the changes we're interested in to the clientIds and their order.
			const watchedInnerBlockFlickrIds = useMemo( () => {
				// Get the Flickr IDs for all the current blocks, sort them alphabetically, and join them into a string.
				// This represents what we actually want to watch for changes.
				// (When the user re-orders slides in an existing set, their assigned flex ratios do not need to change.)
				return watchedInnerBlocks.map( (b) => b.attributes.flickr_id ).sort().join(',');
			}, [ watchedInnerBlocks ] );


			// useEffect to take action when the array of clientIds appears to have changed.
			useEffect(() => {
				if (updateFlexRatioDebounceTimer) { clearTimeout(updateFlexRatioDebounceTimer); }
				const newDebounceTimer = (setTimeout(() => {
						setUpdateFlexRatioDebounceTimer(null);
						updateFlexRatioForChildBlocks()
					}, 200));
				setUpdateFlexRatioDebounceTimer(newDebounceTimer)
			}, [watchedInnerBlockFlickrIds]);


			async function processInitialIds(idString) {
				const records = await resolveImages(idString);
				createImages(records);
			}


			// useEffect with no arguments to run an initial check of the initial_ids attribute.
			useEffect(() => {
				if (!attributes.isPreviewMode) {
					if (attributes.initial_ids.trim() != "") {
						console.log(`Found initial_ids: ${attributes.initial_ids}`);
						processInitialIds(attributes.initial_ids);
						props.setAttributes( { initial_ids: "" } );
					}
				}
			}, []);


			function updateFlexRatioForChildBlocks() {

				console.log("Decided that inner blocks changed");

				const thisBlock = dataSelect( 'core/block-editor' ).getBlock( props.clientId );
				const currentInnerBlocks = thisBlock?.innerBlocks || [];

				// Empty set?  Get outta heeeeere!
				if (currentInnerBlocks.length == 0) { return; }

				const imageWorkingSet = currentInnerBlocks.map((b) => {
					return {
						clientId: b.clientId,
						block: b,
						flexRatio: null,
						height: parseFloat(b.attributes.large_thumbnail_height),
						width: parseFloat(b.attributes.large_thumbnail_width)
					}
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
					dataDispatch( 'core/block-editor' ).updateBlockAttributes( b.clientId, {
						flex_ratio: b.flexRatio
					});
				});
			}


            async function resolveImages(idString) {
                const idStringTrimmed = idString.trim();
				setInputDebounceTimer(null);

				if (idStringTrimmed.length < 1) {
					setFlickrIdsValid(false);
					setFlickrRecords([]);
					return [];
				}

				var re = new RegExp('^[\\d ,]+$');
				if (!idStringTrimmed.match(re)) {
					setFlickrIdsValid(false);
					setFlickrRecords([]);
					return [];
				}

				var potentialIds = idStringTrimmed.split(',');
				potentialIds = potentialIds.map((id) => { return id.trim() });

                if ( this.fetching ) { return []; }
                this.fetching = true;
				var records = [];

				const fetches = potentialIds.map((id) => {
					return new Promise((resolve, reject) => {
						apiFetch({
							path: wpUrl.addQueryArgs( '/ptws/v1/image/flickrid', { id: id } )
						}).then(
							( flickr_record ) => {
								resolve(flickr_record);
							}
						).catch(
							(err) => {
								console.log("Error");
								console.error(err);
								reject(err);
							}
						);
					})
				});

                this.fetching = false;

				try {
					records = await Promise.all(fetches);
				} catch (err) {
					console.log("Await all error");
					console.error(err);
					setFlickrIdsValid(false);
					setFlickrRecords([]);
					return [];
				}

				// If we get this far, the records and Ids are valid.
				console.log(records);
				setFlickrRecords(records);
				setFlickrIdsValid(true);
				return records;
            }


            function createImages(records) {
				if (records.length < 1) { return; }

				// Prepare some data to work with.
				// We're going to mix the info about the blocks we haven't created yet -
				// but have fetched records for - with the blocks that already exist.
				var imageWorkingSet = records.map((r) => {
					return {
						record: r,
						height: parseFloat(r.large_thumbnail_height || 0),
						width: parseFloat(r.large_thumbnail_width || 0)
					}
				});

				// We're only interested in images with non-zero dimensions.
				// We will refuse to create slides that have a 0-width or 0-height thumbnail,
				// and we will refuse to update existing slides that have the same.
				const siblingValidDimensions = imageWorkingSet.filter((d) => ((d.width > 0) && (d.height > 0)));

				// Now create and add the new blocks all at once.
				siblingValidDimensions.forEach((b) => {
					addFlickrSlide(b.record, b.flexRatio);
				});
			}


			// This handles keyboard-based actions in the input field.
			// Changes to the input value are handled in inputOnChange.
			function inputOnKeyDown(event) {
				if (event.key == "Enter") {
					event.preventDefault();
					if (flickrIdsValid) {
						createImages(flickrRecords);
						setIdsInputValue("");
						setFlickrIdsValid(false);
						setFlickrRecords([]);
					}
				// If the user hits delete AND the text area is empty AND there are zero slides in the layout,
				// remove this block.
				} else if (event.key == "Delete") {
					if (idsInputValue === "") {
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


			function onIdsInputChange(value) {
				if (inputDebounceTimer) { clearTimeout(inputDebounceTimer); }
				newInputDebounceTimer = (setTimeout(() => resolveImages(value), 300));
				setInputDebounceTimer(newInputDebounceTimer)
			}


            function addFlickrSlide(flickr_record, flexRatio) {

				const newAttributes = {
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

                const newBlock = createBlock( 'ptws/slides-flickr', newAttributes );
				// The editor saves all its data in a store.
				// https://developer.wordpress.org/block-editor/reference-guides/data/data-core-editor/#getBlocks
				const b = dataSelect( 'core/block-editor' ).getBlock( props.clientId );
                dataDispatch( 'core/block-editor' ).insertBlock( newBlock, b.innerBlocks.length, props.clientId );
            };


            return el( 'div',
					useBlockProps( {
						className: "editing",
						'data-ptws-layout': attributes.layout,
						'data-ptws-initial-ids': attributes.initial_ids
					} ),
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
							value: idsInputValue,
							onKeyDown: inputOnKeyDown,
							onChange: function ( value ) {
								setIdsInputValue(value);
								onIdsInputChange(value);
							}
						} )
					)
			)
		},
        save: function ( props ) {
			var attributes = props.attributes;
            return el( 'div',
						useBlockProps.save( {
							className: props.className,
							'data-ptws-layout': attributes.layout,
							'data-ptws-initial-ids': attributes.initial_ids
						} ),
						el( 'div',
							{ className: 'size-limiter' },
							el( InnerBlocks.Content )
						)
					);
        },
		transforms: {
			from: [
				{	type: 'block',
					blocks: ['core/shortcode'],
					// Example shortcodes we're looking for here:
					// [ptwsgallery fixed="50630827036,50630082778"]
					// [ptwsgallery swipe="50630826096,50630082093,50630081603"]
					isMatch: (attributes) => {
						const name = attributes?.text?.match(/\s*\[ptwsgallery\s+/);
						// Need one or the other of these
						const fixed = attributes?.text?.match(/\s+fixed="[\d,\s]+"/);
						const swipe = attributes?.text?.match(/\s+swipe="[\d,\s]+"/);
						return (name && (fixed || swipe));
					},
					transform: (attributes, b, c) => {
						const fixed = attributes?.text?.match(/\s+fixed="([\d,\s]+)"/);
						const swipe = attributes?.text?.match(/\s+swipe="([\d,\s]+)"/);
						const newAttributes = {
								layout: swipe ? "swipe" : "fixed",
								initial_ids: swipe ? swipe[1] : fixed[1]
							};
						return createBlock( 'ptws/slides', newAttributes );	
					},
				},
				{	type: 'shortcode',
					tag: 'ptwsgallery',
					attributes: {
						layout: {
							type: 'string',
							shortcode: (attributes) => attributes?.fixed ? "fixed" : "swipe",
						},
						initial_ids: {
							type: 'string',
							shortcode: (attributes) => attributes?.fixed || attributes?.swipe,
						}
					}
				}
			]
		}
	} );
} )();