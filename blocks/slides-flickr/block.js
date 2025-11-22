// PTWS Block: Flickr Slide
// Client-side Javascript portion

( function () {

	var el = window.wp.element.createElement;
	var useBlockProps = window.wp.blockEditor.useBlockProps;

	function iconptwsgallery() {
		return el(
			'svg', 
			{ width: '20px', height: '20px', viewBox: '0 0 24 24', xmlns: 'http://www.w3.org/2000/svg' },
			el('path', { d: 'm7 6.5 4 2.5-4 2.5z' }),
			el('path', { d: 'm5 3c-1.10457 0-2 .89543-2 2v14c0 1.1046.89543 2 2 2h14c1.1046 0 2-.8954 2-2v-14c0-1.10457-.8954-2-2-2zm14 1.5h-14c-.27614 0-.5.22386-.5.5v10.7072l3.62953-2.6465c.25108-.1831.58905-.1924.84981-.0234l2.92666 1.8969 3.5712-3.4719c.2911-.2831.7545-.2831 1.0456 0l2.9772 2.8945v-9.3568c0-.27614-.2239-.5-.5-.5zm-14.5 14.5v-1.4364l4.09643-2.987 2.99567 1.9417c.2936.1903.6798.1523.9307-.0917l3.4772-3.3806 3.4772 3.3806.0228-.0234v2.5968c0 .2761-.2239.5-.5.5h-14c-.27614 0-.5-.2239-.5-.5z',
						 fillRule: "evenodd",
						 clipRule: "evenodd"})
		);
	}

	function renderImageDetails(a, editing) {
		return el('div',
			{	className: editing ? 'photodata editing' : 'photodata',
				'data-ptws-flickr-id': a.flickr_id,
				'data-ptws-flickr-id-is-valid': a.flickr_id_is_valid,
				'data-ptws-cached-time': a.cached_time || "",
				'data-ptws-cached-time-epoch': a.cached_time_epoch || "0",
				'data-ptws-embed-secret': a.embed_secret || "",
				'data-ptws-flex-ratio': a.flex_ratio || "1",
				'data-ptws-height': a.height || "0",
				'data-ptws-id': a.id || "",
				'data-ptws-large-thumbnail-height': a.large_thumbnail_height || "0",
				'data-ptws-large-thumbnail-width': a.large_thumbnail_width || "0",
				'data-ptws-latitude': a.latitude || "",
				'data-ptws-layout': a.layout || "fixed",
				'data-ptws-longitude': a.longitude || "",
				'data-ptws-media': a.media || "photo",
				'data-ptws-square-thumbnail-height': a.square_thumbnail_height || "0",
				'data-ptws-square-thumbnail-width': a.square_thumbnail_width || "0",
				'data-ptws-taken-time': a.taken_time || "",
				'data-ptws-taken-time-epoch': a.taken_time_epoch || "0",
				'data-ptws-updated-time': a.updated_time || "",
				'data-ptws-updated-time-epoch': a.updated_time_epoch || "0",
				'data-ptws-uploaded-time': a.uploaded_time || "",
				'data-ptws-uploaded-time-epoch': a.uploaded_time_epoch || "0",
				'data-ptws-video-height': a.video_height || "0",
				'data-ptws-video-width': a.video_width || "0",
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
			),
			el( 'div', { className: 'videourl' },
				a.video_url || "",
			)
		);
	}

	window.wp.blocks.registerBlockType( 'ptws/slides-flickr', {
		title: 'PTWS: Flickr Slide',
		category: 'text',
		icon: {
			background: 'rgba(224, 243, 254, 0.52)',
			src: iconptwsgallery()
		},

		edit: function ( props ) {
			var attributes = props.attributes;

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

			if (attributes.layout == "swipe") {
				return el(
					'div',
					useBlockProps.save( { className: "rsContent" } ),
					renderImageDetails(attributes, false),
					attributes.flickr_id_is_valid ? (
						el( 'a', {
								href: attributes.link_url,
								title: attributes.title
							},
							el( 'img', {
									className: 'rsImg',
									src: attributes.large_thumbnail_url,
									'data-rsh': attributes.large_thumbnail_height,
									'data-rsw': attributes.large_thumbnail_width,
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
							{ className: 'rsCaption' },
							el( 'p', {}, attributes.description )
						)
					) : undefined
				);

			} else {
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
			}
		},

		deprecated: [
			{
				// Old version of attributes, lacking "embed_secret", "latitude", "longitude", "media", "video_width", "video_height", "video_url"
				attributes: {
					"flickr_id": {
						"type": "string",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-flickr-id"
					},
					"flickr_id_is_valid": {
						"type": "boolean",
						"default": false,
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-flickr-id-is-valid"
					},
					"cached_time": {
						"type": "string",
						"default": "1900-01-01 00:00:00",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-cached-time"
					},
					"cached_time_epoch": {
						"type": "string",
						"default": "0",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-cached-time-epoch"
					},
					"description": {
						"type": "string",
						"default": "",
						"source": "text",
						"selector": "div.photodata > div.description"
					},
					"flex_ratio": {
						"type": "string",
						"default": "1",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-flex-ratio"
					},
					"height": {
						"type": "string",
						"default": "0",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-height"
					},
					"id": {
						"type": "string",
						"default": "0",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-id"
					},
					"layout": {
						"type": "string",
						"default": "fixed",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-layout"
					},
					"large_thumbnail_height": {
						"type": "string",
						"default": "0",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-large-thumbnail-height"
					},
					"large_thumbnail_url": {
						"type": "string",
						"default": "",
						"source": "text",
						"selector": "div.photodata > div.largethumbnailurl"
					},
					"large_thumbnail_width": {
						"type": "string",
						"default": "0",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-large-thumbnail-width"
					},
					"link_url": {
						"type": "string",
						"default": "",
						"source": "text",
						"selector": "div.photodata > div.linkurl"
					},
					"square_thumbnail_height": {
						"type": "string",
						"default": "0",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-square-thumbnail-height"
					},
					"square_thumbnail_url": {
						"type": "string",
						"default": "",
						"source": "text",
						"selector": "div.photodata > div.squarethumbnailurl"
					},
					"square_thumbnail_width": {
						"type": "string",
						"default": "0",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-square-thumbnail-width"
					},
					"taken_time": {
						"type": "string",
						"default": "1900-01-01 00:00:00",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-taken-time"
					},
					"taken_time_epoch": {
						"type": "string",
						"default": "0",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-taken-time-epoch"
					},
					"title": {
						"type": "string",
						"default": "",
						"source": "text",
						"selector": "div.photodata > div.title"
					},
					"updated_time": {
						"type": "string",
						"default": "1900-01-01 00:00:00",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-updated-time"
					},
					"updated_time_epoch": {
						"type": "string",
						"default": "0",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-updated-time-epoch"
					},
					"uploaded_time": {
						"type": "string",
						"default": "1900-01-01 00:00:00",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-uploaded-time"
					},
					"uploaded_time_epoch": {
						"type": "string",
						"default": "0",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-uploaded-time-epoch"
					},
					"width": {
						"type": "string",
						"default": "0",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-width"
					}
				},

				migrate( old ) {
					const newAttributes = {
						embed_secret: "",
						latitude: "",
						longitude: "",
						media: "photo",
						video_height: "0",
						video_url: "",
						video_width: "0",
						...old
                	};
                	return newAttributes;
            	},

				save: function ( props ) {
					var attributes = props.attributes;

					function oldRenderImageDetails(a, editing) {
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
								'data-ptws-layout': a.layout || "fixed",
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

					if (attributes.layout == "swipe") {
						return el(
							'div',
							useBlockProps.save( { className: "rsContent" } ),
							oldRenderImageDetails(attributes, false),
							attributes.flickr_id_is_valid ? (
								el( 'a', {
										href: attributes.link_url,
										title: attributes.title
									},
									el( 'img', {
											className: 'rsImg',
											src: attributes.large_thumbnail_url,
											'data-rsh': attributes.large_thumbnail_height,
											'data-rsw': attributes.large_thumbnail_width,
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
									{ className: 'rsCaption' },
									el( 'p', {}, attributes.description )
								)
							) : undefined
						);

					} else {
						return el(
							'div',
							useBlockProps.save( {
								className: props.className,
								style: {
									flex: `${attributes.flex_ratio}`	// Force a string
								},
							} ),
							oldRenderImageDetails(attributes, false),
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
					}
				}
			},
			{
				// Old version of attributes, lacking "layout"
				attributes: {
					"flickr_id": {
						"type": "string",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-flickr-id"
					},
					"flickr_id_is_valid": {
						"type": "boolean",
						"default": false,
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-flickr-id-is-valid"
					},
					"cached_time": {
						"type": "string",
						"default": "1900-01-01 00:00:00",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-cached-time"
					},
					"cached_time_epoch": {
						"type": "string",
						"default": "0",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-cached-time-epoch"
					},
					"description": {
						"type": "string",
						"default": "",
						"source": "text",
						"selector": "div.photodata > div.description"
					},
					"flex_ratio": {
						"type": "string",
						"default": "1",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-flex-ratio"
					},
					"height": {
						"type": "string",
						"default": "0",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-height"
					},
					"id": {
						"type": "string",
						"default": "0",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-id"
					},
					"large_thumbnail_height": {
						"type": "string",
						"default": "0",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-large-thumbnail-height"
					},
					"large_thumbnail_url": {
						"type": "string",
						"default": "",
						"source": "text",
						"selector": "div.photodata > div.largethumbnailurl"
					},
					"large_thumbnail_width": {
						"type": "string",
						"default": "0",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-large-thumbnail-width"
					},
					"link_url": {
						"type": "string",
						"default": "",
						"source": "text",
						"selector": "div.photodata > div.linkurl"
					},
					"square_thumbnail_height": {
						"type": "string",
						"default": "0",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-square-thumbnail-height"
					},
					"square_thumbnail_url": {
						"type": "string",
						"default": "",
						"source": "text",
						"selector": "div.photodata > div.squarethumbnailurl"
					},
					"square_thumbnail_width": {
						"type": "string",
						"default": "0",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-square-thumbnail-width"
					},
					"taken_time": {
						"type": "string",
						"default": "1900-01-01 00:00:00",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-taken-time"
					},
					"taken_time_epoch": {
						"type": "string",
						"default": "0",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-taken-time-epoch"
					},
					"title": {
						"type": "string",
						"default": "",
						"source": "text",
						"selector": "div.photodata > div.title"
					},
					"updated_time": {
						"type": "string",
						"default": "1900-01-01 00:00:00",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-updated-time"
					},
					"updated_time_epoch": {
						"type": "string",
						"default": "0",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-updated-time-epoch"
					},
					"uploaded_time": {
						"type": "string",
						"default": "1900-01-01 00:00:00",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-uploaded-time"
					},
					"uploaded_time_epoch": {
						"type": "string",
						"default": "0",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-uploaded-time-epoch"
					},
					"width": {
						"type": "string",
						"default": "0",
						"source": "attribute",
						"selector": "div.photodata",
						"attribute": "data-ptws-width"
					}
				},

				migrate( old ) {
					const newAttributes = {
                    	layout: "fixed",
						...old
                	};
                	return newAttributes;
            	},

				// Old save operation only supports "fixed" layout.
				save: function ( props ) {
					var attributes = props.attributes;

					function oldRenderImageDetails(a, editing) {
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

					return el(
						'div',
						useBlockProps.save( {
							className: props.className,
							style: {
								flex: `${attributes.flex_ratio}`	// Force a string
							},
						} ),
						oldRenderImageDetails(attributes, false),
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
				}

			}
		]
	} );
} )();