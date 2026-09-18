// PTWS Block: GPS Route
// Client-side Javascript portion

( function () {

	var apiFetch = window.wp.apiFetch;

	var dataDispatch = window.wp.data.dispatch;
	var dataSelect = window.wp.data.select;

	var createBlock = window.wp.blocks.createBlock;

	var PlainText = window.wp.blockEditor.PlainText;
    var useBlockProps = window.wp.blockEditor.useBlockProps;

	var el = window.wp.element.createElement;
	var useEffect = window.wp.element.useEffect;
	var useState = window.wp.element.useState;

	var wpUrl = window.wp.url;


	function iconptwsroute() {
		return el(
			'svg', 
			{ width: '20px', height: '20px', viewBox: '0 0 24 24', xmlns: 'http://www.w3.org/2000/svg' },
			el('path', { d: 'M16.375 4.5H4.625a.125.125 0 0 0-.125.125v8.254l2.859-1.54a.75.75 0 0 1 .68-.016l2.384 1.142 2.89-2.074a.75.75 0 0 1 .874 0l2.313 1.66V4.625a.125.125 0 0 0-.125-.125Zm.125 9.398-2.75-1.975-2.813 2.02a.75.75 0 0 1-.76.067l-2.444-1.17L4.5 14.583v1.792c0 .069.056.125.125.125h11.75a.125.125 0 0 0 .125-.125v-2.477ZM4.625 3C3.728 3 3 3.728 3 4.625v11.75C3 17.273 3.728 18 4.625 18h11.75c.898 0 1.625-.727 1.625-1.625V4.625C18 3.728 17.273 3 16.375 3H4.625ZM20 8v11c0 .69-.31 1-.999 1H6v1.5h13.001c1.52 0 2.499-.982 2.499-2.5V8H20Z',
						 fillRule: "evenodd",
						 clipRule: "evenodd"})
		); 
	}

	window.wp.blocks.registerBlockType( 'ptws/route', {
		icon: {
			background: 'rgba(224, 243, 254, 0.52)',
			src: iconptwsroute()
		},

		edit: function ( props ) {

			const [idInputValue, setIdInputValue] = useState(props.attributes.route_id);
			const [idIsValid, setIdIsValid] = useState(false);
			const [routeRecord, setRouteRecord] = useState(null);

			const [inputDebounceTimer, setInputDebounceTimer] = useState(null);

			var attributes = props.attributes;

			// Attempt to resolve the initial ID that's provided with a
			// pre-populated route block. (Probably auto-generated during a paste operation.)
			async function processInitialId(idString) {
				const record = await resolveRoute(idString);
				createRoute(record);
			}


			// useEffect with no arguments to run an initial check of the initial_id attribute.
			useEffect(() => {
				if (attributes.initial_id.trim() != "") {
					processInitialId(attributes.initial_id);
					props.setAttributes( { initial_id: "" } );
				}
			}, []);


            async function resolveRoute(idString) {
                const idStringTrimmed = idString.trim();
				setInputDebounceTimer(null);

				if (idStringTrimmed.length < 1) {
					setIdIsValid(false);
					setRouteRecord(null);
					return null;
				}

                if ( this.fetching ) { return null; }
                this.fetching = true;
				var record = null;

				const postId = dataSelect("core/editor").getCurrentPostId();

				const fetch = new Promise((resolve, reject) => {
					apiFetch({
						path: wpUrl.addQueryArgs( '/ptws/v1/route/id', { id: idStringTrimmed, last_seen_in_post: postId } )
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

                this.fetching = false;

				try {
					record = await fetch;
				} catch (err) {
					console.log("Await error");
					console.error(err);
					setIdIsValid(false);
					setRouteRecord(null);
					return null;
				}

				// If we get this far, the record and Id are valid.
				setRouteRecord(record);
				setIdIsValid(true);
				return record;
            }


            function createRoute(record) {
				if (!record) { return; }

				props.setAttributes( {
					initial_id: "",
					route_id: record.route_id,
					is_valid: true,
					route_start_time: record.route_start_time,
					route_start_time_epoch: record.route_start_time_epoch,
					route_end_time: record.route_end_time,
					route_end_time_epoch: record.route_end_time_epoch,
					route_duration_seconds: record.route_duration_seconds || "0",
					route_distance_meters: record.route_distance_meters || "0",
					route_json: record.route_json,
					description: record.route_description
				} );
			}


			// This handles keyboard-based actions in the input field.
			// Changes to the input value are handled in inputOnChange.
			function inputOnKeyDown(event) {
				if (event.key == "Enter") {
					event.preventDefault();
					if (idIsValid) {
						createRoute(routeRecord);
						setIdsInputValue("");
						setIdIsValid(false);
						setRouteRecord(null);
					}
				// If the user hits delete AND the text area is empty,
				// remove this block.
				} else if (event.key == "Delete") {
					if (idInputValue === "") {
						dataDispatch( 'core/block-editor' ).removeBlock( props.clientId );
					}
				}
			}


			function onIdInputChange(value) {
				if (inputDebounceTimer) { clearTimeout(inputDebounceTimer); }
				newInputDebounceTimer = (setTimeout(() => resolveRoute(value), 300));
				setInputDebounceTimer(newInputDebounceTimer)
			}

            return el( 'div',
					useBlockProps( {
						className: "editing",
						'data-ptws-initial-id': attributes.initial_id,
						'data-ptws-route-id': attributes.route_id,
						'data-ptws-is-valid': attributes.is_valid,
						'data-ptws-route-start-time': attributes.route_start_time,
						'data-ptws-route-start-time-epoch': attributes.route_start_time_epoch,
						'data-ptws-route-end-time': attributes.route_end_time,
						'data-ptws-route-end-time-epoch': attributes.route_end_time_epoch,
						'data-ptws-route-duration-seconds': attributes.route_duration_seconds,
						'data-ptws-route-distance-meters': attributes.route_distance_meters
					} ),
					// Using "code" is required so Wordpress doesn't "educate" the quotes in the JSON when saving the page.
					el( 'code', { className: 'route-json' }, attributes.route_json || "{}"),
					el( 'div', { className: 'description' }, attributes.description),
					el( 'div', { className: 'editor-information' },
						attributes.is_valid ? ("GPS Recording ID " + attributes.route_id) : "GPS Recording ID Not Valid"
					),
					el( 'div', { className: 'route-ui-container' }),
					el( 'div',
						{ className: "route-id-zone" },
						el( "div", { className: "parse-status" },
							idIsValid ? "\u2714" : "\u2718"
						),
						el( PlainText, {
							tagName: 'div',
							placeholder: 'Route ID',
							value: idInputValue,
							onKeyDown: inputOnKeyDown,
							onChange: function ( value ) {
								setIdInputValue(value);
								onIdInputChange(value);
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
							'data-ptws-initial-id': attributes.initial_id,
							'data-ptws-route-id': attributes.route_id,
							'data-ptws-is-valid': attributes.is_valid,
							'data-ptws-route-start-time': attributes.route_start_time,
							'data-ptws-route-start-time-epoch': attributes.route_start_time_epoch,
							'data-ptws-route-end-time': attributes.route_end_time,
							'data-ptws-route-end-time-epoch': attributes.route_end_time_epoch,
							'data-ptws-route-duration-seconds': attributes.route_duration_seconds,
							'data-ptws-route-distance-meters': attributes.route_distance_meters
						} ),
						el( 'code', { className: 'route-json' }, attributes.route_json || "{}"),
						el( 'div', { className: 'description' }, attributes.description),
						el( 'div', { className: 'editor-information' },
							attributes.is_valid ? ("GPS Recording ID " + attributes.route_id) : "GPS Recording ID Not Valid"
						),
						el( 'div', { className: 'route-ui-container' })
					);
        },

		transforms: {
			from: [
				{	type: 'block',
					blocks: ['core/shortcode'],
					// Example shortcode we're looking for here:
					// [ptwsroute routeid="2011-10-20T18:00:00+00:00"]
					isMatch: (attributes) => {
						const name = attributes?.text?.match(/\s*\[ptwsroute\s+/);
						// Need one or the other of these
						const routeid = attributes?.text?.match(/\s+routeid="[A-Za-z\d\-\:\+T]+"/);
						return (name && routeid);
					},
					transform: (attributes, b, c) => {
						const routeid = attributes?.text?.match(/\s+routeid="([A-Za-z\d\-\:\+T]+)"/);
						const newAttributes = {
								initial_id: routeid ? routeid[1] : "",
								route_id: "",
								is_valid: false,
								description: "",
								route_json: "",
								route_start_time: "",
								route_start_time_epoch: "0",
								route_end_time: "",
								route_end_time_epoch: "0",
								route_duration_seconds: "0",
								route_distance_meters: "0"
							};
						return createBlock( 'ptws/route', newAttributes );	
					}
				},
				{	type: 'shortcode',	// WPShortcodeMatch
					tag: 'ptwsroute',
					transform: (attributes, shortcodeMatch) => {
						// Structure of "shortcodeMatch":
						// { index: number,
						//   content: string (full match including shortcode enclosure)
						//   shortcode: {
						//		content: string (full match without shortcode enclosure)
						//		tag: string (name of shortcode)
						//		type: string (not sure? set to "closed" in my samples)
						//		attrs: {
						//		  named: { } Ostensibly key-value pairs
						//		  numeric: [] Ostensibly in order enocuntered
						//		}
						//   }
						// }
						// First look for photo IDs in the attributes
						var id = attributes?.named?.routeid;
						const newAttributes = {
								initial_id: id,
								route_id: "",
								is_valid: false,
								description: "",
								route_json: "",
								route_start_time: "",
								route_start_time_epoch: "0",
								route_end_time: "",
								route_end_time_epoch: "0",
								route_duration_seconds: "0",
								route_distance_meters: "0"
							};
						return createBlock( 'ptws/route', newAttributes );	
					}
				}
			]
		}
	} );
} )();