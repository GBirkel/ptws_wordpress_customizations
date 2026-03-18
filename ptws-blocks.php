<?php

namespace Poking_Things_With_Sticks;


/**
 * Enqueue block editor JavaScript
 */
function enqueue_block_editor_assets()
{
//    $block_path = '/blocks/itinerary/block.js';
//    wp_enqueue_script(
//        'ptws-blocks-itinerary-js',
//        PTWS_PLUGIN_URL . $block_path,
//        ['wp-blocks', 'wp-i18n', 'wp-editor', 'wp-element', 'wp-components'],
//        filemtime(PTWS_PLUGIN_DIRECTORY . $block_path)
//    );
}


/**
 * Register the dynamic block.
 */
function register_dynamic_blocks()
{
    // Only load if Gutenberg is available.
    if (!function_exists('register_block_type')) { return; }

    register_block_type(PTWS_PLUGIN_DIRECTORY . '/blocks/excerpt');
    register_block_type(PTWS_PLUGIN_DIRECTORY . '/blocks/itinerary');
    register_block_type(PTWS_PLUGIN_DIRECTORY . '/blocks/dialogue');
    register_block_type(PTWS_PLUGIN_DIRECTORY . '/blocks/dialogue-line');
    register_block_type(PTWS_PLUGIN_DIRECTORY . '/blocks/disclosure');
    register_block_type(PTWS_PLUGIN_DIRECTORY . '/blocks/slides');
    register_block_type(PTWS_PLUGIN_DIRECTORY . '/blocks/slides-flickr');
    register_block_type(PTWS_PLUGIN_DIRECTORY . '/blocks/slides-stack');

}

?>