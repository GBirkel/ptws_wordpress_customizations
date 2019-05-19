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


function ptws_create_photo_tables()
{
    global $wpdb;

    $flickr_table_name = $wpdb->prefix . 'ptwsflickrcache';
    $charset_collate = $wpdb->get_charset_collate();

    // last_seen_in_post references a field in the Wordpress posts table
    // https://codex.wordpress.org/Database_Description#Table:_wp_posts
    $sql = "CREATE TABLE $flickr_table_name (
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
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    }
    dbDelta($sql);
}


function ptws_create_route_tables()
{
    global $wpdb;

    $route_table_name = $wpdb->prefix . 'ptwsroutes';
    $charset_collate = $wpdb->get_charset_collate();

    $route_sql = "CREATE TABLE $route_table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        route_id varchar(32) NOT NULL,
        route_json LONGTEXT,
        route_description text DEFAULT '',
        route_start_time datetime DEFAULT 0 NOT NULL,
        route_end_time datetime DEFAULT 0 NOT NULL,
        cached_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        auto_placed tinyint(1) DEFAULT 0,
        last_seen_in_post bigint(20) unsigned,
        CONSTRAINT unique_route_id UNIQUE (route_id),
        PRIMARY KEY (id)
    ) $charset_collate;";

    if (!function_exists('dbDelta')) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    }
    dbDelta($route_sql);
}


// Removes all entries from the photo cache
//
function ptws_clear_photo_cache()
{
    global $wpdb;
    $flickr_table_name = $wpdb->prefix . 'ptwsflickrcache';
    $wpdb->show_errors();

    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $flickr_table_name"
        )
    );
    $wpdb->hide_errors();
}


// Removes the entry with the given ID from the photo cache
//
function ptws_clear_one_photo($pid)
{
    global $wpdb;
    $flickr_table_name = $wpdb->prefix . 'ptwsflickrcache';
    $wpdb->show_errors();

    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $flickr_table_name WHERE flickr_id = %s",
            $pid
        )
    );
    $wpdb->hide_errors();
}


// Gets up to n unresolved entries from the photo cache
//
function ptws_get_unresolved_photos($n)
{
    global $wpdb;
    $flickr_table_name = $wpdb->prefix . 'ptwsflickrcache';

    $uncached_recs = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $flickr_table_name WHERE cached_time = %d LIMIT %d",
            array(0, $n)
        ),
        'ARRAY_A'
    );

    return $uncached_recs;
}


// Given the Flickr ID of a photo, seek its record in the database, and return it.
// If no record exists, return null instead.
//
function ptws_get_flickr_cache_record($pid)
{
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


// Given the ID of a GPS route in the database, locate and return it.
// If no record exists, return null instead.
//
function ptws_get_route_record($pid)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'ptwsroutes';
    $one_row = $wpdb->get_row(
        $wpdb->prepare(
            "
                SELECT * 
                FROM $table_name 
                WHERE route_id = %s
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
    $one_row['cached_time_epoch'] = strtotime($one_row['cached_time']);
    return $one_row;
}


// Given the ID of a GPS route in the database, locate and return it.
// If no record exists, return null instead.
//
function ptws_update_route_record_last_seen($pid)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'ptwsroutes';
    $wpdb->replace(
        $table_name,
        array(
            'route_id'   => $pid,
            'last_seen_in_post' => get_the_ID()
        ),
        array(
            '%s',
            '%d'
        )
    );
}


?>