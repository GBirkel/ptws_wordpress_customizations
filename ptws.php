<?php
/*
Plugin Name: Poking Things With Sticks Extensions
Plugin URI:  http://www.pokingthingswithsticks.com
Description: This plugin supports all the non-standard WP stuff I do on PTWS.  Among other things, it finds recent posted pictures on my Flickr feed and integrates them with recent WP posts in a fancypants way
Version:     1.7
Author:      Pokingthingswithsticks
Author URI:  http://www.pokingthingswithsticks.com
License:     MIT
License URI: https://Icantbebothered.tolook.thisup.right.now

Copyright 2018 Mile42 (email : gbirkel@gmail.com)
This is free software: you can redistribute it and/or modify
it under the terms of the MIT License.
 
It is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

global $ptws_db_version;
$ptws_db_version = '1.7';

include_once('ptws-libs.php');
require_once('afgFlickr/afgFlickr.php');


function ptws_activate() {
    add_option( 'ptws_plugin_activation', 'just-activated' );
}


function ptws_install() {
    global $wpdb;
    global $ptws_db_version;

    $charset_collate = $wpdb->get_charset_collate();

    // http://php.net/manual/en/function.version-compare.php
    if (version_compare( get_option( "ptws_db_version" ), $ptws_db_version, '<' )) {

        $table_name = $wpdb->prefix . 'ptwsflickrcache';

        // last_seen_in_post references a field in the Wordpress posts table
        // https://codex.wordpress.org/Database_Description#Table:_wp_posts
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            flickr_id varchar(32) NOT NULL,
            title text,
            width int UNSIGNED,
            height int UNSIGNED,
            link_url text,
            large_thumbnail_width int UNSIGNED,
            large_thumbnail_height int UNSIGNED,
            large_thumbnail_url text,
            square_thumbnail_width int UNSIGNED,
            square_thumbnail_height int UNSIGNED,
            square_thumbnail_url text,
            comments int UNSIGNED DEFAULT 0 NOT NULL,
            description text,
            taken_time datetime DEFAULT 0 NOT NULL,
            uploaded_time datetime DEFAULT 0 NOT NULL,
            updated_time datetime DEFAULT 0 NOT NULL,
            cached_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            auto_placed tinyint(1) DEFAULT 0,
            last_seen_in_post bigint(20) unsigned,
            CONSTRAINT unique_flickr_id UNIQUE (flickr_id),
            PRIMARY KEY (id)
        ) $charset_collate;";

        if (!function_exists('dbDelta')) {
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        }
        dbDelta( $sql );

        update_option( "ptws_db_version", $ptws_db_version );
    }
}

/*
function ptws_update_db_check() {
    global $ptws_db_version;
    if (version_compare( get_site_option( 'ptws_db_version' ), $ptws_db_version, '<' )) {
        ptws_install();
    }
}
*/

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
function ptws_append_image_and_comments($p, $picContainer, $commentFlag) {

    $objA = $picContainer->addChild('a');
    $objA->addAttribute('href', (string)$p['link_url']);
    $objA->addAttribute('title', (string)$p['title']);

    $objImg = $objA->addChild('img');
    $objImg->addAttribute('style', 'max-width:800px;');
    $objImg->addAttribute('src', (string)$p['large_thumbnail_url']);

    if (!$commentFlag) { return; }

    try {
        // Handy PHP builtin to parse XML and provide an iterator
        $sxe = simplexml_load_string('<div class="imgComment"><p>' . $p['description'] . '</p></div>', 'SimpleXMLIterator');
    }
    catch(Exception $e) {
        echo ptws_error('photo description XML parsing error: ' . $e->getMessage());
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


// Called when the [ptwsgallery] shortcode is encountered in an entry.
//
// $atts - The attributes given inside the brackets (none in our case)
// $content - A string of the content between the opening and closing (which we will parse as XML)
//
function ptwsgallery_shortcode( $atts, $content = null ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ptwsflickrcache';

	if ($content == null) {
		return '';
	}
	try {
        // Handy PHP builtin to parse XML and provide an iterator
    	$sxe = simplexml_load_string($content, 'SimpleXMLIterator');
	}
	catch(Exception $e) {
    	return '<p>ptwsgallery shortcode content XML parsing error: ' . $e->getMessage() . '</p>';
	}
    $sxe->rewind();
    $encloser = $sxe->getName();
    if ($encloser != 'ptwsgallery') {
        return '<p>ptwsgallery shortcode must contain a single ptwsgallery element</p>';
    }

    $emit = '';
    $photos = array();
    $fixedgalleryIDs = array();
    $swipegalleryIDs = array();

    for (; $sxe->valid(); $sxe->next()) {
        $majorSection = $sxe->key();
        if ($majorSection == 'photos') {
            // Ignoring these sections now.
        } elseif ($majorSection == 'swipegallery') {
            if ($sxe->hasChildren()) {
                foreach ($sxe->getChildren() as $element=>$value) {
                    if ($element == 'galleryitem') {
                        if (isset($value['id'])) {
                            array_push($swipegalleryIDs, (string)$value['id']);
                            $photos[(string)$value['id']] = $value;
                        }
                    }
                }
            }
        } elseif ($majorSection == 'fixedgallery') {
            if ($sxe->hasChildren()) {
                foreach ($sxe->getChildren() as $element=>$value) {
                    if ($element == 'galleryitem') {
                        if (isset($value['id'])) {
                            array_push($fixedgalleryIDs, (string)$value['id']);
                            $photos[(string)$value['id']] = $value;
                        }
                    }
                }
            }
        } else {
            $emit .= '<p>Unrecognized major section ' . $majorSection . '. Must be fixedgallery, or swipegallery.</p>';
        }
    }
    if ($photos) {
        //$emit .= "\n<p>Post " . get_the_ID() . ", Photo IDs found: \n";
        foreach($photos as $pid=>$element) {
            //$emit .= $pid;
            $record_exists = ptws_get_flickr_cache_record($pid);
            if ($record_exists == null) {
                $wpdb->show_errors();
                $wpdb->insert(
                    $table_name,
                    array(
                        'flickr_id' => $pid,
                        'cached_time' => 0,
                        'last_seen_in_post' => get_the_ID()
                    ),
                    array( 
                        '%s', 
                        '%d',
                        '%s'
                    ) 
                );
                $wpdb->hide_errors();
                //$emit .= "(a)";
                $photos[$pid] = ptws_get_flickr_cache_record($pid);
            } else {
                $photos[$pid] = $record_exists;
            }
            //$emit .= ", ";
        }
        //$emit .= "</p>\n";
    }

    if ($swipegalleryIDs) {
        $emit .= "\n<div class='royalSlider heroSlider fullWidth rsMinW'>\n";
        foreach($swipegalleryIDs as $pid) {
            if ($photos[$pid]['cached_time'] > 0) {
                $p = $photos[$pid];
                $emit .= "  <div class='rsContent'>\n";
                $emit .= '<a href="' . (string)$p['link_url'] . '" title="' . (string)$p['title'] . '">';
                $wraw = floatval((int)$p['large_thumbnail_width']);
                $hraw = floatval((int)$p['large_thumbnail_height']);
                $w = $wraw / ($hraw / 500.0); // Not using this since we're embedding proper thumbnail-sized dimensions
                $emit .= '<img src="' . (string)$p['large_thumbnail_url'] . '" data-rsw="' . intval($wraw) . '" data-rsh="' . intval($hraw) . '" class="rsImg" />';
                $emit .= '</a>';

                if ($p['description']) {
                    if ($p['description'] != null) {
                        if ($p['description'] != '') {
                            $emit .= '<div class="rsCaption">' . $description . '</div>';
                        }
                    }
                }
                $emit .= "  </div>\n";
            }
        }
        $emit .= '</div>';
    }
    if ($fixedgalleryIDs) {

        $itemsInPortrait = array();
        $itemsNotInPortrait = array();
        $commentFlag = FALSE;

        foreach($fixedgalleryIDs as $pid) {
            if ($photos[$pid]['cached_time'] > 0) {
                $p = $photos[$pid];

                $wraw = floatval((int)$p['large_thumbnail_width']);
                $hraw = floatval((int)$p['large_thumbnail_height']);

                if ($hraw > 0) {
                    if ($wraw/$hraw < 0.8) {
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

        $fixedGalXML = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><div class="images"></div>', null, false);

        if ((count($fixedgalleryIDs) == 3) && (count($itemsInPortrait) == 1)) {
            // Append both items that are not in portrait mode to the same first div
            $picContainer = $fixedGalXML->addChild('div');
            foreach($itemsNotInPortrait as $pid) {
                $p = $photos[$pid];
                ptws_append_image_and_comments($p, $picContainer, $commentFlag);
            }
            // Then append the one item that is in portrait mode to the second div by itself
            foreach($itemsInPortrait as $pid) {
                $picContainer = $fixedGalXML->addChild('div');
                $p = $photos[$pid];
                ptws_append_image_and_comments($p, $picContainer, $commentFlag);
            }
        } else {
            foreach($fixedgalleryIDs as $pid) {
                if (isset($photos[$pid])) {
                    $p = $photos[$pid];
                    $picContainer = $fixedGalXML->addChild('div');
                    ptws_append_image_and_comments($p, $picContainer, $commentFlag);
                }
            }
        }
        $emit .= $fixedGalXML->asXML() . "\n";
    }
	return $emit;
}


add_shortcode( 'ptwsgallery', 'ptwsgallery_shortcode' );


// Given the Flickr ID of a photo, seek its record in the database, and return it.
// If no record exists, return null instead.
//
function ptws_get_flickr_cache_record($pid) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ptwsflickrcache';
    $one_row = $wpdb->get_row(
        $wpdb->prepare( 
            "
                SELECT * 
                FROM $table_name 
                WHERE flickr_id = %s
            ", 
            $pid
        ),
        'ARRAY_A'
    );
    if ($one_row == null) {
        return null;
    }
    // Use PHP to make epoch conversions since SQL may not properly handle negative epochs.
    // https://www.epochconverter.com/programming/php
    // https://www.epochconverter.com/programming/mysql
    $one_row['taken_time_epoch'] = strtotime($one_row['taken_time']);
    $one_row['uploaded_time_epoch'] = strtotime($one_row['uploaded_time']);
    $one_row['updated_time_epoch'] = strtotime($one_row['updated_time']);
    $one_row['cached_time_epoch'] = strtotime($one_row['cached_time']);
    return $one_row;
}


function ptws_enqueue_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('ptws_script', PTWS_PLUGIN_URL . "/js/ptws.js" , array('jquery'));
}


function ptws_enqueue_styles() {
    wp_enqueue_style('ptws_css', PTWS_PLUGIN_URL . "/css/ptws.css");
}


function ptws_admin_init() {

    if (is_admin() && get_option( 'ptws_plugin_activation' ) == 'just-activated' ) {
        delete_option( 'ptws_plugin_activation' );
        ptws_install();
    }

    ptws_create_afgFlickr_obj();
    register_setting('ptws_settings_group', 'ptws_api_key');
    register_setting('ptws_settings_group', 'ptws_api_secret');
    register_setting('ptws_settings_group', 'ptws_user_id');
    register_setting('ptws_settings_group', 'ptws_flickr_token');
    // Get afgFlickr auth token
    ptws_auth_read();
}


function ptws_admin_menu() {
    add_menu_page('PTWS Custom', 'PTWS Custom', 'publish_pages', 'ptws_plugin_page', 'ptws_admin_html_page', PTWS_PLUGIN_URL . "/images/ptws_logo.png", 898);

    // adds "Settings" link to the plugin action page
/*    add_filter( 'plugin_action_links', 'ptws_add_settings_links', 10, 2);*/

/*    ptws_setup_options();*/
}


function ptws_auth_init() {
    session_start();
    global $pf;
    unset($_SESSION['afgFlickr_auth_token']);
    $pf->setToken('');
    $pf->auth('read', $_SERVER['HTTP_REFERER']);
    exit;
}


function ptws_auth_read() {
    if ( isset($_GET['frob']) ) {
        global $pf;
        $auth = $pf->auth_getToken($_GET['frob']);
        update_option('afg_flickr_token', $auth['token']['_content']);
        $pf->setToken($auth['token']['_content']);
        header('Location: ' . $_SESSION['afgFlickr_auth_redirect']);
        exit;
    }
}


function ptws_admin_html_page() {
    global $pf;

	if ($_POST) {
	    global $pf, $custom_size_err_msg;

        if (isset($_POST['submit']) && $_POST['submit'] == 'Save Changes') {
            update_option('ptws_api_key', $_POST['ptws_api_key']);
            if (!$_POST['ptws_api_secret'] || $_POST['ptws_api_secret'] != get_option('ptws_api_secret')) {
                update_option('ptws_flickr_token', '');
            }
            update_option('ptws_api_secret', $_POST['ptws_api_secret']);
            update_option('ptws_user_id', $_POST['ptws_user_id']);

            echo "<div class='updated'>
            	<p>
            		<strong>
            			Settings updated successfully.
            			<br /><br />
            			<font style='color:red'>
            				Important Note:
            			</font>
            			If you have installed a caching plugin (like WP Super Cache or W3 Total Cache etc.),
            			you may have to delete your cached pages for the settings to take effect.
            		</strong>
            	</p>
            </div>";
            if (get_option('ptws_api_secret') && !get_option('ptws_flickr_token')) {
                echo "<div class='updated'><p><strong>Click \"Grant Access\" button to authorize Awesome Flickr Gallery to access your private photos from Flickr.</strong></p></div>";
            }
        }
        ptws_create_afgFlickr_obj();
    }
    $url=$_SERVER['REQUEST_URI'];
?>

    <form method='post' action='<?php echo $url ?>'>
		<div id='afg-wrap'>
	        <h2>PTWS Custom Settings</h2>
            <div id="afg-main-box">
            	<h3>Flickr User Settings</h3>
                <table class='widefat afg-settings-box'>
                    <tr>
                        <th class="afg-label"></th>
                        <th class="afg-input"></th>
                        <th class="afg-help-bubble"></th>
                    </tr>
                    <tr>
                    	<td>Flickr User ID</td>
                    	<td><input class='afg-input' type='text' name='ptws_user_id' value="<?php echo get_option('ptws_user_id'); ?>" /></td>
                    	<td><div>Don't know your Flickr User ID?  Get it from <a href="http://idgettr.com/" target='blank'>here.</a></div></td>
                    </tr>
                    <tr>
                    	<td>Flickr API Key</td>
                    	<td><input class='afg-input' type='text' name='ptws_api_key' value="<?php echo get_option('ptws_api_key'); ?>" /></td>
                    	<td>
                    		<div class='afg-help'>
                    			Don't have a Flickr API Key?  Get it from <a href="http://www.flickr.com/services/api/keys/" target='blank'>here.</a>
                    			Go through the <a href='http://www.flickr.com/services/api/tos/'>Flickr API Terms of Service.</a>
                    		</div>
                    	</td>
                    </tr>
                    <tr>
                        <td>Flickr API Secret</td>
                        <td>
                        	<input class='afg-input' type='text' name='ptws_api_secret' id='ptws_api_secret' value="<?php echo get_option('ptws_api_secret'); ?>"/>
                    		<br /><br />
<?php
							if (get_option('ptws_api_secret')) {
    							if (get_option('ptws_flickr_token')) {
    								echo "<input type='button' class='button-secondary' value='Access Granted' disabled='' />";
    							} else {
?>
    								<input type="button" class="button-primary"
    									value="Grant Access"
    									onClick="document.location.href='<?php echo get_admin_url() .  'admin-ajax.php?action=ptws_gallery_auth'; ?>';" />
<?php
								}
							} else {
								echo "<input type='button' class='button-secondary' value='Grant Access' disabled='' />";
							}
?>
                        </td>
                        <td>
                        	<b>ONLY</b> If you want to include your <b>Private Photos</b> in your galleries, enter your Flickr API Secret here and click Save Changes.
                        </td>
                    </tr>
    			</table>
                <br />
                <input type="submit" name="submit" id="ptws_save_changes" class="button-primary" value="Save Changes" />
                <br />
                <br />
				<input type="button" class="button-secondary"
					value="Test Flickr Credentials"
					onClick="document.location.href='<?php echo get_admin_url() .  'admin-ajax.php?action=ptws_test'; ?>';" />
<?php

    global $wpdb;
    $table_name = $wpdb->prefix . 'ptwsflickrcache';

    $wpdb->show_errors();
    $cache_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
    $cache_unresolved_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE cached_time = 0");
    echo "<p>Flickr cache contains {$cache_count} entries, with {$cache_unresolved_count} unresolved.</p>";

    if ($cache_count > 0) {
?>
                <input type="button" class="button-secondary"
                    value="Clear All Cache"
                    onClick="document.location.href='<?php echo get_admin_url() .  'admin-ajax.php?action=ptws_clear'; ?>';" />
<?php
    }

    if ($cache_unresolved_count > 0) {
?>
                <input type="button" class="button-secondary"
                    value="Resolve Entries"
                    onClick="document.location.href='<?php echo get_admin_url() .  'admin-ajax.php?action=ptws_resolve'; ?>';" />
<?php
    }
?>

			</div>
		</div>
    </form>

<?php

    //$result = $wpdb->get_results('SELECT * FROM ' . $table_name . ' LIMIT 10');

    // https://codex.wordpress.org/Database_Description#Table:_wp_posts
    //$ten_posts = $wpdb->get_results('SELECT * FROM ' . $wpdb->posts . ' LIMIT 10');
    // post_modified, post_modified_gmt DATETIME

    $wpdb->hide_errors();
}



// Gets 5 of the most recent public photos in the stream and displays their thumbnails.
// Uses $pf, a global variable providing access to the afgFlickr flickr library (see ptws-libs).
// Responds to the "ptws_test" AJAX call, e.g. ".../admin-ajax.php?action=ptws_test".
//
function ptws_flickr_connect_test() {
    session_start();
    global $pf;

    echo '<h3>Your Photostream Preview</h3>';

    if (get_option('ptws_flickr_token')) {
    	$rsp_obj = $pf->people_getPhotos(get_option('ptws_user_id'), array('per_page' => 5, 'page' => 1));
    } else {
    	$rsp_obj = $pf->people_getPublicPhotos(get_option('ptws_user_id'), NULL, NULL, 5, 1);
    }
    if (!$rsp_obj) {
    	echo ptws_error('Flickr connectivity error');
    } else {
?>
        <table style='border-spacing:0;border:1px solid #e5e5e5;box-shadow: 0 1px 1px rgba(0, 0, 0, .04)'>
            <tr>
                <th style='text-align: left;line-height: 1.3em;font-size: 14px;padding:10px;'>If your Flickr Settings are correct, 5 of your recent photos from your Flickr photostream should appear here.</th>
            </tr>
            <tr>
                <td style='padding: 8px 10px;color: #555;'>
<?php

        foreach($rsp_obj['photos']['photo'] as $photo) {
            $photo_url = "http://farm{$photo['farm']}.static.flickr.com/{$photo['server']}/{$photo['id']}_{$photo['secret']}_s.jpg";
            echo "<img src=\"$photo_url\" />&nbsp;&nbsp;&nbsp;";
        }

?>
                    <br />
                    <span style="margin-top:15px">
                        Note:  This preview is based on the Flickr Settings only.  Gallery Settings
                        have no effect on this preview.  You will need to insert gallery code to a post
                        or page to actually see the Gallery.
                    </span>
                </td>
            </tr>
        </table>
<?php

    }
    exit;
}


// Finds up to four images in the photo cache that have not been fetched from Flickr,
// and uses the Flickr library via $pf to resolve them.
// Responds to the "cache_resolve" AJAX call, e.g. ".../admin-ajax.php?action=cache_resolve".
//
function ptws_admin_cache_resolve() {
    session_start();
    global $pf;
    global $wpdb;
    $table_name = $wpdb->prefix . 'ptwsflickrcache';
    $wpdb->show_errors();

    echo '<h3>Manually resolve cache entries</h3>';

    $uncached_recs = $wpdb->get_results(
        $wpdb->prepare( 
            "SELECT * FROM $table_name WHERE cached_time = %d LIMIT 4", 
            0
        ),
        'ARRAY_A'
    );

    $uid = get_option('ptws_user_id');

    if ($uncached_recs) {
        foreach ($uncached_recs as $uncached_rec) {

            $rid = $uncached_rec['id'];
            $fid = $uncached_rec['flickr_id'];

            $f_info_obj = $pf->photos_getInfo($fid);
            $f_sizes_obj = $pf->photos_getSizes($fid);

            if (!$f_info_obj || !$f_sizes_obj) {
                echo ptws_error('Flickr connectivity error getting photo ' . $fid);
            } else {
                $f_sizes = array();
                foreach ($f_sizes_obj as $a=>$b) {
                    $f_sizes[$b['label']] = $b;
                }

                $p = $f_info_obj['photo'];
                echo '<ul><li>id ' . $rid . '</li>';
                echo '<li>flicker_id ' . $fid . '</li>';
                echo '<li>title ' . $p['title']['_content'] . '</li>';
                echo '<li>width ' . $f_sizes['Original']['width'] . '</li>';                
                echo '<li>height ' . $f_sizes['Original']['height'] . '</li>';                
                $url = 'https://www.flickr.com/photos/' . $uid . '/' . $fid . '/';
                echo '<li>link_url ' . $url . '</li>';

                echo '<li>large_thumbnail_width ' . $f_sizes['Large']['width'] . '</li>';
                echo '<li>large_thumbnail_height ' . $f_sizes['Large']['height'] . '</li>';
                echo '<li>large_thumbnail_url ' . $f_sizes['Large']['source'] . '</li>';

                echo '<li>square_thumbnail_width ' . $f_sizes['Square']['width'] . '</li>';
                echo '<li>square_thumbnail_height ' . $f_sizes['Square']['height'] . '</li>';
                echo '<li>square_thumbnail_url ' . $f_sizes['Square']['source'] . '</li>';

                $upl_time = ptws_epoch_to_str($p['dateuploaded']);
                $upd_time = ptws_epoch_to_str($p['dates']['lastupdate']);

                echo '<li>comments ' . $p['comments']['_content'] . '</li>';
                echo '<li>description ' . $p['description']['_content'] . '</li>';
                echo '<li>taken_time ' . $p['dates']['taken'] . '</li>';
                echo '<li>uploaded_time ' . $upl_time . '</li>';
                echo '<li>updated_time ' . $upd_time . '</li>';
                echo '<li>old cached_time ' . $uncached_rec['cached_time'] . '</li>';
                echo '</ul>';

                $large_w = intval($f_sizes['Large']['width']);
                $large_h = intval($f_sizes['Large']['height']);
                $large_src = $f_sizes['Large']['source'];
                if ($large_w == 0 || $large_h == 0) {
                    $large_w = intval($f_sizes['Original']['width']);
                    $large_h = intval($f_sizes['Original']['height']);
                    $large_src = $f_sizes['Original']['source'];
                }

                $wpdb->replace(
                    $table_name,
                    array(
                        'flickr_id'     => $fid,
                        'title'         => $p['title']['_content'],
                        'width'     => intval($f_sizes['Original']['width']),
                        'height'     => intval($f_sizes['Original']['height']),
                        'link_url'     => $url,
                        'large_thumbnail_width'     => $large_w,
                        'large_thumbnail_height'     => $large_h,
                        'large_thumbnail_url'     => $large_src,
                        'square_thumbnail_width'     => intval($f_sizes['Square']['width']),
                        'square_thumbnail_height'     => intval($f_sizes['Square']['height']),
                        'square_thumbnail_url'     => $f_sizes['Square']['source'],
                        'comments'     => intval($p['comments']['_content']),
                        'description'     => $p['description']['_content'],
                        'taken_time'     => $p['dates']['taken'],
                        'uploaded_time'     => $upl_time,
                        'updated_time'    => $upd_time,
                        'cached_time'   => $upd_time,
                        'last_seen_in_post' => $uncached_rec['last_seen_in_post']
                    ),
                    array( 
                        '%s', 
                        '%s', 
                        '%d',
                        '%d',
                        '%s', 
                        '%d',
                        '%d',
                        '%s', 
                        '%d',
                        '%d',
                        '%s', 
                        '%d', 
                        '%s', 
                        '%s', 
                        '%s', 
                        '%s', 
                        '%s', 
                        '%s'
                    ) 
                );
            }
        }
    }
    $wpdb->hide_errors();
    exit;
}


// Clears all records from the Flickr metadata cache.
// Responds to the "cache_clear" AJAX call, e.g. ".../admin-ajax.php?action=cache_clear".
//
function ptws_admin_cache_clear() {
    session_start();
    global $wpdb;
    $table_name = $wpdb->prefix . 'ptwsflickrcache';
    $wpdb->show_errors();

    echo '<h3>Clear cache</h3>';

    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $table_name"
        )
    );

    echo '<p>Done.</p>';

    $wpdb->hide_errors();
    exit;
}


if (!is_admin()) {
    add_action('wp_print_scripts', 'ptws_enqueue_scripts');
    add_action('wp_print_styles', 'ptws_enqueue_styles');
    // Turn off auto-formatting of entries, to prevent corruption of XML by the auto-processor
    // http://wordpress.stackexchange.com/questions/46894/why-is-wordpress-changing-my-html-code
    // https://wordpress.org/plugins/wpautop-control/
    remove_filter('the_content', 'wpautop');
    remove_filter('the_excerpt', 'wpautop');
} else {
    register_activation_hook( __FILE__, 'ptws_activate' );
/*    add_filter('plugin_action_links','ptws_add_settings_link', 10, 2 );*/
//    add_action('plugins_loaded', 'ptws_update_db_check' );
	add_action('admin_init', 'ptws_admin_init');
	add_action('admin_menu', 'ptws_admin_menu');
	add_action('wp_ajax_ptws_gallery_auth', 'ptws_auth_init');
	add_action('wp_ajax_ptws_test', 'ptws_flickr_connect_test');
    add_action('wp_ajax_ptws_resolve', 'ptws_admin_cache_resolve');
    add_action('wp_ajax_ptws_clear', 'ptws_admin_cache_clear');
}

?>