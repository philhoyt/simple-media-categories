<?php
/**
 * Settings admin page: registration and React app asset loading.
 *
 * @package SimpleMediaCategories
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers Media > Settings and enqueues its React bundle.
 */
class SMC_Settings_Page {

	const MENU_SLUG = 'smc-settings';

	/**
	 * Page hook suffix returned by add_submenu_page(), used to scope assets.
	 *
	 * @var string
	 */
	private $hook_suffix = '';

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the Settings submenu under the Media menu.
	 */
	public function add_menu(): void {
		$this->hook_suffix = (string) add_submenu_page(
			'upload.php',
			__( 'Media Category Settings', 'simple-media-categories' ),
			__( 'Settings', 'simple-media-categories' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Render the React mount point.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'simple-media-categories' ) );
		}

		echo '<div class="wrap smc-settings-page"><div id="smc-settings-root"></div></div>';
	}

	/**
	 * Enqueue the settings app bundle on the Settings screen only.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( $hook_suffix !== $this->hook_suffix || '' === $this->hook_suffix ) {
			return;
		}

		$asset_file = SMC_DIR . 'build/settings.asset.php';
		$asset      = file_exists( $asset_file )
			? require $asset_file
			: array(
				'dependencies' => array( 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n', 'wp-data', 'wp-notices' ),
				'version'      => SMC_VERSION,
			);

		wp_enqueue_script(
			'smc-settings',
			SMC_URL . 'build/settings.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'smc-settings-app',
			SMC_URL . 'build/style-settings.css',
			array( 'wp-components' ),
			$asset['version']
		);

		wp_set_script_translations( 'smc-settings', 'simple-media-categories', SMC_DIR . 'languages' );
	}
}
