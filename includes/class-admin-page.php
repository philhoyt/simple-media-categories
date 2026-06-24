<?php
/**
 * Tag Media admin page registration and React app asset loading.
 *
 * @package SimpleMediaCategories
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Media > Tag Media page and enqueues its React bundle.
 */
class SMC_Admin_Page {

	const MENU_SLUG = 'smc-tag-media';

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
	 * Register the Tag Media submenu under the Media menu.
	 */
	public function add_menu(): void {
		$this->hook_suffix = (string) add_submenu_page(
			'upload.php',
			__( 'Tag Media', 'simple-media-categories' ),
			__( 'Tag Media', 'simple-media-categories' ),
			'upload_files',
			self::MENU_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Render the React mount point.
	 */
	public function render(): void {
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'simple-media-categories' ) );
		}

		echo '<div class="wrap smc-tag-media"><div id="smc-tag-media-root"></div></div>';
	}

	/**
	 * Enqueue the admin app bundle and styles on the Tag Media screen only.
	 *
	 * The apiFetch REST root URL and nonce middleware are injected automatically
	 * by core because the bundle depends on the wp-api-fetch script handle.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( $hook_suffix !== $this->hook_suffix || '' === $this->hook_suffix ) {
			return;
		}

		$asset_file = SMC_DIR . 'build/admin.asset.php';
		$asset      = file_exists( $asset_file )
			? require $asset_file
			: array(
				'dependencies' => array( 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n', 'wp-data', 'wp-notices' ),
				'version'      => SMC_VERSION,
			);

		wp_enqueue_script(
			'smc-admin',
			SMC_URL . 'build/admin.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'smc-admin-app',
			SMC_URL . 'build/style-admin.css',
			array( 'wp-components' ),
			$asset['version']
		);

		wp_set_script_translations( 'smc-admin', 'simple-media-categories', SMC_DIR . 'languages' );
	}
}
