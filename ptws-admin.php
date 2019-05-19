<?php
/*
Plugin Name: Poking Things With Sticks Extensions
Plugin URI:  http://www.mile42.net
Description: This plugin supports all the non-standard WP stuff I do on PTWS.  Among other things, it finds recent posted pictures on my Flickr feed and integrates them with recent WP posts in a fancypants way
Version:     2.00b1
Author:      Pokingthingswithsticks
Author URI:  http://www.pokingthingswithsticks.com
License:     GPL2
License URI: https://Icantbebothered.tolook.thisup.right.now

Written 2018 Mile42 (email : gbirkel@gmail.com)
This is free software: you can redistribute it and/or modify
it under the terms of the GPL2 License.

It is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

Uses LazyLoad Code by the WordPress.com VIP team, TechCrunch 2011 Redesign team, and Jake Goldman (10up LLC),
which uses jQuery.sonar by Dave Artz (AOL): http://www.artzstudio.com/files/jquery-boston-2010/jquery.sonar/
*/

namespace Poking_Things_With_Sticks;


function ptws_admin_init()
{
    if (is_admin() && get_option('ptws_plugin_activation') == 'just-activated') {
        delete_option('ptws_plugin_activation');
        ptws_install();
    }

    ptws_create_afgFlickr_obj();
    register_setting('ptws_settings_group', 'ptws_api_key');
    register_setting('ptws_settings_group', 'ptws_api_secret');
    register_setting('ptws_settings_group', 'ptws_route_api_secret');
    register_setting('ptws_settings_group', 'ptws_user_id');
    register_setting('ptws_settings_group', 'ptws_flickr_token');
    // Get afgFlickr auth token
    ptws_auth_read();
}


function ptws_auth_init()
{
    session_start();
    global $pf;
    unset($_SESSION['afgFlickr_auth_token']);
    $pf->setToken('');
    $pf->auth('read', $_SERVER['HTTP_REFERER']);
    exit;
}


function ptws_enqueue_admin_styles()
{
    wp_enqueue_style('ptws_admin_css', PTWS_PLUGIN_URL . "/css/ptws-admin.css");
}


function ptws_admin_menu()
{
    add_menu_page('PTWS Custom', 'PTWS Custom', 'publish_pages', 'ptws_plugin_page', __NAMESPACE__ . '\ptws_admin_html_page', PTWS_PLUGIN_URL . "/images/ptws_logo.png", 898);

    // adds "Settings" link to the plugin action page
    /* add_filter( 'plugin_action_links', __NAMESPACE__ . '\ptws_add_settings_links', 10, 2);*/

    /* ptws_setup_options();*/
}


function ptws_admin_html_page()
{
    global $pf;

    if ($_POST) {

        if (isset($_POST['submit']) && $_POST['submit'] == 'Save Changes') {
            update_option('ptws_route_api_secret', $_POST['ptws_route_api_secret']);
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
                echo "<div class='updated'>
        <p><strong>Click \"Grant Access\" button to authorize Awesome Flickr Gallery to access your private photos from Flickr.</strong></p>
    </div>";
            }
        }
        ptws_create_afgFlickr_obj();
    }
    $url = $_SERVER['REQUEST_URI'];
    ?>

        <form method='post' action='<?php echo $url ?>'>
            <div id='afg-wrap'>
                <h2>PTWS Custom Settings</h2>
                <div id="afg-main-box">
                    <h3>Flickr User Settings</h3>
                    <table class='ptws-admin-settings'>
                        <tr>
                            <td>Flickr User ID</td>
                            <td><input class='afg-input' type='text' name='ptws_user_id' value="<?php echo get_option('ptws_user_id'); ?>" /></td>
                            <td>
                                <div>Don't know your Flickr User ID? Get it from <a href="http://idgettr.com/" target='blank'>here.</a></div>
                            </td>
                        </tr>
                        <tr>
                            <td>Flickr API Key</td>
                            <td><input class='afg-input' type='text' name='ptws_api_key' value="<?php echo get_option('ptws_api_key'); ?>" /></td>
                            <td>
                                <div class='afg-help'>
                                    Don't have a Flickr API Key? Get it from <a href="http://www.flickr.com/services/api/keys/" target='blank'>here.</a>
                                    Go through the <a href='http://www.flickr.com/services/api/tos/'>Flickr API Terms of Service.</a>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>Flickr API Secret</td>
                            <td>
                                <input class='afg-input' type='text' name='ptws_api_secret' id='ptws_api_secret' value="<?php echo get_option('ptws_api_secret'); ?>" />
                                <br /><br />
                                <?php
                                if (get_option('ptws_api_secret')) {
                                    if (get_option('ptws_flickr_token')) {
                                        echo "<input type='button' class='button-secondary' value='Access Granted' disabled='' />";
                                    } else {
                                        ?>
                                        <input type="button" class="button-primary" value="Grant Access" onClick="document.location.href='<?php echo get_admin_url() .  'admin-ajax.php?action=ptws_gallery_auth'; ?>';" />
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
                    <?php

                    if ($_POST) {
                        if (isset($_POST['submit']) && $_POST['submit'] == 'Clear Photo') {

                            if (!$_POST['ptws_photo_id_to_clear']) {
                                echo '<p>No photo ID to clear entered.</p>';
                            } else {
                                ptws_clear_one_photo($_POST['ptws_photo_id_to_clear']);
                                echo '<p>Photo cleared from cache.</p>';
                            }
                        }
                    }

                    $cache_count = ptws_get_photos_count();
                    if ($cache_count > 0) {
                        ?>
                        <h3>Photo Cache</h3>

                        <table class='ptws-admin-settings'>

                            <tr>
                                <td colspan="2">
                                    <input type="button" class="button-secondary" value="Clear All Cache" onClick="document.location.href='<?php echo get_admin_url() .  'admin-ajax.php?action=ptws_cache_clear'; ?>';" />
                                </td>
                                <td>
                                    Wipes the entire cache.
                                </td>
                            </tr>
                            <?php
                            $cache_unresolved_count = ptws_get_unresolved_photos_count();
                            if ($cache_unresolved_count > 0) {
                                ?>

                                <tr>
                                    <td colspan="2">
                                        <input type="button" class="button-secondary" value="Resolve Entries" onClick="document.location.href='<?php echo get_admin_url() .  'admin-ajax.php?action=ptws_resolve'; ?>';" />
                                    </td>
                                    <td>
                                        Resolves 4 of the most recently added unresolved cache entries.
                                    </td>
                                </tr>

                            <?php
                        }
                        ?>
                        </table>

                        <table class='ptws-admin-settings'>
                            <tr>
                                <td>Flickr Photo ID</td>
                                <td>
                                    <input class='afg-input' type='text' name='ptws_photo_id_to_clear' id='ptws_photo_id_to_clear' value="" />
                                    <input type="submit" name="submit" id="ptws_clear_single_photo" class="button-primary" value="Clear Photo" />
                                </td>
                                <td>
                                    Clear a single photo from the cache.
                                </td>
                            </tr>
                        </table>
                        <?php
                        echo "<p>Flickr cache contains {$cache_count} entries, with {$cache_unresolved_count} unresolved.</p>";
                    }

                    ?>
                    <h3>Route Database</h3>

                    <table class='ptws-admin-settings'>
                        <tr>
                            <td>Route upload API Secret</td>
                            <td>
                                <input class='afg-input' type='text' name='ptws_route_api_secret' id='ptws_route_api_secret' value="<?php echo get_option('ptws_route_api_secret'); ?>" />
                            </td>
                            <td>
                                A unique string to authorize submitting routes to this PTWS Wordpress plugin. Embed this in the client-side GPS importer.
                            </td>
                        </tr>
                    </table>
                    <?php
                    $route_count = ptws_get_route_count();
                    echo "<p>Route database contains {$route_count} entries.</p>";
                    ?>

                    <br />
                    <input type="submit" name="submit" id="ptws_save_changes" class="button-primary" value="Save Changes" />
                    <br />
                    <br />
                    <input type="button" class="button-secondary" value="Test Flickr Credentials" onClick="document.location.href='<?php echo get_admin_url() .  'admin-ajax.php?action=ptws_test'; ?>';" />

                </div>
            </div>
        </form>

    <?php

    //$result = $wpdb->get_results('SELECT * FROM ' . $flickr_table_name . ' LIMIT 10');

    // https://codex.wordpress.org/Database_Description#Table:_wp_posts
    //$ten_posts = $wpdb->get_results('SELECT * FROM ' . $wpdb->posts . ' LIMIT 10');
    // post_modified, post_modified_gmt DATETIME
}


// Gets 5 of the most recent public photos in the stream and displays their thumbnails.
// Uses $pf, a global variable providing access to the afgFlickr flickr library (see ptws-libs).
// Responds to the "ptws_test" AJAX call, e.g. ".../admin-ajax.php?action=ptws_test".
//
function ptws_flickr_connect_test()
{
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

                    foreach ($rsp_obj['photos']['photo'] as $photo) {
                        $photo_url = "http://farm{$photo['farm']}.static.flickr.com/{$photo['server']}/{$photo['id']}_{$photo['secret']}_s.jpg";
                        echo "<img src=\"$photo_url\" />&nbsp;&nbsp;&nbsp;";
                    }

                    ?>
                    <br />
                    <span style="margin-top:15px">
                        Note: This preview is based on the Flickr Settings only. Gallery Settings
                        have no effect on this preview. You will need to insert gallery code to a post
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
function ptws_admin_cache_resolve()
{
    session_start();
    global $pf;

    echo '<h3>Manually resolve cache entries</h3>';

    $uncached_recs = ptws_get_unresolved_photos(4);

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
                foreach ($f_sizes_obj as $a => $b) {
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

                $f = array();
                $f['flickr_id'] = $fid;
                $f['title'] = $p['title']['_content'];
                $f['width'] = intval($f_sizes['Original']['width']);
                $f['height'] = intval($f_sizes['Original']['height']);
                $f['link_url'] = $url;
                $f['large_thumbnail_width'] = $large_w;
                $f['large_thumbnail_height'] = $large_h;
                $f['large_thumbnail_url'] = $large_src;
                $f['square_thumbnail_width'] = intval($f_sizes['Square']['width']);
                $f['square_thumbnail_height'] = intval($f_sizes['Square']['height']);
                $f['square_thumbnail_url'] = $f_sizes['Square']['source'];
                $f['comments'] = intval($p['comments']['_content']);
                $f['description'] = $p['description']['_content'];
                $f['taken_time'] = $p['dates']['taken'];
                $f['uploaded_time'] = $upl_time;
                $f['updated_time'] = $upd_time;
                $f['cached_time'] = $upd_time;
                $f['last_seen_in_post'] = $uncached_rec['last_seen_in_post'];
                ptws_create_flickr_cache_record($f);
            }
        }
    }
    exit;
}


// Clears all records from the Flickr metadata cache.
// Responds to the "cache_clear" AJAX call, e.g. ".../admin-ajax.php?action=cache_clear".
//
function ptws_admin_cache_clear()
{
    session_start();

    echo '<h3>Clear cache</h3>';
    ptws_clear_photo_cache();
    echo '<p>Done.</p>';

    exit;
}


function ptws_init_actions_for_admin()
{
    add_action('admin_print_styles', __NAMESPACE__ . '\ptws_enqueue_admin_styles');
    add_action('admin_init', __NAMESPACE__ . '\ptws_admin_init');
    add_action('admin_menu', __NAMESPACE__ . '\ptws_admin_menu');
    add_action('wp_ajax_ptws_gallery_auth', __NAMESPACE__ . '\ptws_auth_init');
    add_action('wp_ajax_ptws_test', __NAMESPACE__ . '\ptws_flickr_connect_test');
    add_action('wp_ajax_ptws_resolve', __NAMESPACE__ . '\ptws_admin_cache_resolve');
    add_action('wp_ajax_ptws_cache_clear', __NAMESPACE__ . '\ptws_admin_cache_clear');
    add_action('plugins_loaded', __NAMESPACE__ . '\ptws_update_db_check');
}

?>