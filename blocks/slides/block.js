// PTWS Block: Dialogue
// Client-side Javascript portion

( function () {

	var el = window.wp.element.createElement;
	var createBlock = window.wp.blocks.createBlock;
	var data_dispatch = window.wp.data.dispatch;
	var data_select = window.wp.data.select;
    var InnerBlocks = window.wp.blockEditor.InnerBlocks;
    var getBlock = window.wp.blockEditor;
    var useBlockProps = window.wp.blockEditor.useBlockProps;
    var useInnerBlocksProps = window.wp.blockEditor.useInnerBlocksProps;

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

//			const thisBlock = data_select( 'core/block-editor' ).getBlock( props.clientId );
//			const childBlocks = thisBlock ? thisBlock.innerBlocks : [];
//			const dimensions = childBlocks.map(element => {
//				return { width: element.attributes.width,
//						 height: element.attributes.height };
//			});
//			console.log(dimensions);

            const addSlide = function() {
                const newBlock = createBlock( 'ptws/slides-flickr' );
				// The editor saves all its data in a store.
				// https://developer.wordpress.org/block-editor/reference-guides/data/data-core-editor/#getBlocks
				const b = data_select( 'core/block-editor' ).getBlock( props.clientId );
                data_dispatch( 'core/block-editor' ).insertBlock( newBlock, b.innerBlocks.length, props.clientId );
            };

            return el( 'div',
					useBlockProps( { className: props.className + " editing" } ),
					el( InnerBlocks,
						// https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useinnerblocksprops
						useInnerBlocksProps( {
							className: props.className,
							// https://github.com/WordPress/gutenberg/blob/HEAD/packages/block-editor/src/components/inner-blocks/README.md#renderappender
							renderAppender: false,
							orientation: "horizontal",
							template: [
								['ptws/slides-flickr', {
									"flickr_id": "",
									"flickr_id_is_valid": false,
									"flex_ratio": "1",
									"large_thumbnail_height": "0",
									"large_thumbnail_width": "0",
									"taken_time_epoch": "2024-12-12 00:43:44",
									"uploaded_time_epoch": "2024-12-12 00:43:44",
									"updated_time_epoch": "2024-12-12 00:43:44",
									"cached_time_epoch": "2024-12-12 00:43:44"
								} ]
							]
						} )
					),
					el( 'div',
						{ className: 'addslidebutton' },
						el( 'input',
							{ 	type: 'button',
								onClick: addSlide,
								value: "Add Slide"
							}
						)
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