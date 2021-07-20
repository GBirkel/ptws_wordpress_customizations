<?php

namespace Poking_Things_With_Sticks;


function ptws_create_photo_tables()
{
    global $wpdb;

    $flickr_table_name = $wpdb->prefix . 'ptwsflickrcache';
    $charset_collate = $wpdb->get_charset_collate();

    // last_seen_in_post references a field in the Wordpress posts table
    // https://codex.wordpress.org/Database_Description#Table:_wp_posts
    $sql = "CREATE TABLE " . $flickr_table_name . " (
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
        PRIMARY KEY  (id)
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

    $route_sql = "CREATE TABLE " . $route_table_name . " (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        route_id varchar(32) NOT NULL,
        route_json LONGTEXT,
        route_description text DEFAULT '',
        route_start_time datetime DEFAULT 0 NOT NULL,
        route_end_time datetime DEFAULT 0 NOT NULL,
        cached_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        auto_placed tinyint(1) DEFAULT 0,
        last_seen_in_post bigint(20) UNSIGNED,
        CONSTRAINT unique_route_id UNIQUE (route_id),
        PRIMARY KEY  (id)
    ) $charset_collate;";

    if (!function_exists('dbDelta')) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    }
    dbDelta($route_sql);
}


function ptws_create_comment_tables()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'ptwsphotocommentlog';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE " . $table_name . " (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        content text,
        composition_time datetime DEFAULT 0 NOT NULL,
        submit_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        time_of_embedded_photo datetime,
        flickr_id_of_embedded_photo varchar(32),
        CONSTRAINT unique_composition_time UNIQUE (composition_time),
        PRIMARY KEY (id)
    ) $charset_collate;";

    if (!function_exists('dbDelta')) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    }
    dbDelta($sql);
}


// Adds an unresolved entry to the photo cache
function ptws_add_uncached_photo($pid)
{
    global $wpdb;
    $flickr_table_name = $wpdb->prefix . 'ptwsflickrcache';
    $wpdb->show_errors();
    $wpdb->insert(
        $flickr_table_name,
        array(
            'flickr_id' => $pid,
            'cached_time' => 0,
            'last_seen_in_post' => get_the_ID()
        ),
        array(
            '%s',
            '%d',
            '%s'
        )
    );
    $wpdb->hide_errors();
}


// Removes all entries from the photo cache
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
function ptws_clear_one_photo($pid)
{
    global $wpdb;
    $flickr_table_name = $wpdb->prefix . 'ptwsflickrcache';
    $wpdb->show_errors();

    $wpdb->query(
        $wpdb->prepare("DELETE FROM $flickr_table_name WHERE flickr_id = %s", $pid)
    );
    $wpdb->hide_errors();
}


// Gets up to n unresolved entries from the photo cache
function ptws_get_unresolved_photos($n)
{
    global $wpdb;
    $flickr_table_name = $wpdb->prefix . 'ptwsflickrcache';

    $uncached_recs = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $flickr_table_name WHERE cached_time = %d LIMIT %d", array(0, $n)),
        'ARRAY_A'
    );

    return $uncached_recs;
}


// Gets the count of photos in the cache
function ptws_get_photos_count()
{
    global $wpdb;
    $flickr_table_name = $wpdb->prefix . 'ptwsflickrcache';
    return $wpdb->get_var("SELECT COUNT(*) FROM $flickr_table_name");
}


// Gets the count of unresolved photos in the cache
function ptws_get_unresolved_photos_count()
{
    global $wpdb;
    $flickr_table_name = $wpdb->prefix . 'ptwsflickrcache';
    return $wpdb->get_var("SELECT COUNT(*) FROM $flickr_table_name WHERE cached_time = 0");
}


// Fetch the n most recent photo Flickr IDs.
function ptws_get_latest_flickr_cache_ids($limit)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'ptwsflickrcache';
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "
                SELECT flickr_id
                FROM $table_name
                ORDER BY taken_time DESC LIMIT %d
            ",
            array($limit)
        ),
        'ARRAY_A'
    );
    $s = array();
    foreach ($results as $one_row) {
        array_push($s, (string)$one_row['flickr_id']);
    }
    return $s;
}


// Given the Flickr ID of a photo, seek its record in the database, and return it.
// If no record exists, return null instead.
function ptws_get_flickr_cache_record($pid)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'ptwsflickrcache';
    $one_row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE flickr_id = %s", $pid
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


// Create a fully-resolved record in the cache with the given fields.
function ptws_create_flickr_cache_record($f)
{
    global $wpdb;
    $flickr_table_name = $wpdb->prefix . 'ptwsflickrcache';
    $wpdb->show_errors();
    $wpdb->replace(
        $flickr_table_name,
        array(
            'flickr_id'             => $f['flickr_id'],
            'title'                 => $f['title'],
            'width'                 => $f['width'],
            'height'                => $f['height'],
            'link_url'              => $f['link_url'],
            'large_thumbnail_width'     => $f['large_thumbnail_width'],
            'large_thumbnail_height'    => $f['large_thumbnail_height'],
            'large_thumbnail_url'       => $f['large_thumbnail_url'],
            'square_thumbnail_width'    => $f['square_thumbnail_width'],
            'square_thumbnail_height'   => $f['square_thumbnail_height'],
            'square_thumbnail_url'      => $f['square_thumbnail_url'],
            'comments'              => $f['comments'],
            'description'           => $f['description'],
            'taken_time'            => $f['taken_time'],
            'uploaded_time'         => $f['uploaded_time'],
            'updated_time'          => $f['updated_time'],
            'cached_time'           => $f['cached_time'],
            'last_seen_in_post'     => $f['last_seen_in_post']
        ),
        array(
            '%s',
            '%s',
            '%d',
            '%d',
            '%s',
            '%d',
            '%d',
            '%s',
            '%d',
            '%d',
            '%s',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d'
        )
    );
    $wpdb->hide_errors();
}


// Gets the count of routes
function ptws_get_route_count()
{
    global $wpdb;
    $route_table_name = $wpdb->prefix . 'ptwsroutes';
    return $wpdb->get_var("SELECT COUNT(*) FROM $route_table_name");
}


// Given the ID of a GPS route in the database, locate and return it.
// If no record exists, return null instead.
function ptws_get_route_record($pid)
{
    global $wpdb;
    $routes_table_name = $wpdb->prefix . 'ptwsroutes';
    $one_row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $routes_table_name WHERE route_id = %s", $pid
        ),
        'ARRAY_A'
    );
    if ($one_row == null) {
        return null;
    }

    $r = array();
    $r['id'] = (string)$one_row['id'];
    $r['route_id'] = (string)$one_row['route_id'];
    $r['route_description'] = (string)$one_row['route_description'];
    $r['route_json'] = (string)$one_row['route_json'];
    $r['auto_placed'] = (string)$one_row['auto_placed'];
    $r['last_seen_in_post'] = (string)$one_row['last_seen_in_post'];
    $r['route_start_time'] = (string)$one_row['route_start_time'];
    $r['route_end_time'] = (string)$one_row['route_end_time'];
    $r['cached_time'] = (string)$one_row['cached_time'];
    // Use PHP to make epoch conversions since SQL may not properly handle negative epochs.
    // https://www.epochconverter.com/programming/php
    // https://www.epochconverter.com/programming/mysql
    $r['route_start_time_epoch'] = (string)strtotime($one_row['route_start_time']);
    $r['route_end_time_epoch'] = (string)strtotime($one_row['route_end_time']);
    $r['cached_time_epoch'] = (string)strtotime($one_row['cached_time']);
    return $r;
}


// Get the latest 50 uploaded routes.
// If no record exists, return null instead.
function ptws_get_recent_routes($n)
{
    global $wpdb;
    $routes_table_name = $wpdb->prefix . 'ptwsroutes';
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "
                SELECT id, route_id, route_description, auto_placed, last_seen_in_post, route_start_time, route_end_time, cached_time
                FROM $routes_table_name 
                ORDER BY route_start_time DESC LIMIT %d
            ",
            array($n)
        ),
        'ARRAY_A'
    );

    $s = array();
    foreach ($results as $one_row) {
        $r = array();
        $r['id'] = (string)$one_row['id'];
        $r['route_id'] = (string)$one_row['route_id'];
        $r['route_description'] = (string)$one_row['route_description'];
        $r['auto_placed'] = (string)$one_row['auto_placed'];
        $r['last_seen_in_post'] = (string)$one_row['last_seen_in_post'];
        $r['route_start_time'] = (string)$one_row['route_start_time'];
        $r['route_end_time'] = (string)$one_row['route_end_time'];
        $r['cached_time'] = (string)$one_row['cached_time'];
        // Use PHP to make epoch conversions since SQL may not properly handle negative epochs.
        // https://www.epochconverter.com/programming/php
        // https://www.epochconverter.com/programming/mysql
        $r['route_start_time_epoch'] = (string)strtotime($one_row['route_start_time']);
        $r['route_end_time_epoch'] = (string)strtotime($one_row['route_end_time']);
        $r['cached_time_epoch'] = (string)strtotime($one_row['cached_time']);
        array_push($s, $r);
    }
    return $s;
}


// Given the ID of a GPS route in the database, locate and return it.
// If no record exists, return null instead.
function ptws_create_route_record($f)
{
    global $wpdb;
    $routes_table_name = $wpdb->prefix . 'ptwsroutes';
    $wpdb->show_errors();
    $wpdb->insert(
        $routes_table_name,
        array(
            'route_id' => $f['route_id'],
            'route_json' => $f['route_json'],
            'route_start_time' => $f['route_start_time'],
            'route_end_time' => $f['route_end_time']
        ),
        array( 
            '%s', 
            '%s',
            '%s',
            '%s'
        ) 
    );
    $wpdb->hide_errors();
}


// Given a route record, update the record with the corresponding route_id in the database to match.
// The given route record can have an incomplete set of fields.
// Any that are left un-set will not be changed in the target record.
function ptws_update_route_record($f)
{
    global $wpdb;
    $routes_table_name = $wpdb->prefix . 'ptwsroutes';
    if (!isset( $f['route_id'] ) ) { return; }
    $g = ptws_get_route_record($f['route_id']);
    if (!$g) { return; }
    if (!$g['id']) { return; }

    // Merge over only the fields that are set in the incoming record
    if (isset($f['route_description'])) { $g['route_description'] = $f['route_description']; }
    if (isset($f['route_json'])) { $g['route_json'] = $f['route_json']; }
    if (isset($f['auto_placed'])) { $g['auto_placed'] = $f['auto_placed']; }
    if (isset($f['last_seen_in_post'])) { $g['last_seen_in_post'] = $f['last_seen_in_post']; }
    if (isset($f['route_start_time'])) { $g['route_start_time'] = $f['route_start_time']; }
    if (isset($f['route_end_time'])) { $g['route_end_time'] = $f['route_end_time']; }
    if (isset($f['cached_time'])) { $g['cached_time'] = $f['cached_time']; }

    $wpdb->show_errors();
    $wpdb->update(
        $routes_table_name,
        array(
            'route_description' => $g['route_description'],
            'route_json' => $g['route_json'],
            'auto_placed' => $g['auto_placed'],
            'last_seen_in_post' => $g['last_seen_in_post'],
            'route_start_time' => $g['route_start_time'],
            'route_end_time' => $g['route_end_time'],
            'cached_time' => $g['cached_time']
        ),
        array(
            'id' => $g['id']
        ),
        array( 
            '%s',
            '%s',
            '%d',
            '%d',
            '%s',
            '%s',
            '%s'
        ),
        array(
            '%d'
        )
    );
    $wpdb->hide_errors();
}


// Given the ID of a GPS route in the database, set the 'last seen' value for it
// to the currently viewed post.
function ptws_update_route_record_last_seen($pid)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'ptwsroutes';
    $wpdb->update(
        $table_name,
        array('last_seen_in_post' => get_the_ID() ),
        array('route_id'   => $pid),
        array('%d'),
        array('%s')
    );
}


// Create or replace an entry in the comment log.
function ptws_create_comment_log_record($f)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'ptwsphotocommentlog';
    $wpdb->show_errors();
    $wpdb->replace(
        $table_name,
        array(
            'content'            => $f['content'],
            'composition_time'   => $f['composition_time']
        ),
        array(
            '%s',
            '%s'
        )
    );
    $wpdb->hide_errors();
}


// Gets the count of photos in the cache
function ptws_get_comments_count()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'ptwsphotocommentlog';
    return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
}


// Gets the count of unresolved photos in the cache
function ptws_get_unresolved_comments_count()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'ptwsphotocommentlog';
    return $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE time_of_embedded_photo is NULL");
}


// Gets up to n unresolved comments from the comment log, with newest first
function ptws_get_unresolved_comments($n)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'ptwsphotocommentlog';

    $results = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table_name WHERE time_of_embedded_photo is NULL ORDER BY submit_time DESC LIMIT %d", $n),
        'ARRAY_A'
    );

    $s = array();
    foreach ($results as $one_row) {
        $r = array();
        $r['id'] = (string)$one_row['id'];
        $r['content'] = (string)$one_row['content'];
        $r['composition_time'] = (string)$one_row['composition_time'];
        $r['submit_time'] = (string)$one_row['submit_time'];
        // Use PHP to make epoch conversions since SQL may not properly handle negative epochs.
        // https://www.epochconverter.com/programming/php
        // https://www.epochconverter.com/programming/mysql
        $r['composition_time_epoch'] = (string)strtotime($one_row['composition_time']);
        $r['submit_time_epoch'] = (string)strtotime($one_row['submit_time']);
        array_push($s, $r);
    }
    return $s;
}


// Removes the comment with the given ID from the comment log
function ptws_delete_one_comment($pid)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'ptwsphotocommentlog';
    $wpdb->show_errors();
    $wpdb->query(
        $wpdb->prepare("DELETE FROM $table_name WHERE id = %s", $pid)
    );
    $wpdb->hide_errors();
}

?>