// PTWS Block: Slide Stack
// Client-side Javascript portion

( function () {

	var dataDispatch = window.wp.data.dispatch;
	var dataSelect = window.wp.data.select;
	var useSelect = window.wp.data.useSelect;

	var createBlock = window.wp.blocks.createBlock;

    var InnerBlocks = window.wp.blockEditor.InnerBlocks;
    var useBlockProps = window.wp.blockEditor.useBlockProps;
    var useInnerBlocksProps = window.wp.blockEditor.useInnerBlocksProps;

	var el = window.wp.element.createElement;
	var useEffect = window.wp.element.useEffect;
	var useMemo = window.wp.element.useMemo;
	var useState = window.wp.element.useState;


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


	window.wp.blocks.registerBlockType( 'ptws/slides-stack', {
		icon: {
			background: 'rgba(224, 243, 254, 0.52)',
			src: iconptwsgallery()
		},

		edit: function ( props ) {

			const [idsInputValue, setIdsInputValue] = useState("");
			const [flickrIdsValid, setFlickrIdsValid] = useState(false);
			const [flickrRecords, setFlickrRecords] = useState([]);

			const [inputDebounceTimer, setInputDebounceTimer] = useState(null);
			const [updateLayoutTypeDebounceTimer, setUpdateLayoutTypeDebounceTimer] = useState(null);
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
						measureChildBlocks();
					}, 200));
				setUpdateFlexRatioDebounceTimer(newDebounceTimer)
			}, [watchedInnerBlockFlickrIds]);


			// Interrogate the block tree for the blocks inside this one,
			// parse the dimensions of their images,
			// then calculate their total size based on scaling all images to equal width
			// and stacking them vertically.
			// (The chosen scaling width will be the width of the widest block.)
			// Then save those dimensions in attributes for this block. 
			function measureChildBlocks() {

				const thisBlock = dataSelect( 'core/block-editor' ).getBlock( props.clientId );
				const currentInnerBlocks = thisBlock?.innerBlocks || [];

				props.setAttributes( { image_count: currentInnerBlocks.length } );
				// Empty set?  Get outta heeeeere!
				if (currentInnerBlocks.length == 0) {
					props.setAttributes( { large_thumbnail_height: 0 } );
					props.setAttributes( { large_thumbnail_width: 0 } );
					return;
				}

				// Gather up relevant information from the existing blocks
				const imageWorkingSet = currentInnerBlocks.map((b) => {
					return {
						height: parseFloat(b.attributes.large_thumbnail_height),
						width: parseFloat(b.attributes.large_thumbnail_width)
					}
				});

				// We're only interested in images with non-zero dimensions.
				const siblingValidDimensions = imageWorkingSet.filter((d) => ((d.width > 0) && (d.height > 0)));

				var imageMaxWidth = 0;
				siblingValidDimensions.forEach((b) => {
					if (b.width > imageMaxWidth) { imageMaxWidth = b.width }
				});

				var imgTotalScaledHeight = 0;
				siblingValidDimensions.forEach((b) => {
					imgTotalScaledHeight += (imageMaxWidth / b.width) * b.height;
				});

				// If we didn't get nonzero values for these (kind of impossible) give up.
				if ((imageMaxWidth == 0) || (imgTotalScaledHeight == 0)) {
					props.setAttributes( { large_thumbnail_height: 0 } );
					props.setAttributes( { large_thumbnail_width: 0 } );
					return;
				}

				props.setAttributes( { large_thumbnail_height: imgTotalScaledHeight } );
				props.setAttributes( { large_thumbnail_width: imageMaxWidth } );
			}


            return el( 'div',
					useBlockProps( {
						className: "editing",
						'data-ptws-image-count': attributes.image_count,
						'data-ptws-large-thumbnail-height': attributes.large_thumbnail_height || "0",
						'data-ptws-large-thumbnail-width': attributes.large_thumbnail_width || "0"

					} ),
					(parseInt(attributes.image_count, 10) == 0) ? (
						el( "p", { className: "empty-notification" }, "Empty slide stack" )
					) : undefined,
					el( InnerBlocks,
						// https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useinnerblocksprops
						useInnerBlocksProps( {
							className: props.className,
							// https://github.com/WordPress/gutenberg/blob/HEAD/packages/block-editor/src/components/inner-blocks/README.md#renderappender
							renderAppender: false,
							orientation: "vertical",
							template: []
						} )
					),

			)
		},
        save: function ( props ) {
			var attributes = props.attributes;
            return el( 'div',
						useBlockProps.save( {
							className: props.className,
							style: {
								flex: `${attributes.flex_ratio}`	// Force a string
							},
							'data-ptws-image-count': attributes.image_count,
							'data-ptws-flex-ratio': attributes.flex_ratio || "1",
							'data-ptws-large-thumbnail-height': attributes.large_thumbnail_height || "0",
							'data-ptws-large-thumbnail-width': attributes.large_thumbnail_width || "0"
						} ),
						el( 'div', { className: 'fixed-vertical-stack' },
							el( InnerBlocks.Content )
						)
					);
        },

		transforms: {
			from: [
				{	type: 'block',
					blocks: ['ptws/slides-flickr'],
					isMultiBlock: true,
					// All the blocks must have the "fixed" layout.
					// Stacking "swipe" layout is not supported.
					isMatch: (attributesArray) => {
						console.log(`attributesarray testing length: ${attributesArray.length}`);
						return attributesArray.every((a) => { return a.layout == "fixed" });
					},
					transform: (attributesArray) => {
						const newBlocks = attributesArray.map((a) => {
							return createBlock( 'ptws/slides-flickr', a );
						});
						console.log(`attributesarray length: ${attributesArray.length}`);
						// Gather up relevant information from the existing blocks
						const imageWorkingSet = attributesArray.map((a) => {
							return {
								height: parseFloat(a.large_thumbnail_height),
								width: parseFloat(a.large_thumbnail_width)
							}
						});
						console.log("imageWorkingSet:");
						console.log(imageWorkingSet);

						// We're only interested in images with non-zero dimensions.
						const siblingValidDimensions = imageWorkingSet.filter((d) => ((d.width > 0) && (d.height > 0)));

						var imageMaxWidth = 0;
						siblingValidDimensions.forEach((b) => {
							if (b.width > imageMaxWidth) { imageMaxWidth = b.width }
						});

						var imgTotalScaledHeight = 0;
						siblingValidDimensions.forEach((b) => {
							imgTotalScaledHeight += (imageMaxWidth / b.width) * b.height;
						});

						const newAttributes = {
								image_count: newBlocks.length,
								flex_ratio: 1,
								large_thumbnail_height: imgTotalScaledHeight,
								large_thumbnail_width: imageMaxWidth
							};

						console.log("newAttributes:");
						console.log(newAttributes);

						return createBlock( 'ptws/slides-stack', newAttributes, newBlocks );
					}
				}
			],
			ungroup: ( attributes, innerBlocks ) => {
            	return innerBlocks;
			}
		},
	} );
} )();