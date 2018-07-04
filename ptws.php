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

require_once('afgFlickr/afgFlickr.php');

function add_query_vars_filter( $vars ){
   $vars[] = "ptwsdo";
   return $vars;
}
add_filter( 'query_vars', 'add_query_vars_filter' );

$do_var = (get_query_var('ptwsdo')) ? get_query_var('ptwsdo') : false;
if ($do_var) {
	if ($do_var == 'test') {
		echo '<div style="font-family:\'Open Sans\',sans-serif;font-size: 15px;">';
		echo 'Yup, this is a test alright.';
	    echo '</div>';
	    die();
	}
} else {
	define('PTWS_PLUGIN_URL', plugins_url() . '/' . basename(dirname(__FILE__)));
}

function ptws_enqueue_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('ptws_script', PTWS_PLUGIN_URL . "/js/ptws.js" , array('jquery'));
}

function ptws_enqueue_styles() {
    wp_enqueue_style('ptws_css', PTWS_PLUGIN_URL . "/css/ptws.css");
}

if (!is_admin()) {
    add_action('wp_print_scripts', 'ptws_enqueue_scripts');
    add_action('wp_print_styles', 'ptws_enqueue_styles');
}

?>