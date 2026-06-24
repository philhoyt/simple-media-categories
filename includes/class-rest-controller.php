<?php
/**
 * REST API controller for the media tagging workspace.
 *
 * Exposes the simple-media-categories/v1 namespace used by the Tag Media
 * admin page: term listing/creation, a filtered media listing, and a
 * verb-based bulk term assignment endpoint.
 *
 * @package SimpleMediaCategories
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers and handles the plugin's REST routes.
 */
class SMC_REST_Controller {

	const REST_NAMESPACE = 'simple-media-categories/v1';
	const TAXONOMY       = 'media_category';

	/**
	 * Hook route registration into the REST lifecycle.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register all routes for this controller.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/terms',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_terms' ),
					'permission_callback' => array( $this, 'can_read' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_term' ),
					'permission_callback' => array( $this, 'can_create_term' ),
					'args'                => array(
						'name'   => array(
							'type'     => 'string',
							'required' => true,
						),
						'parent' => array(
							'type'    => 'integer',
							'default' => 0,
						),
					),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/media',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_media' ),
				'permission_callback' => array( $this, 'can_read' ),
				'args'                => array(
					'term'     => array( 'type' => 'integer' ),
					'untagged' => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'search'   => array( 'type' => 'string' ),
					'mime'     => array( 'type' => 'string' ),
					'page'     => array(
						'type'    => 'integer',
						'default' => 1,
					),
					'per_page' => array(
						'type'    => 'integer',
						'default' => 40,
					),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/media/bulk',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'bulk_update' ),
				'permission_callback' => array( $this, 'can_read' ),
				'args'                => array(
					'ids'    => array(
						'type'     => 'array',
						'required' => true,
						'items'    => array( 'type' => 'integer' ),
					),
					'action' => array(
						'type'     => 'string',
						'required' => true,
						'enum'     => array( 'add', 'remove', 'set' ),
					),
					'terms'  => array(
						'type'    => 'array',
						'default' => array(),
						'items'   => array( 'type' => 'integer' ),
					),
				),
			)
		);
	}

	/**
	 * Read permission: any user who can manage the media library.
	 *
	 * @return bool
	 */
	public function can_read(): bool {
		return current_user_can( 'upload_files' );
	}

	/**
	 * Term-creation permission.
	 *
	 * @return bool
	 */
	public function can_create_term(): bool {
		return current_user_can( 'manage_categories' );
	}

	/**
	 * GET /terms — the full media_category tree with counts and display paths.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_terms() {
		$by_id = $this->all_terms_by_id();

		$data = array();
		foreach ( $by_id as $term ) {
			$data[] = $this->prepare_term( $term, $by_id );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * POST /terms — create a term under an optional parent.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_term( WP_REST_Request $request ) {
		$name   = sanitize_text_field( (string) $request['name'] );
		$parent = absint( $request['parent'] );

		if ( '' === $name ) {
			return new WP_Error( 'smc_invalid_name', __( 'Term name cannot be empty.', 'simple-media-categories' ), array( 'status' => 400 ) );
		}

		$args = $parent ? array( 'parent' => $parent ) : array();

		$result = wp_insert_term( $name, self::TAXONOMY, $args );

		if ( is_wp_error( $result ) ) {
			$result->add_data( array( 'status' => 400 ) );
			return $result;
		}

		$term     = get_term( $result['term_id'], self::TAXONOMY );
		$response = rest_ensure_response( $this->prepare_term( $term, $this->all_terms_by_id() ) );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * GET /media — paginated attachments matching the requested filters.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response
	 */
	public function get_media( WP_REST_Request $request ) {
		$per_page = min( 100, max( 1, (int) $request['per_page'] ) );
		$page     = max( 1, (int) $request['page'] );

		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$search = (string) $request['search'];
		if ( '' !== $search ) {
			$args['s'] = sanitize_text_field( $search );
		}

		$mime = (string) $request['mime'];
		if ( '' !== $mime ) {
			$args['post_mime_type'] = sanitize_text_field( $mime );
		}

		if ( $request['untagged'] ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => self::TAXONOMY,
					'operator' => 'NOT EXISTS',
				),
			);
		} elseif ( ! empty( $request['term'] ) ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => self::TAXONOMY,
					'field'    => 'term_id',
					'terms'    => absint( $request['term'] ),
				),
			);
		}

		/**
		 * Filter the WP_Query arguments used for the Tag Media listing.
		 *
		 * @param array           $args    Query arguments.
		 * @param WP_REST_Request $request The REST request.
		 */
		$args  = apply_filters( 'smc_rest_media_query_args', $args, $request );
		$query = new WP_Query( $args );

		$items = array();
		foreach ( $query->posts as $post ) {
			$items[] = $this->prepare_media( $post );
		}

		$response = rest_ensure_response( $items );
		$response->header( 'X-WP-Total', (int) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (int) $query->max_num_pages );

		return $response;
	}

	/**
	 * POST /media/bulk — add, remove, or set terms across many attachments.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function bulk_update( WP_REST_Request $request ) {
		$ids    = array_values( array_filter( array_map( 'absint', (array) $request['ids'] ) ) );
		$action = (string) $request['action'];
		$terms  = $this->filter_valid_terms( array_map( 'absint', (array) $request['terms'] ) );

		if ( empty( $ids ) ) {
			return new WP_Error( 'smc_no_ids', __( 'No media items provided.', 'simple-media-categories' ), array( 'status' => 400 ) );
		}

		if ( 'set' !== $action && empty( $terms ) ) {
			return new WP_Error( 'smc_no_terms', __( 'No valid terms provided.', 'simple-media-categories' ), array( 'status' => 400 ) );
		}

		$updated = array();
		$skipped = array();

		foreach ( $ids as $id ) {
			$post = get_post( $id );

			if ( ! $post || 'attachment' !== $post->post_type || ! current_user_can( 'edit_post', $id ) ) {
				$skipped[] = $id;
				continue;
			}

			if ( 'add' === $action ) {
				wp_add_object_terms( $id, $terms, self::TAXONOMY );
			} elseif ( 'remove' === $action ) {
				wp_remove_object_terms( $id, $terms, self::TAXONOMY );
			} else {
				wp_set_object_terms( $id, $terms, self::TAXONOMY );
			}

			$current        = wp_get_object_terms( $id, self::TAXONOMY, array( 'fields' => 'ids' ) );
			$updated[ $id ] = is_wp_error( $current ) ? array() : array_map( 'intval', $current );

			/**
			 * Fires after an attachment's media_category terms are updated.
			 *
			 * @param int    $id     Attachment ID.
			 * @param int[]  $terms  Term IDs involved in the operation.
			 * @param string $action One of add, remove, set.
			 */
			do_action( 'smc_media_terms_updated', $id, $terms, $action );
		}

		return rest_ensure_response(
			array(
				'updated' => $updated,
				'skipped' => $skipped,
				'counts'  => $this->term_counts(),
			)
		);
	}

	/**
	 * Build the REST payload for a single term.
	 *
	 * @param WP_Term   $term  The term.
	 * @param WP_Term[] $by_id All terms keyed by ID, for path building.
	 * @return array
	 */
	private function prepare_term( WP_Term $term, array $by_id ): array {
		$payload = array(
			'id'     => (int) $term->term_id,
			'name'   => $term->name,
			'slug'   => $term->slug,
			'parent' => (int) $term->parent,
			'count'  => (int) $term->count,
			'path'   => $this->build_path( $term, $by_id ),
		);

		/**
		 * Filter a term's REST payload.
		 *
		 * @param array   $payload The term payload.
		 * @param WP_Term $term    The term object.
		 */
		return apply_filters( 'smc_rest_term_response', $payload, $term );
	}

	/**
	 * Build a breadcrumb path ("Parent / Child") for a term.
	 *
	 * @param WP_Term   $term  The term.
	 * @param WP_Term[] $by_id All terms keyed by ID.
	 * @return string
	 */
	private function build_path( WP_Term $term, array $by_id ): string {
		$names  = array( $term->name );
		$parent = (int) $term->parent;
		$guard  = 0;

		while ( $parent && isset( $by_id[ $parent ] ) && $guard < 20 ) {
			array_unshift( $names, $by_id[ $parent ]->name );
			$parent = (int) $by_id[ $parent ]->parent;
			++$guard;
		}

		return implode( ' / ', $names );
	}

	/**
	 * Build the REST payload for a single attachment.
	 *
	 * @param WP_Post $post The attachment.
	 * @return array
	 */
	private function prepare_media( WP_Post $post ): array {
		$thumb = wp_get_attachment_image_url( $post->ID, 'thumbnail' );

		if ( ! $thumb ) {
			$thumb = wp_mime_type_icon( $post->ID );
		}

		$term_ids = wp_get_object_terms( $post->ID, self::TAXONOMY, array( 'fields' => 'ids' ) );

		return array(
			'id'        => (int) $post->ID,
			'title'     => get_the_title( $post ),
			'thumbnail' => $thumb ? $thumb : '',
			'mime'      => $post->post_mime_type,
			'terms'     => is_wp_error( $term_ids ) ? array() : array_map( 'intval', $term_ids ),
			'can_edit'  => current_user_can( 'edit_post', $post->ID ),
		);
	}

	/**
	 * Fetch all taxonomy terms keyed by term ID.
	 *
	 * @return WP_Term[]
	 */
	private function all_terms_by_id(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => false,
				'orderby'    => 'name',
			)
		);

		$by_id = array();
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$by_id[ (int) $term->term_id ] = $term;
			}
		}

		return $by_id;
	}

	/**
	 * Keep only term IDs that exist in this taxonomy.
	 *
	 * @param int[] $term_ids Candidate term IDs.
	 * @return int[]
	 */
	private function filter_valid_terms( array $term_ids ): array {
		$valid = array();
		foreach ( array_filter( $term_ids ) as $term_id ) {
			if ( get_term( $term_id, self::TAXONOMY ) instanceof WP_Term ) {
				$valid[] = (int) $term_id;
			}
		}

		return $valid;
	}

	/**
	 * Current term counts keyed by term ID, for client-side refresh.
	 *
	 * @return array<int,int>
	 */
	private function term_counts(): array {
		$counts = array();
		foreach ( $this->all_terms_by_id() as $term ) {
			$counts[ (int) $term->term_id ] = (int) $term->count;
		}

		return $counts;
	}
}
