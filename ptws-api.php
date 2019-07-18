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

// Using a class structure to encapsulate and organize the API implementation.
class PTWS_API {

    private $api_version;
	private $api_namespace;

    // Called automatically when the class is instantiated.
	public function __construct() {
		$this->api_version   = '1';
		$this->api_namespace = 'ptws/v' . $this->api_version;
	}


    // Called manually when it's time to register the APIs for initialization
	public function run() {
		add_action( 'rest_api_init', array( $this, 'init_route_api' ) );
	}


    // The main function for initializing the "route" APIs.
	public function init_route_api() {
		// Route for fetching an individual route by ID
        register_rest_route( $this->api_namespace, '/route/id', array(
            array(
                // By using this constant we ensure that when the WP_REST_Server changes, our readable endpoints will work as intended.
                'methods'  => \WP_REST_Server::READABLE,
                'args' => $this->route_get_by_id_arguments(),
                // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
                'callback' => array( $this, 'route_get_by_id'),
            ),
        ) );
		// Route for fetching a list of the most recent 50 routes
        register_rest_route($this->api_namespace, '/route/recent', array(
            array(
                // By using this constant we ensure that when the WP_REST_Server changes, our readable endpoints will work as intended.
                'methods'  => \WP_REST_Server::READABLE,
                'args' => array(),
                // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
                'callback' => array($this, 'route_get_recent'),
            ),
        ));
		// Route for adding a new route
        register_rest_route($this->api_namespace, '/route/create', array(
            array(
                // By using this constant we ensure that when the WP_REST_Server changes, our create endpoints will work as intended.
                'methods'  => \WP_REST_Server::CREATABLE,
                'args' => $this->route_create_arguments(),
                // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
                'callback' => array($this, 'route_create'),
                // Here we register our permissions callback.
                // The callback is fired before the main callback to check if the current user can access the endpoint.
                'permission_callback' => array($this, 'route_permissions_check'),
            ),
        ));
	}


    // Defining the arguments for the route API 'get' method.
    public function route_get_by_id_arguments() {
        $args = array();
        // Here we are registering the schema for the route id argument.
        $args['id'] = array(
            // description should be a human readable description of the argument.
            'description' => esc_html__( 'The id parameter is the unique identifier string for the route', 'my-text-domain' ),
            // type specifies the type of data that the argument should be.
            'type'        => 'string',
            'validate_callback' => array($this, 'route_arg_validate'),
            // enum specified what values filter can take on.
            //'enum'        => array( 'red', 'green', 'blue' ),
        );
        return $args;
    }


    // Implementing the route API 'get by id' method.  Currently it just spews a boilerplate message.
	public function route_get_by_id($request) {
        if (!isset( $request['id'] ) ) {
            return new \WP_Error( 'rest_invalid', esc_html__( 'The id parameter is required.', 'my-text-domain' ), array( 'status' => 400 ) );
        }
        $response = ptws_get_route_record($request['id']);
        if ($response == null) {
            return new \WP_Error('rest_invalid', esc_html__('No route exists with ID ' . $request['id'], 'my-text-domain'), array('status' => 400));
        }
        // rest_ensure_response() wraps the data we want to return into a WP_REST_Response, and ensures it will be properly returned.
        return rest_ensure_response( $response );
    }


    // Implementing the route API 'get latest 50' method.  Currently it just spews a boilerplate message.
    public function route_get_recent($request)
    {
        // rest_ensure_response() wraps the data we want to return into a WP_REST_Response, and ensures it will be properly returned.
        return rest_ensure_response('Hello World, this is the PTWS REST API');
    }


    // Defining the arguments for the route API 'post' method.
    public function route_create_arguments() {
        $args = array();
        $args['id'] = array(
            'description' => esc_html__( 'The id parameter is the unique identifier string for the route', 'my-text-domain' ),
            'type'        => 'string',
            'validate_callback' => array( $this, 'route_arg_validate'),
        );
        $args['route'] = array(
            'description' => esc_html__( 'The contents of the route as JSON', 'my-text-domain' ),
            'type'        => 'string',
            'validate_callback' => array( $this, 'route_arg_validate'),
        );
        $args['name'] = array(
            'description' => esc_html__('An optional name to give to the route', 'my-text-domain'),
            'type'        => 'string',
        );
        $args['key'] = array(
            'description' => esc_html__( 'The secret API key (set in the plugin admin section)', 'my-text-domain' ),
            'type'        => 'string',
            'validate_callback' => array( $this, 'route_arg_validate'),
        );
        return $args;
    }


    // A basic validation function that just checks to see if the given value is defined and is a string.
    public function route_arg_validate( $value, $request, $param ) {
        if (!is_string($value)) {
            return new \WP_Error( 'rest_invalid_param', esc_html__( 'The ' . $param . ' argument must be a string.', 'my-text-domain' ), array( 'status' => 400 ) );
        }
    }


    // Implementing the route API 'post' method.
    // After checking for necessary arguments, it attempts to fetch the record with the given 'id'.
    // (Typically this is a formatted timstamp of the start of the GPS recording.)
    // If none exists, it creates the record, using the content in 'route'.  If the record already exists,
    // it replaces the body of the route with the content in 'route'.
    public function route_create($request) {
        if (!isset( $request['id'] ) ) {
            return new \WP_Error( 'rest_invalid', esc_html__( 'The id parameter is required.', 'my-text-domain' ), array( 'status' => 400 ) );
        }
        if (!isset( $request['route'] ) ) {
            return new \WP_Error( 'rest_invalid', esc_html__( 'The route parameter is required.', 'my-text-domain' ), array( 'status' => 400 ) );
        }
        if (!isset( $request['key'] ) ) {
            return new \WP_Error( 'rest_invalid', esc_html__( 'The key parameter is required.', 'my-text-domain' ), array( 'status' => 400 ) );
        }
        if (!get_option('ptws_route_api_secret')) {
            return new \WP_Error( 'rest_invalid', esc_html__( 'Route API secret is not set.', 'my-text-domain' ), array( 'status' => 400 ) );
        }
        if ($request['key'] != get_option('ptws_route_api_secret')) {
            return new \WP_Error( 'rest_invalid', esc_html__( 'The key parameter is incorrect.', 'my-text-domain' ), array( 'status' => 400 ) );
        }

        // search and remove line breaks
        $json_concatenated = str_replace(array("\n","\r"),"",$request['route']); 
        $decoded_route = json_decode($json_concatenated, TRUE);
        if (!isset($decoded_route)) {
            return rest_ensure_response( 'JSON submitted for record ' . $request['id'] . ' appears to be invalid.' );
        }
        // Get ahold of the first value in the timestamp series
        if (!array_key_exists('t', $decoded_route)) {
            return rest_ensure_response( 'JSON submitted for record ' . $request['id'] . ' does not contain a timestamp array.' );
        }
        $start_time = $decoded_route['t'][0];
        $last_value = array_slice($decoded_route['t'], -1);
        $end_time = array_pop($last_value);

        // The timestamps we are parsing will look like "2011-10-21T05:44:53+00:00", which is known as SOAP format.
        $start_time_parsed = strtotime($start_time);
        $end_time_parsed = strtotime($end_time);

        $f = array();
        $f['route_id'] = $request['id'];
        $f['route_description'] = isset($request['name']) ? $request['name'] : '';
        $f['route_json'] = $request['route'];
        $f['route_start_time'] = $start_time_parsed;
        $f['route_end_time'] = $end_time_parsed;

        $one_row = ptws_get_route_record($f['route_id']);
        if ($one_row == null) {
            ptws_create_route_record($f);
            return rest_ensure_response( 'Record ' . $f['route_id'] . ' inserted.' );
        } else {
            ptws_update_route_record($f);
            return rest_ensure_response( 'Record ' . $f['route_id'] . ' updated.' );
        }
    }


    // Check if the requester has permission to access the API.
    // Currently there is no check; the API relies on a secret key send via HTTPS.
    // If we required that the requester be logged in as a current user, it would make the API harder to use in low-bandwidth situations.
    public function route_permissions_check() {
        // Restrict endpoint to only users who have the edit_posts capability.
        //if ( ! current_user_can( 'edit_others_posts' ) ) {
        //    return new \WP_Error( 'rest_forbidden', esc_html__( 'OMG you can not view private data.', 'my-text-domain' ), array( 'status' => 401 ) );
        //}
        return true;
    }
}

?>