<?php

require_once('afgFlickr/afgFlickr.php');

add_action('admin_init', 'ptws_admin_init');
add_action('admin_menu', 'ptws_admin_menu');


function ptws_admin_init() {
    register_setting('afg_settings_group', 'afg_api_key');
    register_setting('afg_settings_group', 'afg_api_secret');
    register_setting('afg_settings_group', 'afg_user_id');
    register_setting('afg_settings_group', 'afg_flickr_token');
}


function ptws_admin_menu() {
    add_menu_page('PTWS Custom', 'PTWS Custom', 'publish_pages', 'ptws_plugin_page', 'ptws_admin_html_page', BASE_URL . "/images/ptws_logo.png", 898);

    // adds "Settings" link to the plugin action page
/*    add_filter( 'plugin_action_links', 'afg_add_settings_links', 10, 2);*/

/*    afg_setup_options();*/
}


function ptws_admin_html_page() {
    global $pf;

	if ($_POST) {
	    global $pf, $custom_size_err_msg;

        if (isset($_POST['submit']) && $_POST['submit'] == 'Save Changes') {
            update_option('afg_api_key', $_POST['afg_api_key']);
            if (!$_POST['afg_api_secret'] || $_POST['afg_api_secret'] != get_option('afg_api_secret')) {
                update_option('afg_flickr_token', '');
            }
            update_option('afg_api_secret', $_POST['afg_api_secret']);
            update_option('afg_user_id', $_POST['afg_user_id']);

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
            if (get_option('afg_api_secret') && !get_option('afg_flickr_token')) {
                echo "<div class='updated'><p><strong>Click \"Grant Access\" button to authorize Awesome Flickr Gallery to access your private photos from Flickr.</strong></p></div>";
            }
        }
        create_afgFlickr_obj();
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
                    	<td><input class='afg-input' type='text' name='afg_user_id' value="<?php echo get_option('afg_user_id'); ?>" /><b>*</b></td>
                    	<td><div class="afg-help">Don't know your Flickr User ID?  Get it from <a href="http://idgettr.com/" target='blank'>here.</a></div></td>
                    </tr>
                    <tr>
                    	<td>Flickr API Key</td>
                    	<td><input class='afg-input' type='text' name='afg_api_key' value="<?php echo get_option('afg_api_key'); ?>" ><b>*</b></input> </td>
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
                        	<input class='afg-input' type='text' name='afg_api_secret' id='afg_api_secret' value="<?php echo get_option('afg_api_secret'); ?>"/>
                    		<br /><br />
<?php
							if (get_option('afg_api_secret')) {
    							if (get_option('afg_flickr_token')) {
    								echo "<input type='button' class='button-secondary' value='Access Granted' disabled=''";
    							} else {
?>
    								<input type="button" class="button-primary"
    									value="Grant Access"
    									onClick="document.location.href='<?php echo get_admin_url() .  'admin-ajax.php?action=afg_gallery_auth'; ?>';"/>
<?php
								}
							} else {
								echo "<input type='button' class='button-secondary' value='Grant Access' disabled=''";
							}
?>
                        </td>
                        <td class="afg-help">
                        	<b>ONLY</b> If you want to include your <b>Private Photos</b> in your galleries, enter your Flickr API Secret here and click Save Changes.
                        </td>
                    </tr>
    			</table>
                <br />
                <input type="submit" name="submit" id="afg_save_changes" class="button-primary" value="Save Changes" />
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
							    if (get_option('afg_flickr_token')) {
							    	$rsp_obj = $pf->people_getPhotos(get_option('afg_user_id'), array('per_page' => 5, 'page' => 1));
							    } else {
							    	$rsp_obj = $pf->people_getPublicPhotos(get_option('afg_user_id'), NULL, NULL, 5, 1);
							    }
							    if (!$rsp_obj) {
							    	echo afg_error();
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
                <input type="submit" name="submit" class="button-secondary" value="Delete Cached Galleries"/>
			</div>
		</div>
    </form>
<?php
	}
?>
