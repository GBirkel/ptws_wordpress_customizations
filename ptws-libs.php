<?php

namespace Poking_Things_With_Sticks;


define('PTWS_PLUGIN_URL', plugins_url() . '/' . basename(dirname(__FILE__)));
define('PTWS_SITE_URL', site_url());
define('PTWS_PLUGIN_DIRECTORY', __DIR__);


function ptws_session_check() {
    if (session_status() == PHP_SESSION_NONE) { session_start(['read_and_close' => true]); }
}


function ptws_create_afgFlickr_obj() {
    global $pf;
    $pf = new \afgFlickr(get_option('ptws_api_key'), get_option('ptws_api_secret') ? get_option('ptws_api_secret') : NULL);
    $pf->setToken(get_option('ptws_flickr_token'));
}


function ptws_epoch_to_str($e) {
    $dt = new \DateTime("@$e");  // convert UNIX timestamp to PHP DateTime
    return $dt->format('Y-m-d H:i:s'); // output = 2017-01-01 00:00:00
}


function ptws_html_log($msg = 'Unknown') {
    return "<h3 style='font-family:monospace;'>PTWS: $msg</h3>";
}

function ptws_html_log_error($error_msg = 'Unknown') {
    return "<h3 style='font-family:monospace;color:red;'>PTWS Error: $error_msg</h3>";
}

?>