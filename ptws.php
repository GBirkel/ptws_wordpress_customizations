<?php
/*
Plugin Name: Poking Things With Sticks Extensions
Plugin URI:  http://www.mile42.net
Description: This plugin supports all the non-standard WP stuff I do on PTWS.  Among other things, it finds recent posted pictures on my Flickr feed and integrates them with recent WP posts in a fancypants way
Version:     2.25
Author:      Pokingthingswithsticks
Author URI:  http://www.pokingthingswithsticks.com
License:     GPL2
License URI: https://Icantbebothered.tolook.thisup.right.now

Written 2018 by Mile42 (email : gbirkel@gmail.com)
This is free software: you can redistribute it and/or modify
it under the terms of the GPL2 License.

It is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

Uses LazyLoad Code by the WordPress.com VIP team, TechCrunch 2011 Redesign team, and Jake Goldman (10up LLC),
which uses jQuery.sonar by Dave Artz (AOL): http://www.artzstudio.com/files/jquery-boston-2010/jquery.sonar/
*/

namespace Poking_Things_With_Sticks;

global $ptws_db_version;
$ptws_db_version = '2.25';

require_once('afgFlickr/afgFlickr.php');
include_once('ptws-libs.php');
include_once('ptws-storage.php');
require_once('ptws-api.php');
include_once('ptws-lazyload.php');
include_once('ptws-admin.php');
include_once('ptws-blocks.php');


function ptws_install()
{
    global $ptws_db_version;

    // http://php.net/manual/en/function.version-compare.php
    if (version_compare(get_option("ptws_db_version"), $ptws_db_version, '<')) {

        ptws_create_route_tables();
        ptws_create_photo_tables();
        ptws_create_comment_tables();

        update_option("ptws_db_version", $ptws_db_version);
    }
}

function ptws_update_db_check()
{
    global $ptws_db_version;
    if (version_compare(get_site_option('ptws_db_version'), $ptws_db_version, '<')) {
        ptws_install();
    }
}

/*
function add_query_vars_filter( $vars ){
   $vars[] = "ptwsdo";
   return $vars;
}
add_filter( 'query_vars', 'add_query_vars_filter' );
*/
/*
$do_var = (get_query_var('ptwsdo')) ? get_query_var('ptwsdo') : false;
if ($do_var) {
	if ($do_var == 'test') {
		echo '<div style="font-family:\'Open Sans\',sans-serif;font-size: 15px;">';
		echo 'Yup, this is a test alright.';
	    echo '</div>';
	    die();
	}
}

if (!function_exists("ptws_add_settings_link")) {
	function ptws_add_settings_link($links, $file) {
	    static $this_plugin;
	    if (!$this_plugin) $this_plugin = plugin_basename(__FILE__);
	    if ($file == $this_plugin){
	        $settings_link = '<a href="ptws-settings.php?page=general">'.__("Settings", "default").'</a>';
	        array_unshift($links, $settings_link);
	    }
	    return $links;
	}
}
*/


// Given an object describing a photo, construct HTML describing it and append
// it inside the given element.
//
// $p - An object of info about the photo, derived from a db record
// $picContainer - An HTML element to append the constructed HTML into as children
// $commentFlag - If true, append the photo's comment
//
function ptws_append_image_and_comments($p, $picContainer, $commentFlag)
{
    $objA = $picContainer->addChild('a');
    $objA->addAttribute('href', (string)$p['link_url']);
    $objA->addAttribute('title', (string)$p['title']);

    $objImg = $objA->addChild('img');
    $objImg->addAttribute('style', 'max-width:800px;');
    $objImg->addAttribute('src', (string)$p['large_thumbnail_url']);

    $wraw = floatval((int)$p['large_thumbnail_width']);
    $hraw = floatval((int)$p['large_thumbnail_height']);

    if (($hraw > 0) && ($hraw > 0)) {
        $objImg->addAttribute('data-ptws-width', (string)$wraw);
        $objImg->addAttribute('data-ptws-height', (string)$hraw);
    }

    if (!$commentFlag) { return; }

    try {
        // Handy PHP builtin to parse XML and provide an iterator
        $sxe = simplexml_load_string('<div class="imgComment"><p>' . $p['description'] . '</p></div>', 'SimpleXMLIterator');
    }
    catch(Exception $e) {
        echo ptws_html_log_error('photo description XML parsing error: ' . $e->getMessage());
    }

    // http://stackoverflow.com/questions/3418019/simplexml-append-one-tree-to-another
    $domComContainer = dom_import_simplexml($picContainer);

    $domDesc = dom_import_simplexml($sxe);
    $domDesc = $domComContainer->ownerDocument->importNode($domDesc, TRUE);

    // Append the <cat> to <c> in the dictionary
    $domComContainer->appendChild($domDesc);
}


// https://gist.github.com/Narno/4677722
// http://stackoverflow.com/questions/15830575/php-string-could-not-be-parsed-as-xml-when-using-simplexmlelement
// https://codex.wordpress.org/Shortcode_API
/*
  Problem:
		Content inside shortcodes in entries is still processed in the editor
		as though it was freehand text needing paragraph breaks.  Also, it's processed badly.
	So this perfectly valid XML inside a shortcode:
		[ptwsgallery]
			<description><p>Marking how much I need to saw off.</p></description>
		[/ptwsgallery]
	Gets turned into this when the entry is saved:
		[ptwsgallery]
			<description></p><p>Marking how much I need to saw off.</p><p></description>
		[/ptwsgallery]
	Which is clearly badly formed XML and ruins this plugin's content.  Thanks, Wordpress.
    The only available workaround is to turn off automatic paragraph insertion in all entries, sitewide.
*/


// Called when the [ptwsroute] shortcode is encountered in an entry.
//
// $atts - The attributes given inside the brackets
// $content - A string of the content between the opening and closing
//
function ptwsroute_shortcode( $atts, $content = null ) {
	$atts = shortcode_atts(
		array(
			'routeid' => ''
		), $atts, 'ptws' );
    if (!isset($atts['routeid'])) { return 'ptwsroute shortcode: missing routeid'; }
    if ($atts['routeid'] == '') { return 'ptwsroute shortcode: routeid is blank'; }
    $record_exists = ptws_get_route_record($atts['routeid']);
    if ($record_exists == null) { return 'ptwsroute shortcode: routeid "' . $atts['routeid'] . '" not in database'; }
    ptws_update_route_record_last_seen($atts['routeid']);
    $all_out = "<div class='ptws-ride-log' rideid='" . $record_exists['route_id'] . "'><div class='data'>" . $record_exists['route_json'] . "</div></div>";
    return $all_out;
}


// Called when the [ptwsgallery] shortcode is encountered in an entry.
//
// $atts - The attributes given inside the brackets
//         (photos can be specified in this way now that we're not reliant on in-place content, and thus XML.)
// $content - A string of the content between the opening and closing (which we will parse as XML)
//
function ptwsgallery_shortcode($atts, $content = null)
{
    $emit = '';
    $photos = array();
    $fixedgalleryIDs = array();
    $swipegalleryIDs = array();
    $autoplay = false;

    // Only use the attributes if there is no content inside the shortcode to parse.
    if ($content == null) {
        if (isset($atts['fixed'])) {
            $gIds = explode(',', $atts['fixed']);
            foreach ($gIds as $gId) {
                array_push($fixedgalleryIDs, $gId);
                $photos[$gId] = $gId;
            }
        } elseif (isset($atts['swipe'])) {
            $gIds = explode(',', $atts['swipe']);
            foreach ($gIds as $gId) {
                array_push($swipegalleryIDs, $gId);
                $photos[$gId] = $gId;
            }
            if (isset($atts['autoplay'])) {
                $autoplay = true;
            }
        } elseif (isset($atts['latest'])) {
            $gIds = ptws_get_latest_flickr_cache_ids(intval($atts['latest']));
            foreach ($gIds as $gId) {
                array_push($swipegalleryIDs, $gId);
                $photos[$gId] = $gId;
            }
            // The "latest" reel always autoplays
            $autoplay = true;
        }
    // Try old XML parsing method, from when we embedded all slide data in XML inside the shortcode
    } else {
        try {
            // Handy PHP builtin to parse XML and provide an iterator
            $sxe = simplexml_load_string($content, 'SimpleXMLIterator');
        } catch (Exception $e) {
            return '<p>ptwsgallery shortcode content XML parsing error: ' . $e->getMessage() . '</p>';
        }
        $sxe->rewind();
        $encloser = $sxe->getName();
        if ($encloser != 'ptwsgallery') {
            return '<p>ptwsgallery shortcode must contain a single ptwsgallery element</p>';
        }

        for (; $sxe->valid(); $sxe->next()) {
            $majorSection = $sxe->key();
            if ($majorSection == 'photos') {
                // Ignoring these sections now.
            } elseif ($majorSection == 'swipegallery') {
                if ($sxe->hasChildren()) {
                    foreach ($sxe->getChildren() as $element => $value) {
                        if ($element == 'galleryitem') {
                            if (isset($value['id'])) {
                                array_push($swipegalleryIDs, (string)$value['id']);
                                $photos[(string)$value['id']] = (string)$value['id'];
                            }
                        }
                    }
                }
            } elseif ($majorSection == 'fixedgallery') {
                if ($sxe->hasChildren()) {
                    foreach ($sxe->getChildren() as $element => $value) {
                        if ($element == 'galleryitem') {
                            if (isset($value['id'])) {
                                array_push($fixedgalleryIDs, (string)$value['id']);
                                $photos[(string)$value['id']] = (string)$value['id'];
                            }
                        }
                    }
                }
            } else {
                $emit .= '<p>Unrecognized major section ' . $majorSection . '. Must be fixedgallery, or swipegallery.</p>';
            }
        }
    }

    if ($photos) {
        //$emit .= "\n<p>Post " . get_the_ID() . ", Photo IDs found: \n";
        foreach ($photos as $pid) {
            //$emit .= $pid;
            $record_exists = ptws_get_flickr_cache_record($pid);
            if ($record_exists == null) {
                ptws_add_uncached_photo($pid);
                $photos[$pid] = ptws_get_flickr_cache_record($pid);
            } else {
                $photos[$pid] = $record_exists;
            }
            //$emit .= ", ";
        }
        //$emit .= "</p>\n";
    }
    if ($swipegalleryIDs) {
        $autoplayAttr = $autoplay ? " ptwsautoplay" : "";
        $emit .= "\n<div class='royalSlider heroSlider fullWidth rsMinW" . $autoplayAttr . "'>\n";
        foreach ($swipegalleryIDs as $pid) {
            if ($photos[$pid]['cached_time'] > 0) {
                $p = $photos[$pid];
                $emit .= "  <div class='rsContent'>\n";
                $emit .= '<a href="' . (string)$p['link_url'] . '" title="' . (string)$p['title'] . '">';
                $wraw = floatval((int)$p['large_thumbnail_width']);
                $hraw = floatval((int)$p['large_thumbnail_height']);
                $w = $wraw / ($hraw / 500.0); // Not using this since we're embedding proper thumbnail-sized dimensions
                $emit .= '<img src="' . (string)$p['large_thumbnail_url'] . '" ';
                $emit .= 'data-rsw="' . intval($wraw) . '" ';
                $emit .= 'data-rsh="' . intval($hraw) . '" ';
                $emit .= 'data-ptws-width="' . intval($wraw) . '" ';
                $emit .= 'data-ptws-height="' . intval($hraw) . '" ';

                $emit .= 'class="rsImg" />';
                $emit .= '</a>';

                if ($p['description']) {
                    if ($p['description'] != null) {
                        if ($p['description'] != '') {
                            $emit .= '<div class="rsCaption">' . $p['description'] . '</div>';
                        }
                    }
                }
                $emit .= "  </div>\n";
            }
        }
        $emit .= '</div>';
    }
    elseif ($fixedgalleryIDs) {

        $itemsInPortrait = array();
        $itemsNotInPortrait = array();
        $commentFlag = FALSE;

        foreach ($fixedgalleryIDs as $pid) {
            if ($photos[$pid]['cached_time'] > 0) {
                $p = $photos[$pid];

                $wraw = floatval((int)$p['large_thumbnail_width']);
                $hraw = floatval((int)$p['large_thumbnail_height']);

                if ($hraw > 0) {
                    if ($wraw / $hraw < 0.8) {
                        array_push($itemsInPortrait, $pid);
                    } else {
                        array_push($itemsNotInPortrait, $pid);
                    }
                } else {
                    array_push($itemsNotInPortrait, $pid);
                }
                if ($p['description']) {
                    if ($p['description'] != null) {
                        if ($p['description'] != '') {
                            $commentFlag = TRUE;
                        }
                    }
                }
            }
        }

        $fixedGalXML = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><div class="images"></div>', null, false);

        if ((count($fixedgalleryIDs) == 3) && (count($itemsInPortrait) == 1)) {
            // Append both items that are not in portrait mode to the same first div
            $picContainer = $fixedGalXML->addChild('div');
            foreach ($itemsNotInPortrait as $pid) {
                $p = $photos[$pid];
                ptws_append_image_and_comments($p, $picContainer, $commentFlag);
            }
            // Then append the one item that is in portrait mode to the second div by itself
            foreach ($itemsInPortrait as $pid) {
                $picContainer = $fixedGalXML->addChild('div');
                $p = $photos[$pid];
                ptws_append_image_and_comments($p, $picContainer, $commentFlag);
            }
        } elseif (count($fixedgalleryIDs) == 1) {
            foreach ($fixedgalleryIDs as $pid) {
                if (isset($photos[$pid])) {
                    $p = $photos[$pid];
                    $picContainer = $fixedGalXML->addChild('div');
                    ptws_append_image_and_comments($p, $picContainer, $commentFlag);
                }
            }
        } else {

            $galleryItems = array();
            foreach ($fixedgalleryIDs as $pid) {
                if (isset($photos[$pid])) {
                    array_push($galleryItems, $photos[$pid]);
                }
            }
            $imgMaxHeight = 0;
            $missingSize = false;
            foreach ($galleryItems as $p) {
                $hraw = floatval((int)$p['large_thumbnail_height']);
                $wraw = floatval((int)$p['large_thumbnail_width']);
                if (($hraw > 0) && ($wraw > 0)) {
                    if ($imgMaxHeight < $hraw) {
                        $imgMaxHeight = $hraw;
                    }
                } else {
                    $missingSize = true;
                }
            }
            $imgTotalScaledWidth = 0;
            foreach ($galleryItems as $p) {
                $hraw = floatval((int)$p['large_thumbnail_height']);
                $wraw = floatval((int)$p['large_thumbnail_width']);
                if (($hraw > 0) && ($wraw > 0)) {
                    $imgScaledWidth = ($imgMaxHeight / $hraw) * $wraw;
                    $imgTotalScaledWidth = $imgTotalScaledWidth + $imgScaledWidth;
                }
            }
            $galleryCount = floatval(count($galleryItems));
            foreach ($galleryItems as $p) {
                $picContainer = $fixedGalXML->addChild('div');

                if (($imgMaxHeight > 0) && ($imgTotalScaledWidth > 0) && ($missingSize == false)) {
                    $wraw = floatval((int)$p['large_thumbnail_width']);
                    $hraw = floatval((int)$p['large_thumbnail_height']);
                    $imgScaledWidth = ($imgMaxHeight / $hraw) * $wraw;
                    $flexProportion = ($galleryCount / $imgTotalScaledWidth) * $imgScaledWidth;
                    $picContainer->addAttribute('style', sprintf('flex:%01.4f', $flexProportion));
                }
                ptws_append_image_and_comments($p, $picContainer, $commentFlag);
            }
        }
        $emit .= $fixedGalXML->asXML() . "\n";
    }
    return $emit;
}


function ptws_enqueue_styles()
{
    wp_enqueue_style('ptws_leaflet_css', PTWS_PLUGIN_URL . "/css/leaflet.css");
    wp_enqueue_style('ptws_css', PTWS_PLUGIN_URL . "/css/ptws.css");
    wp_enqueue_style('ptws_royalsider_custom_css', PTWS_PLUGIN_URL . "/css/rscustom.css");
}


function ptws_auth_read()
{
    if (isset($_GET['frob'])) {
        global $pf;
        $auth = $pf->auth_getToken($_GET['frob']);
        update_option('afg_flickr_token', $auth['token']['_content']);
        $pf->setToken($auth['token']['_content']);
        header('Location: ' . $_SESSION['afgFlickr_auth_redirect']);
        exit;
    }
}


function ptws_enqueue_scripts()
{
    wp_enqueue_script('jquery');
    wp_enqueue_script('ptws_js', PTWS_PLUGIN_URL . '/js/ptws.js', array(
            'jquery'
        )
    );
}


function ptws_activate()
{
    add_option('ptws_plugin_activation', 'just-activated');
}

add_action('wp_print_scripts', __NAMESPACE__ . '\ptws_enqueue_scripts');
add_action('wp_print_styles', __NAMESPACE__ . '\ptws_enqueue_styles');

// TODO: Replace with block.json and a subfolder?
//require plugin_dir_path( __FILE__ ) . 'blocks/itinerary/index.php';
//add_action('plugins_loaded', __NAMESPACE__ . '\register_dynamic_blocks');
add_action('init', __NAMESPACE__ . '\register_dynamic_blocks');
add_action('enqueue_block_editor_assets', __NAMESPACE__ . '\enqueue_block_editor_assets');

add_shortcode('ptwsgallery', __NAMESPACE__ . '\ptwsgallery_shortcode');
add_shortcode('ptwsroute', __NAMESPACE__ . '\ptwsroute_shortcode');

if (!is_admin()) {
    // Turn off auto-formatting of entries, to prevent corruption of XML by the auto-processor
    // http://wordpress.stackexchange.com/questions/46894/why-is-wordpress-changing-my-html-code
    // https://wordpress.org/plugins/wpautop-control/
    remove_filter('the_content', 'wpautop');
    remove_filter('the_excerpt', 'wpautop');
    // run this later, so other content filters have run, including image_add_wh on WP.com
    add_filter('the_content', 'Poking_Things_With_Sticks_Lazyload\ptws_ll_add_image_placeholders', 9999);
} else {
    ptws_init_actions_for_admin();
    register_activation_hook(__FILE__, 'ptws_activate');
    /*    add_filter('plugin_action_links', __NAMESPACE__ . '\ptws_add_settings_link', 10, 2 );*/
}

// Initilize the API endpoints class
$ptws_api = new PTWS_API();
$ptws_api->run();

?>