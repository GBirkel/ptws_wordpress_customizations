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

    // Hook server side rendering into render callback
    //register_block_type('ptws/gallery', [
    //    'name' => 'PTWS Gallery',
    //    'description' => 'A server-side assembled gallery of images with various options.',
    //    'render_callback' => __NAMESPACE__ . '\render_dynamic_gallery_block',
    //    'category' => 'widgets',
    //]);
}


/**
 * Server rendering for dynamic block
 */
function render_dynamic_gallery_block($block)
{
    $recent_posts = wp_get_recent_posts([
        'numberposts' => 3,
        'post_status' => 'publish',
    ]);

    if (empty($recent_posts)) { return '<p>No posts</p>'; }

    $markup = '<ul>';
    foreach ($recent_posts as $post) {
        $post_id  = $post['ID'];
        $markup  .= sprintf(
            '<li><a href="%1$s">%2$s</a></li>',
            esc_url(get_permalink($post_id)),
            esc_html(get_the_title($post_id))
        );
    }
    $block_description = print_r($block, true);
    return "{$markup}</ul>{$block_description}<p>poop</p>";
}


?>