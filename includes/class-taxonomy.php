<?php
/**
 * Taxonomy registration, hooks, and asset enqueueing.
 *
 * @package SimpleMediaCategories
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manages the media_category taxonomy and all associated admin behaviour.
 */
class SMC_Taxonomy {

	/**
	 * Hook into WordPress.
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_taxonomy' ), 0 );
		add_action( 'admin_menu', array( $this, 'add_submenu' ) );
		add_action( 'restrict_manage_posts', array( $this, 'render_list_filter' ) );
		add_action( 'parse_query', array( $this, 'filter_query' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'attachment_fields_to_edit', array( $this, 'attachment_fields' ), 10, 2 );
		add_action( 'wp_ajax_save-attachment-compat', array( $this, 'save_attachment_compat' ), 0 );
		add_filter( 'ajax_query_attachments_args', array( $this, 'filter_ajax_query' ) );
		add_action( 'add_attachment', array( $this, 'auto_assign_post_type_term' ) );
		add_filter( 'bulk_actions-upload', array( $this, 'register_bulk_action' ) );
		add_filter( 'handle_bulk_actions-upload', array( $this, 'handle_bulk_action' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'render_bulk_edit_form' ) );
		add_action( 'admin_init', array( $this, 'process_bulk_edit_form' ) );
	}

	/**
	 * Register the media_category taxonomy on the attachment post type.
	 */
	public function register_taxonomy() {
		register_taxonomy(
			'media_category',
			'attachment',
			array(
				'label'                 => __( 'Media Categories', 'simple-media-categories' ),
				'hierarchical'          => true,
				'public'                => false,
				'publicly_queryable'    => false,
				'show_ui'               => true,
				'show_in_menu'          => false,
				'show_in_nav_menus'     => false,
				'show_in_rest'          => true,
				'show_admin_column'     => true,
				'rewrite'               => false,
				'query_var'             => false,
				'update_count_callback' => array( __CLASS__, 'update_attachment_term_count' ),
			)
		);
	}

	/**
	 * Custom term count callback that only counts attachment post types.
	 *
	 * WordPress skips _update_post_term_count() for non-public taxonomies, so
	 * this ensures term counts stay accurate for the media library.
	 *
	 * @param int[]       $terms    Array of term taxonomy IDs to update.
	 * @param WP_Taxonomy $taxonomy The taxonomy object.
	 */
	public static function update_attachment_term_count( array $terms, WP_Taxonomy $taxonomy ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		global $wpdb;

		foreach ( $terms as $term_id ) {
			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*)
					FROM {$wpdb->term_relationships}
					INNER JOIN {$wpdb->posts}
						ON {$wpdb->term_relationships}.object_id = {$wpdb->posts}.ID
					WHERE {$wpdb->posts}.post_status = 'inherit'
					AND {$wpdb->posts}.post_type = 'attachment'
					AND {$wpdb->term_relationships}.term_taxonomy_id = %d",
					$term_id
				)
			);

			$wpdb->update(
				$wpdb->term_taxonomy,
				array( 'count' => $count ),
				array( 'term_taxonomy_id' => $term_id )
			);
		}
	}

	/**
	 * Register a Categories submenu under the Media admin menu.
	 */
	public function add_submenu(): void {
		add_submenu_page(
			'upload.php',
			__( 'Media Categories', 'simple-media-categories' ),
			__( 'Categories', 'simple-media-categories' ),
			'manage_categories',
			'edit-tags.php?taxonomy=media_category&post_type=attachment'
		);
	}

	/**
	 * Render the category filter dropdown in the media list view.
	 */
	public function render_list_filter() {
		global $pagenow;

		if ( 'upload.php' !== $pagenow ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$selected = isset( $_GET['media_category'] ) ? sanitize_text_field( wp_unslash( $_GET['media_category'] ) ) : '';

		wp_dropdown_categories(
			array(
				'taxonomy'        => 'media_category',
				'name'            => 'media_category',
				'show_option_all' => __( 'All categories', 'simple-media-categories' ),
				'hide_empty'      => false,
				'hierarchical'    => true,
				'orderby'         => 'name',
				'selected'        => $selected,
				'show_count'      => true,
				'walker'          => new SMC_Walker_Filter(),
				'value'           => 'slug',
			)
		);
	}

	/**
	 * Apply the selected category filter to the media list-view query.
	 *
	 * @param WP_Query $query The current WP_Query instance.
	 */
	public function filter_query( WP_Query $query ) {
		global $pagenow;

		if ( ! is_admin() || 'upload.php' !== $pagenow ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['media_category'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$slug = sanitize_text_field( wp_unslash( $_GET['media_category'] ) );

		$query->query_vars['tax_query'] = array(
			array(
				'taxonomy' => 'media_category',
				'field'    => 'slug',
				'terms'    => $slug,
			),
		);
	}

	/**
	 * Enqueue CSS and the grid-view filter script on screens that load media-editor.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_assets( string $hook_suffix ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		if ( ! wp_script_is( 'media-editor', 'enqueued' ) ) {
			return;
		}

		wp_enqueue_style(
			'smc-admin',
			SMC_URL . 'assets/css/admin.css',
			array(),
			SMC_VERSION
		);

		$this->enqueue_grid_filter_script();
	}

	/**
	 * Add "Edit Categories" to the media list bulk actions dropdown.
	 *
	 * @param array $actions Existing bulk actions.
	 * @return array
	 */
	public function register_bulk_action( array $actions ): array {
		$actions['smc_edit_categories'] = __( 'Edit Categories', 'simple-media-categories' );
		return $actions;
	}

	/**
	 * Handle the "Edit Categories" bulk action by redirecting to the category form.
	 *
	 * @param string $redirect_url The redirect URL after bulk action handling.
	 * @param string $action       The current bulk action key.
	 * @param int[]  $post_ids     Array of selected attachment IDs.
	 * @return string
	 */
	public function handle_bulk_action( string $redirect_url, string $action, array $post_ids ): string {
		if ( 'smc_edit_categories' !== $action ) {
			return $redirect_url;
		}

		return add_query_arg(
			array(
				'smc_bulk_cats' => '1',
				'ids'           => array_map( 'absint', $post_ids ),
			),
			admin_url( 'upload.php' )
		);
	}

	/**
	 * Render the bulk category edit form (or success notice) as an admin notice.
	 */
	public function render_bulk_edit_form(): void {
		global $pagenow;

		if ( 'upload.php' !== $pagenow ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['smc_bulk_updated'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$count = absint( $_GET['smc_bulk_updated'] );
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				sprintf(
					/* translators: %d: number of attachments updated */
					esc_html( _n( 'Categories updated for %d attachment.', 'Categories updated for %d attachments.', $count, 'simple-media-categories' ) ),
					absint( $count )
				)
			);
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['smc_bulk_cats'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post_ids = isset( $_GET['ids'] ) ? array_map( 'absint', (array) wp_unslash( $_GET['ids'] ) ) : array();

		if ( ! $post_ids ) {
			return;
		}

		$count = count( $post_ids );
		?>
		<div class="notice notice-info smc-bulk-edit-notice">
			<form method="post">
				<?php wp_nonce_field( 'smc_bulk_edit', 'smc_bulk_nonce' ); ?>
				<?php foreach ( $post_ids as $id ) : ?>
					<input type="hidden" name="smc_post_ids[]" value="<?php echo absint( $id ); ?>">
				<?php endforeach; ?>
				<p>
					<strong>
					<?php
					printf(
						/* translators: %d: number of selected attachments */
						esc_html( _n( 'Set categories for %d attachment:', 'Set categories for %d attachments:', $count, 'simple-media-categories' ) ),
						absint( $count )
					);
					?>
					</strong>
				</p>
				<ul class="cat-checklist media_category-checklist">
					<?php wp_terms_checklist( 0, array( 'taxonomy' => 'media_category' ) ); ?>
				</ul>
				<p>
					<input type="submit" name="smc_bulk_submit" class="button button-primary" value="<?php esc_attr_e( 'Update Categories', 'simple-media-categories' ); ?>">
					<a href="<?php echo esc_url( admin_url( 'upload.php' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'simple-media-categories' ); ?></a>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Process the bulk category edit form submission.
	 */
	public function process_bulk_edit_form(): void {
		if ( empty( $_POST['smc_bulk_submit'] ) ) {
			return;
		}

		if ( ! isset( $_POST['smc_bulk_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['smc_bulk_nonce'] ) ), 'smc_bulk_edit' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'simple-media-categories' ) );
		}

		$post_ids = isset( $_POST['smc_post_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['smc_post_ids'] ) ) : array();

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$term_ids = isset( $_POST['tax_input']['media_category'] )
			? array_map( 'absint', (array) wp_unslash( $_POST['tax_input']['media_category'] ) )
			: array();

		$updated = 0;
		foreach ( $post_ids as $post_id ) {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}
			wp_add_object_terms( $post_id, $term_ids, 'media_category' );
			++$updated;
		}

		wp_safe_redirect( add_query_arg( 'smc_bulk_updated', $updated, admin_url( 'upload.php' ) ) );
		exit;
	}

	/**
	 * Register, localise, and enqueue the grid-view toolbar filter script.
	 */
	private function enqueue_grid_filter_script() {
		$asset_file = SMC_DIR . 'build/index.asset.php';
		$asset      = file_exists( $asset_file )
			? require $asset_file
			: array(
				'dependencies' => array(),
				'version'      => SMC_VERSION,
			);

		$dependencies = array_unique( array_merge( $asset['dependencies'], array( 'media-views' ) ) );

		wp_enqueue_script(
			'smc-media-views',
			SMC_URL . 'build/index.js',
			$dependencies,
			$asset['version'],
			true
		);

		wp_add_inline_script(
			'smc-media-views',
			'var smcTaxonomies = ' . wp_json_encode( $this->build_taxonomy_data() ) . ';',
			'before'
		);
	}

	/**
	 * Build the smcTaxonomies data structure for the grid-view JS filter.
	 *
	 * @return array
	 */
	private function build_taxonomy_data(): array {
		$terms_html = wp_dropdown_categories(
			array(
				'taxonomy'     => 'media_category',
				'hide_empty'   => false,
				'hierarchical' => true,
				'orderby'      => 'name',
				'show_count'   => false,
				'walker'       => new SMC_Walker_Grid_Filter(),
				'value'        => 'id',
				'echo'         => false,
			)
		);

		// Strip the <select> wrapper added by wp_dropdown_categories().
		// wp_dropdown_categories() adds "\n" after the opening tag, so trim whitespace
		// AND leading commas together — ltrim( ..., ',' ) alone would miss the newline.
		$json_fragment = preg_replace( array( '/<select([^>]*)>/', '/<\/select>/' ), '', $terms_html );
		$json_fragment = trim( (string) $json_fragment, " \t\n\r\0\x0B," );

		$term_list = array();
		if ( ! empty( $json_fragment ) ) {
			$decoded = json_decode( '[' . $json_fragment . ']', true );
			if ( is_array( $decoded ) ) {
				$term_list = $decoded;
			}
		}

		return array(
			'media_category' => array(
				'list_title'   => __( 'All categories', 'simple-media-categories' ),
				'filter_label' => __( 'Filter by Category', 'simple-media-categories' ),
				'term_list'    => $term_list,
			),
		);
	}

	/**
	 * Add a category checklist to the attachment fields in the grid-view sidebar.
	 *
	 * @param array   $form_fields Array of form fields.
	 * @param WP_Post $post        The attachment post object.
	 * @return array
	 */
	public function attachment_fields( array $form_fields, WP_Post $post ): array {
		$taxonomy = get_taxonomy( 'media_category' );

		if ( ! $taxonomy || ! $taxonomy->show_ui ) {
			return $form_fields;
		}

		ob_start();
		wp_terms_checklist(
			$post->ID,
			array(
				'taxonomy' => 'media_category',
				'walker'   => new SMC_Walker_Checklist(),
			)
		);
		$checklist = ob_get_clean();

		$form_fields['media_category'] = array(
			'label'         => $taxonomy->labels->name,
			'input'         => 'html',
			'html'          => '<ul class="term-list">' . $checklist . '</ul>',
			'show_in_edit'  => false,
			'show_in_modal' => true,
		);

		return $form_fields;
	}

	/**
	 * Save media_category term assignments from the attachment-compat AJAX handler.
	 *
	 * Runs at priority 0 so term data is saved before WordPress sends its response
	 * via the default handler at priority 10.
	 */
	public function save_attachment_compat() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$id = isset( $_REQUEST['id'] ) ? absint( $_REQUEST['id'] ) : 0;

		if ( ! $id ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $id ) ) {
			return;
		}

		$post = get_post( $id );

		if ( ! $post || 'attachment' !== $post->post_type ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw      = isset( $_REQUEST['tax_input']['media_category'] ) ? (array) wp_unslash( $_REQUEST['tax_input']['media_category'] ) : array();
		$slugs    = array_map( 'sanitize_text_field', array_keys( $raw ) );
		$term_ids = array();

		foreach ( $slugs as $slug ) {
			$term = get_term_by( 'slug', $slug, 'media_category' );
			if ( $term instanceof WP_Term ) {
				$term_ids[] = $term->term_id;
			}
		}

		wp_set_object_terms( $id, $term_ids, 'media_category' );
	}

	/**
	 * Auto-assign a media_category term matching the parent post's post type
	 * and a child term for the specific parent post when an attachment is uploaded.
	 *
	 * @param int $attachment_id The newly inserted attachment ID.
	 */
	public function auto_assign_post_type_term( int $attachment_id ): void {
		$attachment = get_post( $attachment_id );

		if ( ! $attachment || ! $attachment->post_parent ) {
			return;
		}

		$parent = get_post( $attachment->post_parent );

		if ( ! $parent ) {
			return;
		}

		$post_type_obj = get_post_type_object( $parent->post_type );

		if ( ! $post_type_obj || ! $post_type_obj->public ) {
			return;
		}

		$type_label = $post_type_obj->labels->singular_name;
		$type_slug  = $parent->post_type;

		$type_term = get_term_by( 'slug', $type_slug, 'media_category' );

		if ( ! $type_term ) {
			$result = wp_insert_term( $type_label, 'media_category', array( 'slug' => $type_slug ) );

			if ( is_wp_error( $result ) ) {
				return;
			}

			$type_term_id = $result['term_id'];
		} else {
			$type_term_id = $type_term->term_id;
		}

		$post_slug = $type_slug . '-' . $parent->ID;
		$post_term = get_term_by( 'slug', $post_slug, 'media_category' );

		if ( ! $post_term ) {
			$post_name = '' !== $parent->post_title ? $parent->post_title : __( 'Untitled', 'simple-media-categories' );
			$result    = wp_insert_term(
				$post_name,
				'media_category',
				array(
					'slug'   => $post_slug,
					'parent' => $type_term_id,
				)
			);

			if ( is_wp_error( $result ) ) {
				wp_add_object_terms( $attachment_id, $type_term_id, 'media_category' );
				return;
			}

			$post_term_id = $result['term_id'];
		} else {
			$post_term_id = $post_term->term_id;
		}

		wp_add_object_terms( $attachment_id, array( $type_term_id, $post_term_id ), 'media_category' );
	}

	/**
	 * Apply the media_category filter to AJAX media library queries.
	 *
	 * @param array $query The query arguments array.
	 * @return array
	 */
	public function filter_ajax_query( array $query ): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_REQUEST['query']['media_category'] ) ) {
			return $query;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$term_id = absint( $_REQUEST['query']['media_category'] );

		if ( ! $term_id ) {
			return $query;
		}

		$query['tax_query'] = array(
			array(
				'taxonomy' => 'media_category',
				'field'    => 'term_id',
				'terms'    => $term_id,
			),
		);

		return $query;
	}
}
