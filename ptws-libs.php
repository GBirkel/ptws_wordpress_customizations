<?php

define('PTWS_PLUGIN_URL', plugins_url() . '/' . basename(dirname(__FILE__)));
define('PTWS_SITE_URL', site_url());
define('DEBUG', false);
define('VERSION', '0.0.1');

function ptws_create_afgFlickr_obj() {
    global $pf;
    $pf = new afgFlickr(get_option('ptws_api_key'), get_option('ptws_api_secret') ? get_option('ptws_api_secret') : NULL);
    $pf->setToken(get_option('ptws_flickr_token'));
}


function ptws_error($error_msg) {
    return "<h3>PTWS Error - $error_msg</h3>";
}

?>