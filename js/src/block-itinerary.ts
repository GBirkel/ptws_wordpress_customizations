// https://github.com/DefinitelyTyped/DefinitelyTyped/blob/master/types/wordpress__blocks/index.d.ts
import { registerBlockType } from '@wordpress/blocks';
import { createElement as el } from '@wordpress/element';
import { RichText, MediaUpload, useBlockProps } from "@wordpress/block-editor";
import { Button } from '@wordpress/components';
import { __ } from "@wordpress/i18n";


export class BlockItinerary {

	static iconptwsgallery() {
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

	static register() {
		registerBlockType( 'gutenberg-examples/example-05-recipe-card', {
			title: __( 'Example: Recipe Card', 'gutenberg-examples' ),
			icon: {
				background: 'rgba(224, 243, 254, 0.52)',
				src: BlockItinerary.iconptwsgallery()
			},
			category: 'layout',
			attributes: {
				title: {
					type: 'array',
					source: 'children',
					selector: 'h2',
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
				ingredients: {
					type: 'array',
					source: 'children',
					selector: '.ingredients',
				},
				instructions: {
					type: 'array',
					source: 'children',
					selector: '.steps',
				},
			},

			example: {
				attributes: {
					title: __( 'Chocolate Chip Cookies', 'gutenberg-examples' ),
					mediaID: 1,
					mediaURL:
						'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f1/2ChocolateChipCookies.jpg/320px-2ChocolateChipCookies.jpg',
					ingredients: [
						{ type: 'li', props: { children: [ 'flour' ] } },
						{ type: 'li', props: { children: [ 'sugar' ] } },
						{ type: 'li', props: { children: [ 'chocolate' ] } },
						{ type: 'li', props: { children: [ '💖' ] } },
					],
					instructions: [
						__( 'Mix, Bake, Enjoy!', 'gutenberg-examples' ),
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
					el( RichText, {
						tagName: 'h2',

						placeholder: __(
							'Write Recipe title…',
							'gutenberg-examples'
						),
						value: (<any>attributes.title),
						onChange: function ( value ) {
							props.setAttributes( { title: value } );
						},
					} ),
					el(
						'div',
						{ className: 'recipe-image' },
						el( MediaUpload, {
							onSelect: onSelectImage,
							allowedTypes: ['image'],
							value: (<any>attributes.mediaID),
							render: function ( obj ) {
								return el(
									Button,
									{
										className: attributes.mediaID
											? 'image-button'
											: 'button button-large',
										onClick: obj.open,
									},
									! attributes.mediaID
										? __( 'Upload Image', 'gutenberg-examples' )
										: el( 'img', { src: attributes.mediaURL } )
								);
							},
						} )
					),
					el( 'h3', {}, __( 'Ingredients', 'gutenberg-examples' ) ),
					el( RichText, {
						tagName: 'ul',
						multiline: 'li',
						placeholder: __(
							'Write a list of ingredients…',
							'gutenberg-examples'
						),
						value: (<any>attributes.ingredients),
						onChange: function ( value ) {
							props.setAttributes( { ingredients: value } );
						},
						className: 'ingredients',
					} ),
					el( 'h3', {}, __( 'Instructions', 'gutenberg-examples' ) ),
					el( RichText, {
						tagName: 'div',
						placeholder: __(
							'Write instructions…',
							'gutenberg-examples'
						),
						value: (<any>attributes.instructions),
						onChange: function ( value ) {
							props.setAttributes( { instructions: value } );
						},
					} )
				);
			},
			save: function ( props ) {
				var attributes = props.attributes;

				return el(
					'div',
//					useBlockProps.save( { className: props.className } ),
					useBlockProps.save( { className: 'recipe' } ),
					el( RichText.Content, {
						tagName: 'h2',
						value: (<any>attributes.title),
					} ),
					attributes.mediaURL &&
						el(
							'div',
							{ className: 'recipe-image' },
							el( 'img', { src: attributes.mediaURL } )
						),
					el( 'h3', {}, __( 'Ingredients', 'gutenberg-examples' ) ),
					el( RichText.Content, {
						tagName: 'ul',
						className: 'ingredients',
						value: (<any>attributes.ingredients),
					} ),
					el( 'h3', {}, __( 'Instructions', 'gutenberg-examples' ) ),
					el( RichText.Content, {
						tagName: 'div',
						className: 'steps',
						value: (<any>attributes.instructions),
					} )
				);
			},
		} );
	}
}

