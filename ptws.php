<?php
/*
Plugin Name: Poking Things With Sticks Extensions
Plugin URI:  http://www.pokingthingswithsticks.com
Description: This plugin supports all the non-standard WP stuff I do on PTWS.  Among other things, it finds recent posted pictures on my Flickr feed and integrates them with recent WP posts in a fancypants way
Version:     1.4
Author:      Pokingthingswithsticks
Author URI:  http://www.pokingthingswithsticks.com
License:     MIT
License URI: https://Icantbebothered.tolook.thisup.right.now

Copyright 2017 Mile42 (email : gbirkel@gmail.com)
This is free software: you can redistribute it and/or modify
it under the terms of the MIT License.
 
It is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

global $ptws_db_version;
$ptws_db_version = '1.4';

include_once('ptws-libs.php');
require_once('afgFlickr/afgFlickr.php');


function ptws_activate() {
    add_option( 'ptws_plugin_activation', 'just-activated' );
}


function ptws_install() {
    global $wpdb;
    global $ptws_db_version;

    $charset_collate = $wpdb->get_charset_collate();
    echo 'Heres the debug. ';
    echo get_option( "ptws_db_version" ) . ' ';
    echo $ptws_db_version . ' ';

    $wpdb->show_errors();

    // http://php.net/manual/en/function.version-compare.php
    if (version_compare( get_option( "ptws_db_version" ), $ptws_db_version, '<' )) {
        echo 'Making a table. ';

        $table_name = $wpdb->prefix . 'ptwsflickrcache';
        echo $table_name . ' ';

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            flickr_id varchar(32) NOT NULL,
            title text,
            width int UNSIGNED,
            height int UNSIGNED,
            link_url text,
            thumbnail_url text,
            comments int UNSIGNED DEFAULT 0 NOT NULL,
            description text,
            taken_time datetime DEFAULT 0 NOT NULL,
            uploaded_time datetime DEFAULT 0 NOT NULL,
            updated_time datetime DEFAULT 0 NOT NULL,
            cached_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            CONSTRAINT unique_flickr_id UNIQUE (flickr_id),
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        update_option( "ptws_db_version", $ptws_db_version );
    }

    $wpdb->hide_errors();
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


function ptws_append_image_and_comments($p, $picContainer, $commentFlag) {

    $objA = $picContainer->addChild('a');
    $objA->addAttribute('href', (string)$p['url']);
    $objA->addAttribute('title', (string)$p['title']);

    $objImg = $objA->addChild('img');
    $objImg->addAttribute('style', 'max-width:800px;');
    $objImg->addAttribute('src', (string)$p['thumbnail']);

    if (!$commentFlag) { return; }
    if ($p->count() < 1) { return; }
    $descriptionElement = null;
    // Only respect one description element - the last one in the structure
    foreach ($p->children() as $child) {
        if ($child->getName() == 'description') {
            // Note the adding of the blank 'value' parameter, to force '<div></div>' instead of '<div/>'
            $descriptionElement = $child;
        }
    }
    if ($descriptionElement == null) { return; }

    $commentSubContainer = $picContainer->addChild('div', '');
    $commentSubContainer->addAttribute('class', 'imgComment');
    // http://stackoverflow.com/questions/3418019/simplexml-append-one-tree-to-another
    $domComContainer = dom_import_simplexml($commentSubContainer);

    $domDesc = dom_import_simplexml($descriptionElement);
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
	Which is clearly badly formed XML and ruins this plugin's content.  Thnanks, Wordpress.
    The only available workaround is to turn off automatic paragraph insertion in all entries, sitewide.
	Thanks, Wordpress.
*/


function ptwsgallery_shortcode( $atts, $content = null ) {
	if ($content == null) {
		return '';
	}
	try {
    	$sxe = simplexml_load_string($content, 'SimpleXMLIterator');
	}
	catch(Exception $e) {
    	return $e->getMessage();
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
            if ($sxe->hasChildren()) {
                foreach ($sxe->getChildren() as $element=>$value) {
                    if ($element == 'photo') {
                        if (isset($value['id'])) {
                            $photos[(string)$value['id']] = $value;
                        }
                    }
                }
            }
        } elseif ($majorSection == 'swipegallery') {
            if ($sxe->hasChildren()) {
                foreach ($sxe->getChildren() as $element=>$value) {
                    if ($element == 'galleryitem') {
                        if (isset($value['id'])) {
                            array_push($swipegalleryIDs, (string)$value['id']);
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
                        }
                    }
                }
            }
        } else {
            $emit .= '<p>Unrecognized major section ' . $majorSection . '. Must be photos, fixedgallery, or swipegallery.</p>';
        }
    }
    if ($swipegalleryIDs) {
        $emit .= "\n<div class='royalSlider heroSlider fullWidth rsMinW'>\n";
        foreach($swipegalleryIDs as $pid) {
            if (isset($photos[$pid])) {
                $p = $photos[$pid];
                $emit .= "  <div class='rsContent'>\n";
                $emit .= '<a href="' . (string)$p['url'] . '" title="' . (string)$p['title'] . '">';
                $wraw = floatval((int)$p['width']);
                $hraw = floatval((int)$p['height']);
                $w = $wraw / ($hraw / 500.0); // Not using this since we're embedding proper thumbnail-sized dimensions
                $emit .= '<img src="' . (string)$p['thumbnail'] . '" data-rsw="' . intval($wraw) . '" data-rsh="' . intval($hraw) . '" class="rsImg" />';
                $emit .= '</a>';

                $description = '';
                if ($p->count() > 0) {
                    foreach ($p->children() as $child) {
                        if ($child->getName() == 'description') {
                            $description = $child->asXML();
                        }
                    }
                }
                if ($description != '') {
                    $emit .= '<div class="rsCaption">' . $description . '</div>';
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
            if (isset($photos[$pid])) {
                $p = $photos[$pid];

                $wraw = floatval((int)$p['width']);
                $hraw = floatval((int)$p['height']);

                if ($hraw > 0) {
                    if ($wraw/$hraw < 0.8) {
                        array_push($itemsInPortrait, (string)$p['id']);
                    } else {
                        array_push($itemsNotInPortrait, (string)$p['id']);
                    }
                } else {
                    array_push($itemsNotInPortrait, (string)$p['id']);
                }
                if ($p->count() > 0) {
                    foreach ($p->children() as $child) {
                        if ($child->getName() == 'description') {
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
                <br /><br />
                <h3>Your Photostream Preview</h3>
                <table class='widefat afg-settings-box'>
                    <tr>
                    	<th>If your Flickr Settings are correct, 5 of your recent photos from your Flickr photostream should appear here.</th>
                    </tr>
                    <tr>
                    	<td>
                    		<div style="margin-top:15px">
<?php
							    global $pf;
							    if (get_option('ptws_flickr_token')) {
							    	$rsp_obj = $pf->people_getPhotos(get_option('ptws_user_id'), array('per_page' => 5, 'page' => 1));
							    } else {
							    	$rsp_obj = $pf->people_getPublicPhotos(get_option('ptws_user_id'), NULL, NULL, 5, 1);
							    }
							    if (!$rsp_obj) {
							    	echo ptws_error();
							    } else {
							        foreach($rsp_obj['photos']['photo'] as $photo) {
							            $photo_url = "http://farm{$photo['farm']}.static.flickr.com/{$photo['server']}/{$photo['id']}_{$photo['secret']}_s.jpg";
							            echo "<img src=\"$photo_url\"/>&nbsp;&nbsp;&nbsp;";
							        }
							    }
?>

							</div>
                            <br />
                            <span style="margin-top:15px">
                                Note:  This preview is based on the Flickr Settings only.  Gallery Settings
                                have no effect on this preview.  You will need to insert gallery code to a post
                                or page to actually see the Gallery.
                            </span>
                        </td>
                    </tr>
                </table>
                <br />
				<input type="button" class="button-secondary"
					value="Test"
					onClick="document.location.href='<?php echo get_admin_url() .  'admin-ajax.php?action=ptws_test'; ?>';" />
			</div>
		</div>
    </form>

<?php

    global $wpdb;
    $table_name = $wpdb->prefix . 'ptwsflickrcache';

    $wpdb->show_errors();
    $photo_cache_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
    echo "<p>Flickr cache contains {$photo_cache_count} entries.</p>";

    //$result = $wpdb->get_results('SELECT * FROM ' . $table_name . ' LIMIT 10');

    // https://codex.wordpress.org/Database_Description#Table:_wp_posts
    //$ten_posts = $wpdb->get_results('SELECT * FROM ' . $wpdb->posts . ' LIMIT 10');
    // post_modified, post_modified_gmt DATETIME
/*
    $wpdb->insert(
            $table_name,
            array(
                'flickr_id'     => $flickr_id,
                'title'         => $flickr_title,
                'width'     => $flickr_id,
                'height'     => $flickr_id,
                'link_url'     => $flickr_id,
                'thumbnail_url'     => $flickr_id,
                'comments'     => $flickr_id,
                'description'     => $flickr_id,
                'taken_time'     => $flickr_id,
                'uploaded_time'     => $flickr_id,
                'updated_time'    => $flickr_id,
                'cached_time'   => $_POST['meta_key']
            ),
            array( 
                '%s', 
                '%s', 
                '%d',
                '%d',
                '%s', 
                '%s', 
                '%s', 
                '%s', 
                '%s', 
                '%s', 
                '%s', 
                '%s'
            ) 
        );

    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $table_name
            WHERE post_id = %d
            AND meta_key = %s",
            $post_id,
            $key
        )
    );
*/
    $wpdb->hide_errors();
}


function ptws_test() {
    session_start();
    global $pf;

    if (get_option('ptws_flickr_token')) {
    	$rsp_obj = $pf->people_getPhotos(get_option('ptws_user_id'), array('per_page' => 5, 'page' => 1));
    } else {
    	$rsp_obj = $pf->people_getPublicPhotos(get_option('ptws_user_id'), NULL, NULL, 5, 1);
    }
    if (!$rsp_obj) {
    	echo ptws_error();
    } else {
        foreach($rsp_obj['photos']['photo'] as $photo) {
            $photo_url = "http://farm{$photo['farm']}.static.flickr.com/{$photo['server']}/{$photo['id']}_{$photo['secret']}_s.jpg";
            echo "<img src=\"$photo_url\" />&nbsp;&nbsp;&nbsp;";
        }
    }
    exit;
}


if (!is_admin()) {
    add_action('wp_print_scripts', 'ptws_enqueue_scripts');
    add_action('wp_print_styles', 'ptws_enqueue_styles');
    // To prevent corruption of entries by the auto-processor
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
	add_action('wp_ajax_ptws_test', 'ptws_test');
}

?>