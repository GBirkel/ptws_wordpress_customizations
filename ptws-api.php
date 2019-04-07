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

global $ptws_db_version;
$ptws_db_version = '1.91';

require_once('afgFlickr/afgFlickr.php');
include_once('ptws-libs.php');


class PTWS_API {

    private $api_version;
	private $api_namespace;

	public function __construct() {
		$this->api_version   = '1';
		$this->api_namespace = 'ptws/v' . $this->api_version;
	}


	public function run() {
		add_action( 'rest_api_init', array( $this, 'init_route_api' ) );
	}


	public function init_route_api() {
		// Register Rest route for map routes
        register_rest_route( $this->api_namespace, '/route', array(
            array(
                // By using this constant we ensure that when the WP_REST_Server changes, our readable endpoints will work as intended.
                'methods'  => \WP_REST_Server::READABLE,
                'args' => $this->route_get_arguments(),
                // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
                'callback' => array( $this, 'route_get'),
            ),
            array(
                // By using this constant we ensure that when the WP_REST_Server changes, our create endpoints will work as intended.
                'methods'  => \WP_REST_Server::CREATABLE,
                'args' => $this->route_create_arguments(),
                // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
                'callback' => array( $this, 'route_create'),
                // Here we register our permissions callback.
                // The callback is fired before the main callback to check if the current user can access the endpoint.
                'permission_callback' => array( $this, 'route_permissions_check'),
            ),
        ) );
	}


    public function route_get_arguments() {
        $args = array();
        // Here we are registering the schema for the route id argument.
        $args['id'] = array(
            // description should be a human readable description of the argument.
            'description' => esc_html__( 'The id parameter is the unique identifier string for the route', 'my-text-domain' ),
            // type specifies the type of data that the argument should be.
            'type'        => 'string',
            // enum specified what values filter can take on.
            //'enum'        => array( 'red', 'green', 'blue' ),
        );
        return $args;
    }


	public function route_get($request) {
        if (!isset( $request['id'] ) ) {
            return new \WP_Error( 'rest_invalid', esc_html__( 'The id parameter is required.', 'my-text-domain' ), array( 'status' => 400 ) );
        }
        // rest_ensure_response() wraps the data we want to return into a WP_REST_Response, and ensures it will be properly returned.
        return rest_ensure_response( 'Hello World, this is the PTWS REST API' );
    }


    public function route_create_arguments() {
        $args = array();
        $args['id'] = array(
            'description' => esc_html__( 'The id parameter is the unique identifier string for the route', 'my-text-domain' ),
            'type'        => 'string',
            'validate_callback' => array( $this, 'route_create_validate'),
        );
        $args['route'] = array(
            'description' => esc_html__( 'The contents of the route as JSON', 'my-text-domain' ),
            'type'        => 'string',
            'validate_callback' => array( $this, 'route_create_validate'),
        );
        $args['key'] = array(
            'description' => esc_html__( 'The secret API key (set in the plugin admin section)', 'my-text-domain' ),
            'type'        => 'string',
            'validate_callback' => array( $this, 'route_create_validate'),
        );
        return $args;
    }


    public function route_create_validate( $value, $request, $param ) {
        // If the 'filter' argument is not a string return an error.
        if (!is_string($value)) {
            return new \WP_Error( 'rest_invalid_param', esc_html__( 'The ' . $param . ' argument must be a string.', 'my-text-domain' ), array( 'status' => 400 ) );
        }
    }


    public function route_create($request) {
        global $wpdb;
        if (!isset( $request['id'] ) ) {
            return new \WP_Error( 'rest_invalid', esc_html__( 'The id parameter is required.', 'my-text-domain' ), array( 'status' => 400 ) );
        }
        if (!isset( $request['route'] ) ) {
            return new \WP_Error( 'rest_invalid', esc_html__( 'The route parameter is required.', 'my-text-domain' ), array( 'status' => 400 ) );
        }
        if (!isset( $request['key'] ) ) {
            return new \WP_Error( 'rest_invalid', esc_html__( 'The key parameter is required.', 'my-text-domain' ), array( 'status' => 400 ) );
        }
        if ($request['key'] != get_option('ptws_route_api_secret')) {
            return new \WP_Error( 'rest_invalid', esc_html__( 'The key parameter is incorrect.', 'my-text-domain' ), array( 'status' => 400 ) );
        }
        $routes_table_name = $wpdb->prefix . 'ptwsroutes';

        $one_row = $wpdb->get_row(
            $wpdb->prepare( 
                "
                    SELECT * 
                    FROM $routes_table_name 
                    WHERE route_id = %s
                ",
                $request['id']
            ),
            'ARRAY_A'
        );
        if ($one_row == null) {
            $wpdb->show_errors();
            $wpdb->insert(
                $routes_table_name,
                array(
                    'route_id' => $request['id'],
                    'route_json' => $request['route']
                ),
                array( 
                    '%s', 
                    '%s'
                ) 
            );
            $wpdb->hide_errors();
            return rest_ensure_response( 'Record ' . $request['id'] . ' inserted.' );
        } else {
            $wpdb->show_errors();
            $wpdb->replace(
                $routes_table_name,
                array(
                    'route_id'   => $request['id'],
                    'route_json' => $request['route']
                ),
                array( 
                    '%s', 
                    '%s'
                )
            );
            $wpdb->hide_errors();
            return rest_ensure_response( 'Record ' . $request['id'] . ' updated.' );
        }
    }


    public function route_permissions_check() {
        // Restrict endpoint to only users who have the edit_posts capability.
        //if ( ! current_user_can( 'edit_others_posts' ) ) {
        //    return new \WP_Error( 'rest_forbidden', esc_html__( 'OMG you can not view private data.', 'my-text-domain' ), array( 'status' => 401 ) );
        //}
        return true;
    }
}

?>