<?php

namespace Poking_Things_With_Sticks;

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
		add_action( 'rest_api_init', array( $this, 'init_ptws_api' ) );
	}


    // The main function for initializing the "route" APIs.
	public function init_ptws_api() {
		// Route for fetching an individual image by Flickr ID
        register_rest_route( $this->api_namespace, '/image/flickrid', array(
            array(
                // By using this constant we ensure that when the WP_REST_Server changes, our readable endpoints will work as intended.
                'methods'  => \WP_REST_Server::READABLE,
                'args' => $this->image_get_by_flickr_id_arguments(),
                // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
                'callback' => array( $this, 'image_get_by_flickr_id'),
            ),
        ) );
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
                'permission_callback' => array($this, 'standard_permissions_check'),
            ),
        ));
		// Route for fetching a list of the most recent 50 unresolved comments
        register_rest_route($this->api_namespace, '/commentlog/unresolved', array(
            array(
                // By using this constant we ensure that when the WP_REST_Server changes, our readable endpoints will work as intended.
                'methods'  => \WP_REST_Server::CREATABLE,
                'args' => $this->standard_api_key_only_arguments(),
                // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
                'callback' => array($this, 'comment_get_recent_unresolved'),
                'permission_callback' => array($this, 'standard_permissions_check'),
            ),
        ));
	}


    // Defining the arguments for the image API 'get' method.
    public function image_get_by_flickr_id_arguments() {
        $args = array();
        // Here we are registering the schema for the image id argument.
        $args['id'] = array(
            // description should be a human readable description of the argument.
            'description' => esc_html__( 'The id parameter is the unique identifier string for the image', 'my-text-domain' ),
            // type specifies the type of data that the argument should be.
            'type'        => 'string',
            'validate_callback' => array($this, 'ptws_string_arg_validate'),
            // enum specifies what values filter can take on.
            //'enum'        => array( 'red', 'green', 'blue' ),
        );
        $args['last_seen_in_post'] = array(
            // description should be a human readable description of the argument.
            'description' => esc_html__( 'The id of the post requesting this image, if applicable.', 'my-text-domain' ),
            'type'        => 'string',
            'validate_callback' => array($this, 'ptws_string_arg_validate'),
        );
        return $args;
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
            'validate_callback' => array($this, 'ptws_string_arg_validate'),
        );
        return $args;
    }


    // Implementing the image API 'get by Flickr id' method.
	public function image_get_by_flickr_id($request) {
        if (!isset( $request['id'] ) ) {
            return new \WP_Error( 'rest_invalid',
                        esc_html__( 'The id parameter is required.', 'my-text-domain' ),
                        array( 'status' => 400 ) );
        }

        $flickr_id = $request['id'];

        $response = ptws_get_flickr_cache_record($flickr_id);
        if ($response != null) {
            // rest_ensure_response() wraps the data we want to return into a WP_REST_Response,
            // and ensures it will be properly returned.
            return rest_ensure_response( $response );
        }

        if (current_user_can( 'edit_posts' )) {
            ptws_session_check();
            $flickr_user_id = get_option('ptws_user_id');

            $last_seen_in_post = null;
            if (isset( $request['last_seen_in_post'] ) ) {
                $last_seen_in_post = $request['last_seen_in_post'];
            }

            global $pf;
            ptws_create_afgFlickr_obj();

            $f_info_obj = $pf->photos_getInfo($flickr_id);
            $f_sizes_obj = $pf->photos_getSizes($flickr_id);

            if (!$f_info_obj || !$f_sizes_obj) {
                return new \WP_Error('rest_invalid',
                            esc_html__('Queried Flickr, but no image exists with ID ' . $flickr_id, 'my-text-domain'),
                            array('status' => 400));
            }

            $r = ptws_construct_flickr_cache_record_fields($flickr_user_id, $flickr_id, $f_info_obj, $f_sizes_obj, $last_seen_in_post);

            if (!$r) {
                return new \WP_Error('rest_invalid',
                            esc_html__('Error constructing Flickr cache record for Flickr ID ' . $flickr_id, 'my-text-domain'),
                            array('status' => 400));
            }

            ptws_create_flickr_cache_record($r);

            $response = ptws_get_flickr_cache_record($flickr_id);
            if ($response == null) {
                return new \WP_Error('rest_invalid',
                            esc_html__('Error fetching newly created Flickr cache record for Flickr ID ' . $flickr_id, 'my-text-domain'),
                            array('status' => 400));
            }

            // rest_ensure_response() wraps the data we want to return into a WP_REST_Response,
            // and ensures it will be properly returned.
            return rest_ensure_response( $response );
        }
        return new \WP_Error('rest_invalid',
                    esc_html__('No cached image exists with ID ' . $request['id'], 'my-text-domain'),
                    array('status' => 400));
    }


    // Implementing the route API 'get by id' method.
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


    // Implementing the route API 'get latest 50' method.
    public function route_get_recent($request)
    {
        $response = ptws_get_recent_routes(50);
        if ($response == null) {
            return new \WP_Error('rest_invalid', esc_html__('Problem getting latest routes', 'my-text-domain'), array('status' => 400));
        }
        return rest_ensure_response( $response );
    }


    // Standard arguments requirement:  Just the API key.
    public function standard_api_key_only_arguments() {
        $args = array();
        $args['key'] = array(
            'description' => esc_html__( 'The secret API key (set in the plugin admin section)', 'my-text-domain' ),
            'type'        => 'string',
            'validate_callback' => array( $this, 'ptws_string_arg_validate'),
        );
        return $args;
    }


    // Defining the arguments for the route API 'post' method.
    public function route_create_arguments() {
        $args = array();
        $args['id'] = array(
            'description' => esc_html__( 'The id parameter is the unique identifier string for the route', 'my-text-domain' ),
            'type'        => 'string',
            'validate_callback' => array( $this, 'ptws_string_arg_validate'),
        );
        $args['route'] = array(
            'description' => esc_html__( 'The contents of the route as JSON', 'my-text-domain' ),
            'type'        => 'string',
            'validate_callback' => array( $this, 'ptws_string_arg_validate'),
        );
        $args['name'] = array(
            'description' => esc_html__('An optional name to give to the route', 'my-text-domain'),
            'type'        => 'string',
        );
        $args['key'] = array(
            'description' => esc_html__( 'The secret API key (set in the plugin admin section)', 'my-text-domain' ),
            'type'        => 'string',
            'validate_callback' => array( $this, 'ptws_string_arg_validate'),
        );
        return $args;
    }


    // A basic validation function that just checks to see if the given value is defined and is a string.
    public function ptws_string_arg_validate( $value, $request, $param ) {
        if (!is_string($value)) {
            return new \WP_Error( 'rest_invalid_param', esc_html__( 'The ' . $param . ' argument must be a string.', 'my-text-domain' ), array( 'status' => 400 ) );
        }
    }


    // Implementing the route API 'post' method.
    // After checking for necessary arguments, it attempts to fetch the record with the given 'id'.
    // (Typically this is a formatted timstamp of the start of the GPS recording.)
    // If none exists, it creates the record, using the content in 'route'.  If the record already exists,
    // it replaces the body of the route with the content in 'route'.
    //
    // We used to accept all the JSON as a regular multipart form element,
    // But that ran afoul of mod_secrurity's SecRequestBodyInMemoryLimit value,
    // which was set in /dh/apache2/template/etc/mod_sec2/10_modsecurity_crs_10_config.conf
    // with the line
    // SecRequestBodyInMemoryLimit 131072
    // and caused the submission to be rejected no matter what the settings were for PHP or Wordpress.
    // The following had no effect, because they were actually already set within tolerances:
    // In the plugin, or in functions.php:
    // @ini_set( 'post_max_size', '64M');
    // in .htaccess:
    // php_value post_max_size 64M
    public function route_create($request) {
        if (!isset( $request['id'] ) ) {
            return new \WP_Error( 'rest_invalid', esc_html__( 'The id parameter is required.', 'my-text-domain' ), array( 'status' => 400 ) );
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

        if ( !function_exists( 'wp_handle_upload' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }

        $uploadedfile = $_FILES['route'];
        $upload_overrides = array( 'test_form' => false );

        $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

        if (!$movefile) {
            return new \WP_Error( 'rest_invalid', esc_html__( 'Could not create temporary file', 'my-text-domain' ), array( 'status' => 400 ) );
        }
        
        if (isset($movefile['error'])) {
            return new \WP_Error( 'rest_invalid', esc_html__( 'Temporary upload error: ' . $movefile['error'], 'my-text-domain' ), array( 'status' => 400 ) );
        }

        $raw_json = file_get_contents($movefile['file']);
        wp_delete_file($movefile['file']);

        // search and remove line breaks
        $json_concatenated = str_replace(array("\n","\r"),"",$raw_json); 
        $decoded_route = json_decode($json_concatenated, TRUE);
        if (!isset($decoded_route)) {
            $response = rest_ensure_response( 'JSON submitted for record ' . $request['id'] . ' appears to be invalid.' );
            $response->header( 'Access-Control-Allow-Origin', '*');
            return $response;

        }
        // Get ahold of the first value in the timestamp series
        if (!array_key_exists('t', $decoded_route)) {
            $response = rest_ensure_response( 'JSON submitted for record ' . $request['id'] . ' does not contain a timestamp array.' );
            $response->header( 'Access-Control-Allow-Origin', '*');
            return $response;
        }
        $start_time = $decoded_route['t'][0];
        $last_value = array_slice($decoded_route['t'], -1);
        $end_time = array_pop($last_value);

        $f = array();
        $f['route_id'] = $request['id'];
        $f['route_description'] = isset($request['name']) ? $request['name'] : '';
        $f['route_json'] = $json_concatenated;
        $f['route_start_time'] = $start_time;
        $f['route_end_time'] = $end_time;

        //return new \WP_Error( 'rest_invalid', esc_html__( 'Assembled route: ' . print_r($f, true), 'my-text-domain' ), array( 'status' => 400 ) );

        $one_row = ptws_get_route_record($f['route_id']);

        if ($one_row == null) {
            ptws_create_route_record($f);
            $response = rest_ensure_response( 'Record ' . $f['route_id'] . ' inserted.' );
        } else {
            ptws_update_route_record($f);
            $response = rest_ensure_response( 'Record ' . $f['route_id'] . ' updated.' );
        }
        $response->header( 'Access-Control-Allow-Origin', '*');
        return $response;
    }


    // Check if the requester has permission to access the API.
    // Currently there is no check; the API relies on a secret key send via HTTPS.
    // If we required that the requester be logged in as a current user, it would make the API harder to use in low-bandwidth situations.
    public function standard_permissions_check() {
        // Restrict endpoint to only users who have the edit_posts capability.
        //if ( ! current_user_can( 'edit_others_posts' ) ) {
        //    return new \WP_Error( 'rest_forbidden', esc_html__( 'OMG you can not view private data.', 'my-text-domain' ), array( 'status' => 401 ) );
        //}
        return true;
    }


    // Implementing the comment API 'get latest 50' method.
    public function comment_get_recent_unresolved($request)
    {
        if (!isset( $request['key'] ) ) {
            return new \WP_Error( 'rest_invalid', esc_html__( 'The key parameter is required.', 'my-text-domain' ), array( 'status' => 400 ) );
        }
        if (!get_option('ptws_route_api_secret')) {
            return new \WP_Error( 'rest_invalid', esc_html__( 'Route API secret is not set.', 'my-text-domain' ), array( 'status' => 400 ) );
        }
        if ($request['key'] != get_option('ptws_route_api_secret')) {
            return new \WP_Error( 'rest_invalid', esc_html__( 'The key parameter is incorrect.', 'my-text-domain' ), array( 'status' => 400 ) );
        }

        $recent_comments = ptws_get_unresolved_comments(50);
        if ($recent_comments == null) {
            return new \WP_Error('rest_invalid', esc_html__('Problem getting latest routes', 'my-text-domain'), array('status' => 400));
        }
        $response = rest_ensure_response( 'Results fetched.' );
        $response->header( 'Access-Control-Allow-Origin', '*');
        $response->set_data($recent_comments);
        return $response;
    }
}

?>