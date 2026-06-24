<?php
/**
 * Automatic media tagging on upload, and the shared logic used by the
 * retroactive "tag existing media" tool.
 *
 * Two independent rules, each gated by the smc_settings option:
 *  - post: assign a post-type term plus a post-specific child term when an
 *    attachment is uploaded to a public parent post.
 *  - mime: assign a broad file-type term (Images/Documents/Audio/Video/Other)
 *    nested under a "File Type" parent.
 *
 * @package SimpleMediaCategories
 */

defined( 'ABSPATH' ) || exit;

/**
 * Applies auto-tagging rules to attachments.
 */
class SMC_Auto_Tagger {

	const TAXONOMY         = 'media_category';
	const FILE_TYPE_PARENT = 'file-type';

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'add_attachment', array( $this, 'tag_attachment' ) );
	}

	/**
	 * Apply every enabled auto-tagging rule to an attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function tag_attachment( int $attachment_id ): void {
		if ( $this->is_enabled( 'post', $attachment_id ) ) {
			$this->tag_by_post( $attachment_id );
		}

		if ( $this->is_enabled( 'mime', $attachment_id ) ) {
			$this->tag_by_mime( $attachment_id );
		}
	}

	/**
	 * Whether a rule should run for this attachment.
	 *
	 * @param string $rule          Rule key ('post' or 'mime').
	 * @param int    $attachment_id Attachment ID.
	 * @return bool
	 */
	private function is_enabled( string $rule, int $attachment_id ): bool {
		$enabled = SMC_Settings::is_enabled( $rule );

		/**
		 * Filter whether an auto-tagging rule runs for an attachment.
		 *
		 * @param bool   $enabled       Whether the rule is enabled.
		 * @param string $rule          Rule key ('post' or 'mime').
		 * @param int    $attachment_id Attachment ID.
		 */
		return (bool) apply_filters( 'smc_auto_tag_enabled', $enabled, $rule, $attachment_id );
	}

	/**
	 * Assign a post-type term and a post-specific child term based on the
	 * attachment's public parent post.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function tag_by_post( int $attachment_id ): void {
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

		$type_term_id = $this->find_or_create_term(
			$post_type_obj->labels->singular_name,
			$parent->post_type,
			0
		);

		if ( ! $type_term_id ) {
			return;
		}

		$post_title   = '' !== $parent->post_title ? $parent->post_title : __( 'Untitled', 'simple-media-categories' );
		$post_term_id = $this->find_or_create_term(
			$post_title,
			$parent->post_type . '-' . $parent->ID,
			$type_term_id
		);

		$terms = $post_term_id ? array( $type_term_id, $post_term_id ) : array( $type_term_id );
		wp_add_object_terms( $attachment_id, $terms, self::TAXONOMY );
	}

	/**
	 * Assign a broad file-type term under the "File Type" parent.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function tag_by_mime( int $attachment_id ): void {
		$attachment = get_post( $attachment_id );

		if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
			return;
		}

		$key    = $this->mime_group( (string) $attachment->post_mime_type );
		$groups = $this->mime_groups();
		$label  = $groups[ $key ] ?? $groups['other'];

		$parent_id = $this->find_or_create_term( __( 'File Type', 'simple-media-categories' ), self::FILE_TYPE_PARENT, 0 );

		if ( ! $parent_id ) {
			return;
		}

		$child_id = $this->find_or_create_term( $label, self::FILE_TYPE_PARENT . '-' . $key, $parent_id );

		if ( $child_id ) {
			wp_add_object_terms( $attachment_id, array( $child_id ), self::TAXONOMY );
		}
	}

	/**
	 * Map a MIME type to a broad group key.
	 *
	 * @param string $mime The attachment MIME type.
	 * @return string One of the keys from mime_groups().
	 */
	public function mime_group( string $mime ): string {
		$key = 'other';

		if ( 0 === strpos( $mime, 'image/' ) ) {
			$key = 'images';
		} elseif ( 0 === strpos( $mime, 'audio/' ) ) {
			$key = 'audio';
		} elseif ( 0 === strpos( $mime, 'video/' ) ) {
			$key = 'video';
		} elseif ( 0 === strpos( $mime, 'text/' ) || 0 === strpos( $mime, 'application/' ) ) {
			$key = 'documents';
		}

		/**
		 * Filter the group key chosen for a MIME type.
		 *
		 * @param string $key  Group key.
		 * @param string $mime MIME type.
		 */
		$key = (string) apply_filters( 'smc_mime_group', $key, $mime );

		return isset( $this->mime_groups()[ $key ] ) ? $key : 'other';
	}

	/**
	 * The available file-type groups, keyed by slug suffix.
	 *
	 * @return array<string, string> Map of group key => display label.
	 */
	public function mime_groups(): array {
		$groups = array(
			'images'    => __( 'Images', 'simple-media-categories' ),
			'documents' => __( 'Documents', 'simple-media-categories' ),
			'audio'     => __( 'Audio', 'simple-media-categories' ),
			'video'     => __( 'Video', 'simple-media-categories' ),
			'other'     => __( 'Other', 'simple-media-categories' ),
		);

		/**
		 * Filter the available file-type groups.
		 *
		 * @param array<string, string> $groups Map of group key => label.
		 */
		return apply_filters( 'smc_mime_groups', $groups );
	}

	/**
	 * Find a term by slug or create it.
	 *
	 * @param string $name      Term name (used only on creation).
	 * @param string $slug      Term slug (the stable identifier).
	 * @param int    $parent_id Parent term ID (0 for top level).
	 * @return int Term ID, or 0 on failure.
	 */
	private function find_or_create_term( string $name, string $slug, int $parent_id ): int {
		$existing = get_term_by( 'slug', $slug, self::TAXONOMY );

		if ( $existing instanceof WP_Term ) {
			return (int) $existing->term_id;
		}

		$args = array( 'slug' => $slug );
		if ( $parent_id ) {
			$args['parent'] = $parent_id;
		}

		$result = wp_insert_term( $name, self::TAXONOMY, $args );

		return is_wp_error( $result ) ? 0 : (int) $result['term_id'];
	}
}
