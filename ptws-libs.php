<?php

define('PTWS_PLUGIN_URL', plugins_url() . '/' . basename(dirname(__FILE__)));
define('PTWS_SITE_URL', site_url());

function ptws_create_afgFlickr_obj() {
    global $pf;
    $pf = new afgFlickr(get_option('ptws_api_key'), get_option('ptws_api_secret') ? get_option('ptws_api_secret') : NULL);
    $pf->setToken(get_option('ptws_flickr_token'));
}


function ptws_epoch_to_str($e) {
    $dt = new DateTime("@$e");  // convert UNIX timestamp to PHP DateTime
    return $dt->format('Y-m-d H:i:s'); // output = 2017-01-01 00:00:00
}


function ptws_error($error_msg = 'Unknown') {
    return "<h3>PTWS Error - $error_msg</h3>";
}

?>