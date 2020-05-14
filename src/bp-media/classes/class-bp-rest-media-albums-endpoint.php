<?php
/**
 * BP REST: BP_REST_Media_Albums_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Media Albums endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Media_Albums_Endpoint extends WP_REST_Controller {

	/**
	 * BP_REST_Media_Endpoint endpoint
	 *
	 * @var string
	 */
	protected $media_endpoint;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace      = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base      = 'media/albums';
		$this->media_endpoint = new BP_REST_Media_Endpoint();
	}

	/**
	 * Register the component routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'A unique numeric ID for the album.', 'buddyboss' ),
						'type'        => 'integer',
						'required'    => true,
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

	}

	/**
	 * Retrieve Albums.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/media/albums Get Albums
	 * @apiName        GetBBAlbums
	 * @apiGroup       Media
	 * @apiDescription Retrieve Albums.
	 * @apiVersion     1.0.0
	 * @apiParam {Number} [page] Current page of the collection.
	 * @apiParam {Number} [per_page=10] Maximum number of items to be returned in result set.
	 * @apiParam {String} [search] Limit results to those matching a string.
	 * @apiParam {String=asc,desc} [order=desc] Order sort attribute ascending or descending.
	 * @apiParam {String=date_created,menu_order} [orderby=date_created] Order albums by which attribute.
	 * @apiParam {Number} [max] Maximum number of results to return.
	 * @apiParam {Number} [user_id] Limit result set to items created by a specific user (ID).
	 * @apiParam {Number} [group_id] A unique numeric ID for the Group.
	 * @apiParam {String=public,loggedin,friends,onlyme,grouponly} [privacy=public] The privacy of album.
	 * @apiParam {Array} [exclude] Ensure result set excludes specific IDs.
	 * @apiParam {Array} [include] Ensure result set includes specific IDs.
	 * @apiParam {Boolean} [count_total=true] Show total count or not.
	 */
	public function get_items( $request ) {
		$args = array(
			'page'        => $request['page'],
			'per_page'    => $request['per_page'],
			'sort'        => $request['order'],
			'order_by'    => $request['orderby'],
			'count_total' => $request['count_total'],
			'fields'      => 'all',
		);

		if ( ! empty( $request['search'] ) ) {
			$args['search_terms'] = $request['search'];
		}

		if ( ! empty( $request['max'] ) ) {
			$args['max'] = $request['max'];
		}

		if ( ! empty( $request['user_id'] ) ) {
			$args['user_id'] = $request['user_id'];
		}

		if ( ! empty( $request['group_id'] ) ) {
			$args['group_id'] = $request['group_id'];
		}

		if ( ! empty( $request['privacy'] ) ) {
			$args['privacy'] = $request['privacy'];
		}

		if ( ! empty( $request['exclude'] ) ) {
			$args['exclude'] = $request['exclude'];
		}

		if ( ! empty( $request['include'] ) ) {
			$args['album_ids'] = $request['include'];
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_media_albums_get_items_query_args', $args, $request );

		$medias = $this->assemble_response_data( $args );

		$retval = array();
		foreach ( $medias['albums'] as $album ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $album, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $medias['total'], $args['per_page'] );

		/**
		 * Fires after a list of members is fetched via the REST API.
		 *
		 * @param array            $media    Fetched Media Albums.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_media_albums_get_items', $medias, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to get all users.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 * @since 0.1.0
	 */
	public function get_items_permissions_check( $request ) {

		/**
		 * Filter the albums `get_items` permissions check.
		 *
		 * @param bool            $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_media_albums_get_items_permissions_check', true, $request );
	}

	/**
	 * Retrieve a single Album.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/media/albums/:id Get Album
	 * @apiName        GetBBAlbum
	 * @apiGroup       Media
	 * @apiDescription Retrieve a single Album.
	 * @apiVersion     1.0.0
	 * @apiParam {Number} id A unique numeric ID for the Album.
	 */
	public function get_item( $request ) {

		$medias = $this->assemble_response_data( array( 'album_ids' => array( $request['id'] ) ) );

		if ( empty( $medias['albums'] ) ) {
			return new WP_Error(
				'bp_rest_album_invalid_id',
				__( 'Invalid Album ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$retval = '';
		foreach ( $medias['albums'] as $album ) {
			$retval = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $album, $request )
			);
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a album is fetched via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_media_album_get_item', $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to get all users.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 * @since 0.1.0
	 */
	public function get_item_permissions_check( $request ) {
		$retval = true;
		$album  = new BP_Media_Album( $request['id'] );

		if ( empty( $album->id ) ) {
			$retval = new WP_Error(
				'bp_rest_album_invalid_id',
				__( 'Invalid Album ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		/**
		 * Filter the album `get_item` permissions check.
		 *
		 * @param bool            $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_media_album_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Create medias.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/media/albums Create Album
	 * @apiName        CreateBBAlbum
	 * @apiGroup       Media
	 * @apiDescription Create an Album.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {String} title New Album Title.
	 * @apiParam {String=public,loggedin,friends,onlyme,grouponly} [privacy=public] The privacy of album.
	 * @apiParam {Number} [group_id] A unique numeric ID for the Group.
	 * @apiParam {Number} [user_id] The ID for the author of the Album.
	 * @apiParam {Array} [upload_ids] Media specific IDs.
	 */
	public function create_item( $request ) {

		$args = array(
			'upload_ids' => $request['upload_ids'],
			'privacy'    => $request['privacy'],
			'title'      => $request['title'],
			'user_id'    => ( ! empty( $request['user_id'] ) ? (int) $request['user_id'] : get_current_user_id() ),
		);

		if ( empty( $request['title'] ) ) {
			return new WP_Error(
				'bp_rest_no_album_title_not_found',
				__( 'Sorry, you are not allowed to create a Album.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		if ( isset( $request['group_id'] ) && ! empty( $request['group_id'] ) ) {
			$args['group_id'] = $request['group_id'];
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_media_albums_create_items_query_args', $args, $request );

		$album = $this->bp_rest_create_media_album( $args );

		if ( is_wp_error( $album ) ) {
			return $album;
		}

		$medias = $this->assemble_response_data( array( 'album_ids' => $album['album_id'] ) );

		$retval = array(
			'created' => $album['created'],
			'error'   => $album['error'],
		);

		foreach ( $medias['albums'] as $album ) {
			$retval['album'] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $album, $request )
			);
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a Media is created via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_media_album_create_item', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to create a media.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function create_item_permissions_check( $request ) {
		$retval = true;

		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to create a media.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		/**
		 * Filter the Media `create_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_media_create_items_permissions_check', $retval, $request );
	}

	/**
	 * Update a single album.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {PATCH} /wp-json/buddyboss/v1/media/albums/:id Update Album
	 * @apiName        UpdateBBAlbum
	 * @apiGroup       Media
	 * @apiDescription Update a single Album.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the Album.
	 * @apiParam {String} [title] New Album Title.
	 * @apiParam {String=public,loggedin,friends,onlyme,grouponly} [privacy] The privacy of album.
	 */
	public function update_item( $request ) {

		$medias = $this->assemble_response_data( array( 'album_ids' => array( $request['id'] ) ) );

		if ( empty( $medias['albums'] ) ) {
			return new WP_Error(
				'bp_rest_album_invalid_id',
				__( 'Invalid Album ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$album = end( $medias['albums'] );

		$args = array(
			'id'       => $album->id,
			'title'    => $album->title,
			'privacy'  => $album->privacy,
			'user_id'  => $album->user_id,
			'group_id' => $album->group_id,
		);

		if ( isset( $request['title'] ) && ! empty( $request['title'] ) ) {
			$args['title'] = $request['title'];
		}

		if ( isset( $request['privacy'] ) && ! empty( $request['privacy'] ) && $album->privacy !== $request['privacy'] ) {
			$args['privacy'] = $request['privacy'];
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_media_albums_update_items_query_args', $args, $request );

		$album = $this->bp_rest_create_media_album( $args );

		if ( is_wp_error( $album ) ) {
			return $album;
		}

		$medias = $this->assemble_response_data( array( 'album_ids' => $album['album_id'] ) );

		$retval = array(
			'updated' => $album['updated'],
			'error'   => $album['error'],
		);

		foreach ( $medias['albums'] as $album ) {
			$retval['album'] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $album, $request )
			);
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a media is fetched via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_media_update_item', $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to for the user.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 * @since 0.1.0
	 */
	public function update_item_permissions_check( $request ) {
		$retval = true;

		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you need to be logged in to update this album.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$album = new BP_Media_Album( $request['id'] );

		if ( empty( $album->id ) ) {
			$retval = new WP_Error(
				'bp_rest_album_invalid_id',
				__( 'Invalid Album ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( true === $retval && ! bp_album_user_can_delete( $album ) ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to update this album.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		/**
		 * Filter the album `update_item` permissions check.
		 *
		 * @param bool            $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_media_album_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete a single album.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/media/albums/:id Delete Album
	 * @apiName        DeleteBBAlbum
	 * @apiGroup       Media
	 * @apiDescription Delete a single Album.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the Album.
	 */
	public function delete_item( $request ) {

		$medias = $this->assemble_response_data( array( 'album_ids' => array( $request['id'] ) ) );

		if ( empty( $medias['albums'] ) ) {
			return new WP_Error(
				'bp_rest_album_invalid_id',
				__( 'Invalid Album ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$previous = array();
		foreach ( $medias['albums'] as $album ) {
			$previous = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $album, $request )
			);
		}

		if ( ! bp_album_user_can_delete( $request['id'] ) ) {
			return WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to delete this album.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$status = bp_album_delete( array( 'id' => $request['id'] ) );

		// Build the response.
		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted'  => $status,
				'previous' => $previous,
			)
		);

		/**
		 * Fires after a media is fetched via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_media_get_item', $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to for the user.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 * @since 0.1.0
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = true;

		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you need to be logged in to delete this album.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$album = new BP_Media_Album( $request['id'] );

		if ( empty( $album->id ) ) {
			$retval = new WP_Error(
				'bp_rest_album_invalid_id',
				__( 'Invalid Album ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( true === $retval && ! bp_album_user_can_delete( $album ) ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to delete this album.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		/**
		 * Filter the album `delete_item` permissions check.
		 *
		 * @param bool            $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_media_album_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Get Albums.
	 *
	 * @param array|string $args All arguments and defaults are shared with BP_Media::get(),
	 *                           except for the following.
	 *
	 * @return array
	 */
	public function assemble_response_data( $args ) {

		// Fetch specific media items based on ID's.
		if ( isset( $args['album_ids'] ) && ! empty( $args['album_ids'] ) ) {
			return bp_album_get_specific( $args );

			// Fetch all activity items.
		} else {
			return bp_album_get( $args );
		}
	}

	/**
	 * Select the item schema arguments needed for the CREATABLE methods.
	 *
	 * @param string $method Optional. HTTP method of the request.
	 *
	 * @return array Endpoint arguments.
	 * @since 0.1.0
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$args = array();
		$key  = 'update';

		$args['title'] = array(
			'description'       => __( 'New Album Title.', 'buddyboss' ),
			'type'              => 'string',
			'required'          => true,
			'validate_callback' => 'rest_validate_request_arg',
		);

		$args['privacy'] = array(
			'description'       => __( 'A unique numeric ID for the Media Album.', 'buddyboss' ),
			'type'              => 'string',
			'default'           => 'public',
			'enum'              => array( 'public', 'loggedin', 'friends', 'onlyme', 'grouponly' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		if ( WP_REST_Server::EDITABLE === $method ) {
			$args['id']                  = array(
				'description' => __( 'A unique numeric ID for the album.', 'buddyboss' ),
				'type'        => 'integer',
				'required'    => true,
			);
			$args['title']['required']   = false;
			$args['privacy']['required'] = false;
		}

		if ( WP_REST_Server::CREATABLE === $method ) {
			$args['group_id'] = array(
				'description'       => __( 'A unique numeric ID for the Group.', 'buddyboss' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['user_id'] = array(
				'description'       => __( 'The ID for the author of the Album.', 'buddyboss' ),
				'default '          => get_current_user_id(),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['upload_ids'] = array(
				'description'       => __( 'Media specific IDs.', 'buddyboss' ),
				'default'           => array(),
				'type'              => 'array',
				'items'             => array( 'type' => 'integer' ),
				'sanitize_callback' => 'wp_parse_id_list',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$key = 'create';
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @param array  $args   Query arguments.
		 * @param string $method HTTP method of the request.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( "bp_rest_media_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Prepares activity data for return as an object.
	 *
	 * @param BP_Media_Album  $album   Album data.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $album, $request ) {
		$data = array(
			'id'            => $album->id,
			'user_id'       => $album->user_id,
			'group_id'      => $album->group_id,
			'date_created'  => $album->date_created,
			'title'         => $album->title,
			'privacy'       => $album->privacy,
			'media'         => $album->media,
			'user_email'    => $album->user_email,
			'user_nicename' => $album->user_nicename,
			'user_login'    => $album->user_login,
			'display_name'  => $album->display_name,
		);

		$response = rest_ensure_response( $data );

		/**
		 * Filter an activity value returned from the API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 * @param BP_Media         $media    The Media object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_media_album_prepare_value', $response, $request, $album );
	}


	/**
	 * Get the media album schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_media_albums',
			'type'       => 'object',
			'properties' => array(
				'id'            => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the Album.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'user_id'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The ID for the author of the Album.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'group_id'      => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the Group.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'date_created'  => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The date the media was created, in the site\'s timezone.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'title'         => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The Album title.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'privacy'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Privacy of the media.', 'buddyboss' ),
					'enum'        => array( 'public', 'loggedin', 'friends', 'onlyme', 'grouponly' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'media'         => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Album\'s media.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'object',
					'properties'  => array(
						'medias' => array(
							'context'     => array( 'embed', 'view', 'edit' ),
							'description' => __( 'The Album\'s Media.', 'buddyboss' ),
							'readonly'    => true,
							'type'        => 'object',
						),
					),
				),
				'user_email'    => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The user\'s email id to create a media.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'user_nicename' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The User\'s nice name to create a media.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'user_login'    => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The User\'s login name to create a media.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'display_name'  => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The User\'s display name to create a media.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
			),
		);

		$schema['properties']['media']['properties']['medias']['properties'] = $this->media_endpoint->get_item_schema()['properties'];

		/**
		 * Filters the media schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_media_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['order'] = array(
			'description'       => __( 'Order sort attribute ascending or descending.', 'buddyboss' ),
			'default'           => 'desc',
			'type'              => 'string',
			'enum'              => array( 'asc', 'desc' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['orderby'] = array(
			'description'       => __( 'Order albums by which attribute.', 'buddyboss' ),
			'default'           => 'date_created',
			'type'              => 'string',
			'enum'              => array( 'date_created', 'menu_order' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['max'] = array(
			'description'       => __( 'Maximum number of results to return', 'buddyboss' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['user_id'] = array(
			'description'       => __( 'Limit results to friends of a user.', 'buddyboss' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['group_id'] = array(
			'description'       => __( 'A unique numeric ID for the Group.', 'buddyboss' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['privacy'] = array(
			'description'       => __( 'The privacy of album.', 'buddyboss' ),
			'enum'              => array( 'public', 'loggedin', 'friends', 'onlyme', 'grouponly' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['include'] = array(
			'description'       => __( 'Ensure result set includes specific IDs.', 'buddyboss' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['exclude'] = array(
			'description'       => __( 'Ensure result set excludes specific IDs.', 'buddyboss' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['count_total'] = array(
			'description' => __( 'Show total count or not.', 'buddyboss' ),
			'default'     => true,
			'type'        => 'boolean',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_media_albums_collection_params', $params );
	}

	/**
	 * Create the Media Album.
	 *
	 * @param array $args Key value array of query var to query value.
	 *
	 * @return array|WP_Error
	 * @since 0.1.0
	 */
	public function bp_rest_create_media_album( $args ) {
		$upload_ids = ( ! empty( $args['upload_ids'] ) ? $args['upload_ids'] : '' );
		$privacy    = $args['privacy'];
		$title      = $args['title'];
		$user_id    = ( ! empty( $args['user_id'] ) ? (int) $args['user_id'] : get_current_user_id() );
		$group_id   = ( ! empty( $args['group_id'] ) ? (int) $args['group_id'] : false );
		$id         = ( ! empty( $args['id'] ) ? (int) $args['id'] : false );

		$album_id = bp_album_add(
			array(
				'id'         => $id,
				'title'      => $title,
				'privacy'    => $privacy,
				'group_id'   => $group_id,
				'user_id'    => $user_id,
				'error_type' => 'wp_error',
			)
		);

		if ( is_wp_error( $album_id ) || empty( $album_id ) ) {
			return new WP_Error(
				'bp_rest_media_album_creation_error',
				__( 'There is an error while creating album.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		$relval = array(
			'created'  => true,
			'error'    => false,
			'album_id' => $album_id,
		);

		if ( $id ) {
			unset( $relval['created'] );
			$relval['updated'] = true;
		}

		if ( ! empty( $upload_ids ) ) {
			$added_medias = $this->media_endpoint->bp_rest_create_media(
				array(
					'upload_ids' => $upload_ids,
					'privacy'    => $privacy,
					'privacy'    => $privacy,
					'group_id'   => $group_id,
					'album_id'   => $album_id,
					'user_id'    => $user_id,
				)
			);

			if ( is_wp_error( $added_medias ) ) {
				$relval['error'] = $added_medias;
			}
		}

		return $relval;
	}
}

