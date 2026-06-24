<?php
/**
 * Plugin settings: registration, defaults, sanitization, and access helpers.
 *
 * The option is exposed through core's /wp/v2/settings REST endpoint via
 * show_in_rest, so the React settings screen reads and writes it directly.
 *
 * @package SimpleMediaCategories
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manages the smc_settings option.
 */
class SMC_Settings {

	const OPTION = 'smc_settings';
	const GROUP  = 'smc';

	/**
	 * Default settings.
	 *
	 * `auto_tag_post` defaults true to preserve the plugin's original
	 * always-on parent-post tagging; `auto_tag_mime` is opt-in.
	 *
	 * @return array{auto_tag_post: bool, auto_tag_mime: bool}
	 */
	public static function defaults(): array {
		return array(
			'auto_tag_post' => true,
			'auto_tag_mime' => false,
		);
	}

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_setting' ) );
	}

	/**
	 * Register the option with the Settings API and REST.
	 */
	public function register_setting(): void {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'object',
				'default'           => self::defaults(),
				'sanitize_callback' => array( $this, 'sanitize' ),
				'show_in_rest'      => array(
					'schema' => array(
						'type'                 => 'object',
						'properties'           => array(
							'auto_tag_post' => array( 'type' => 'boolean' ),
							'auto_tag_mime' => array( 'type' => 'boolean' ),
						),
						'additionalProperties' => false,
					),
				),
			)
		);
	}

	/**
	 * Sanitize the option to a strict boolean shape.
	 *
	 * @param mixed $value Raw option value.
	 * @return array{auto_tag_post: bool, auto_tag_mime: bool}
	 */
	public function sanitize( $value ): array {
		$value = is_array( $value ) ? $value : array();

		return array(
			'auto_tag_post' => ! empty( $value['auto_tag_post'] ),
			'auto_tag_mime' => ! empty( $value['auto_tag_mime'] ),
		);
	}

	/**
	 * Get the current settings merged with defaults.
	 *
	 * @return array{auto_tag_post: bool, auto_tag_mime: bool}
	 */
	public static function get(): array {
		return wp_parse_args( (array) get_option( self::OPTION, array() ), self::defaults() );
	}

	/**
	 * Whether an auto-tagging rule is enabled.
	 *
	 * @param string $rule Rule key without the `auto_tag_` prefix (e.g. 'post', 'mime').
	 * @return bool
	 */
	public static function is_enabled( string $rule ): bool {
		$settings = self::get();
		return ! empty( $settings[ 'auto_tag_' . $rule ] );
	}
}
