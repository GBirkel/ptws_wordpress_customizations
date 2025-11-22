<?php

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
    ptws_session_check();
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
    $cp = 'publish_pages';
    add_menu_page('PTWS Custom', 'PTWS Custom', $cp, 'ptws_plugin_page', __NAMESPACE__ . '\ptws_admin_html_page', PTWS_PLUGIN_URL . "/images/ptws_logo.png", 898);
    add_submenu_page( 'ptws_plugin_page', 'Log Comment', 'Log Comment', $cp, 'ptws_log_comment', __NAMESPACE__ . '\ptws_admin_html_log_comment_page', 899 );

    // adds "Settings" link to the plugin action page
    /* add_filter( 'plugin_action_links', __NAMESPACE__ . '\ptws_add_settings_links', 10, 2);*/

    /* ptws_setup_options();*/
}


function ptws_admin_html_log_comment_page()
{
    if ($_POST) {

        if (isset($_POST['submit']) && $_POST['submit'] == 'Submit Comment') {

            $f = array();
            $f['content'] = $_POST['ptws_log_comment_text'];
            $f['composition_time'] = $_POST['ptws_log_comment_timestamp'];
            ptws_create_comment_log_record($f);

            echo "<div class='updated'><p><strong>
                Comment submitted.
                </strong></p></div>";
        }
        ptws_create_afgFlickr_obj();
    }
    $url = $_SERVER['REQUEST_URI'];
    $log_timestamp = ptws_epoch_to_str(time());

    $log_count = ptws_get_comments_count();
    $log_unresolved_count = ptws_get_unresolved_comments_count();

    ?>
        <form method='post' action='<?php echo $url ?>'>
            <div id='afg-wrap'>
                <h2>PTWS Log Comment Page</h2>
                <div id="afg-main-box">
                    <h4>Timestamp</h4>
                    <table>
                        <tr>
                            <td>
                                <input class='afg-input' type='text' name='ptws_log_comment_timestamp' value="<?php echo $log_timestamp; ?>" />
                            </td>
                            <td style="padding-left:15px;">
                                Paste in the timestamp of an existing comment to edit it.
                            </td>
                        </tr>
                    </table>
                    <h4>Comment</h4>
                    <div>
                        <textarea style="margin:8px;" name='ptws_log_comment_text' rows='8' cols='32'></textarea>
                    </div>
                    <input type="submit" name="submit" id="ptws_log_comment_submit" class="button-primary" value="Submit Comment" />
                </div>
            </div>
        </form>
    <?php
    if ($log_unresolved_count > 0) {

        $latest_unresolved = ptws_get_unresolved_comments(50);
        $latest_unresolved_count = count($latest_unresolved);

        echo "<p>{$latest_unresolved_count} most recent unresolved comments:</p>";

        echo "<table style='border-spacing:0;border:none;'>";
        $stripe = 0;
        foreach ($latest_unresolved as $lu) {
            $stripe = 1 - $stripe;
            ?>
                <tr style="background-color:rgba(230,230,230,<?php echo $stripe; ?>);">
                    <td style='padding:10px;white-space:nowrap;'><b><?php echo $lu['composition_time']; ?></b></td>
                    <td style='padding:10px;text-align:right;'>
                        <input type="button" class="button-secondary" value="Delete" onClick="document.location.href='<?php echo get_admin_url() . 'admin-ajax.php?action=ptws_delete_comment&comment_id=' . $lu['id']; ?>';" />
                    </td>
                </tr>
                <tr style="background-color:rgba(230,230,230,<?php echo $stripe; ?>);">
                    <td style='padding:10px;' colspan='2'><?php echo $lu['content']; ?></td>
                </tr>
            <?php
        }

        echo "</table>";

        echo "<p>Comment log contains {$log_count} entries total, with {$log_unresolved_count} unresolved.</p>";
    }
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

            ?>
                <div class='updated' style="font-weight:bold;">
                    <p>
                        Settings updated successfully.
                        <br /><br />
                        <font style='color:red'>Important Note:</font>
                        If you have installed a caching plugin (like WP Super Cache or W3 Total Cache etc.),
                        you may have to delete your cached pages for the settings to take effect.
                    </p>
                </div>
            <?php 
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
                    <input type="button" class="button-secondary" value="Test Flickr Credentials" onClick="document.location.href='<?php echo get_admin_url() .  'admin-ajax.php?action=ptws_test'; ?>';" />
                    <br />
                    <?php

                    if ($_POST) {
                        if (isset($_POST['submit']) && $_POST['submit'] == 'Clear Photo By Flickr ID') {

                            if (!$_POST['ptws_photo_flickr_id_to_clear']) {
                                echo '<p>No photo ID to clear entered.</p>';
                            } else {
                                ptws_clear_one_photo_by_flickr_id($_POST['ptws_photo_flickr_id_to_clear']);
                                echo '<p>Photo cleared from cache.</p>';
                            }
                        }
                        if (isset($_POST['submit']) && $_POST['submit'] == 'Clear Photo By ID') {

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
                        $cache_unresolved_count = ptws_get_unresolved_photos_count();
                        ?>
                        <h3>Photo Cache</h3>
                        <?php

                            echo "<p>Flickr cache contains {$cache_count} entries, with {$cache_unresolved_count} unresolved.</p>";

                        ?>
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

                        <p>Clear a single photo from the cache.</p>

                        <table class='ptws-admin-settings'>
                            <tr>
                                <td>Flickr Photo ID</td>
                                <td>
                                    <input class='afg-input' type='text' name='ptws_photo_flickr_id_to_clear' id='ptws_photo_flickr_id_to_clear' value="" />
                                </td>
                                <td>
                                    <input type="submit" name="submit" id="ptws_clear_single_photo_by_flickr_id" class="button-primary" value="Clear Photo By Flickr ID" />
                                </td>
                            </tr>
                            <tr>
                                <td>PTWS Database ID</td>
                                <td>
                                    <input class='afg-input' type='text' name='ptws_photo_id_to_clear' id='ptws_photo_id_to_clear' value="" />
                                </td>
                                <td>
                                    <input type="submit" name="submit" id="ptws_clear_single_photo_by_id" class="button-primary" value="Clear Photo By ID" />
                                </td>
                            </tr>
                        </table>
                        <?php
                    }

                    ?>
                    <h3>Route Database</h3>
                    <?php
                        $route_count = ptws_get_route_count();
                        echo "<p>Route database contains {$route_count} entries.</p>";
                    ?>
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

                    <br />
                    <input type="submit" name="submit" id="ptws_save_changes" class="button-primary" value="Save Settings" />
                    <br />

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
    ptws_session_check();
    global $pf;

    echo '<h3>Your Photostream Preview</h3>';

    $uid = get_option('ptws_user_id');
    echo ptws_html_log('Flickr User ID "' . $uid . '"');
    $apik = get_option('ptws_api_key');
    echo ptws_html_log('Flickr API Key "' . $apik . '"');

    if (get_option('ptws_flickr_token')) {
        $rsp_obj = $pf->people_getPhotos($uid, array('per_page' => 5, 'page' => 1));
    } else {
        $rsp_obj = $pf->people_getPublicPhotos($uid, NULL, NULL, 5, 1);
    }
    if (!$rsp_obj) {
        echo ptws_html_log_error('Flickr connectivity error:' . $pf->getErrorMsg());
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
    ptws_session_check();
    global $pf;

    echo '<h3>Manually resolve cache entries</h3>';

    $uncached_recs = ptws_get_unresolved_photos(4);

    $uid = get_option('ptws_user_id');

    if ($uncached_recs) {
        foreach ($uncached_recs as $uncached_rec) {

            $rid = $uncached_rec['id'];
            $fid = $uncached_rec['flickr_id'];

            echo ptws_html_log('Resolving Flickr photo id "' . $fid . '", record id "' . $rid . '":');

            $f_info_obj = $pf->photos_getInfo($fid);
            $f_sizes_obj = $pf->photos_getSizes($fid);

            if (!$f_info_obj || !$f_sizes_obj) {
                echo ptws_html_log_error('Error with Flickr API.');
                continue;
            }

            $r = ptws_construct_flickr_cache_record_fields($rid, $fid, $f_info_obj, $f_sizes_obj, $uncached_rec['last_seen_in_post']);

            if (!$r) {
                echo ptws_html_log_error('Error constructing Flickr cache record.');
                continue;
            }

            echo '<ul><li>id ' . $rid . '</li>';
            echo '<li>flicker_id ' . $fid . '</li>';
            echo '<li>title ' . $r['title'] . '</li>';
            echo '<li>width ' . $r['width'] . '</li>';
            echo '<li>height ' . $r['height'] . '</li>';
            echo '<li>link_url ' . $r['link_url'] . '</li>';

            echo '<li>large_thumbnail_width ' . $r['large_thumbnail_width'] . '</li>';
            echo '<li>large_thumbnail_height ' . $r['large_thumbnail_height'] . '</li>';
            echo '<li>large_thumbnail_url ' . $r['large_thumbnail_url'] . '</li>';

            echo '<li>square_thumbnail_width ' . $r['square_thumbnail_width'] . '</li>';
            echo '<li>square_thumbnail_height ' . $r['square_thumbnail_height'] . '</li>';
            echo '<li>square_thumbnail_url ' . $r['square_thumbnail_url'] . '</li>';

            echo '<li>comments ' . $r['comments'] . '</li>';
            echo '<li>description ' . $r['description'] . '</li>';
            echo '<li>taken_time ' . $r['taken_time'] . '</li>';
            echo '<li>uploaded_time ' . $r['uploaded_time'] . '</li>';
            echo '<li>updated_time ' . $r['updated_time'] . '</li>';
            echo '<li>old cached_time ' . $uncached_rec['cached_time'] . '</li>';
            echo '</ul>';

            ptws_create_flickr_cache_record($r);
        }
    }
    exit;
}


// Clears all records from the Flickr metadata cache.
// Responds to the "cache_clear" AJAX call, e.g. ".../admin-ajax.php?action=cache_clear".
//
function ptws_admin_cache_clear()
{
    ptws_session_check();

    echo '<h3>Clear cache</h3>';
    ptws_clear_photo_cache();
    echo '<p>Done.</p>';

    exit;
}


// Deleted the given comment from the comment log.
// Responds to the "ptws_delete_comment" AJAX call, e.g. ".../admin-ajax.php?action=ptws_delete_comment".
//
function ptws_admin_comment_delete()
{
    ptws_session_check();

    echo '<h3>Delete comment</h3>';
    if (!isset( $_REQUEST['comment_id'] ) ) {
        echo "<p>Error: No comment id specified.</p>";
        exit;
    }
    $comment_id = intval($_REQUEST['comment_id']);
    if (!$comment_id > 0) {
        echo "<p>Error: Comment id must be greater than 0.</p>";
        exit;
    }
    ptws_delete_one_comment($_REQUEST['comment_id']);
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
    add_action('wp_ajax_ptws_delete_comment', __NAMESPACE__ . '\ptws_admin_comment_delete');
    add_action('wp_ajax_ptws_resolve', __NAMESPACE__ . '\ptws_admin_cache_resolve');
    add_action('wp_ajax_ptws_cache_clear', __NAMESPACE__ . '\ptws_admin_cache_clear');
    add_action('plugins_loaded', __NAMESPACE__ . '\ptws_update_db_check');
}

?>