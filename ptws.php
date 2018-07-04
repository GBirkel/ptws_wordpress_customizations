<?php
/*
Plugin Name: Poking Things With Sticks Extensions
Plugin URI:  http://www.pokingthingswithsticks.com
Description: This plugin supports all the non-standard WP stuff I do on PTWS.  Among other things, it finds recent posted pictures on my Flickr feed and integrates them with recent WP posts in a fancypants way
Version:     0.1
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

include_once('ptws-libs.php');
require_once('afgFlickr/afgFlickr.php');

ptws_create_afgFlickr_obj();

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


function ptwsgallery_shortcode( $atts, $content = null ) {
	if ($content == null) {
		return '';
	}
	$emit = '';
    $sxi = new SimpleXmlIterator($content);

    for ($sxi->rewind(); $sxi->valid(); $sxi->next() ) {

        if ($sxi->key() == 'photo') {
			$emit .= ' found photo';
        }
        if ($sxi->hasChildren()) {
			$emit .= ' found children';
        }
    }

	return '<span>' . $emit . '</span>';
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
} else {
/*    add_filter('plugin_action_links','ptws_add_settings_link', 10, 2 );*/
	add_action('admin_init', 'ptws_admin_init');
	add_action('admin_menu', 'ptws_admin_menu');
	add_action('wp_ajax_ptws_gallery_auth', 'ptws_auth_init');
	add_action('wp_ajax_ptws_test', 'ptws_test');
}

?>