<?php

/**
 * Class ICClub_Settings
 * Settings page for plugin
 *
 * @since 1.0.0
 */

class  ICClub_Settings {

	/**
	 * Updated status
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	private $updated = false;

	/**
	 * ICClub_Settings constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ) );
		add_action( 'admin_menu', array( $this, 'add_link_to_menu' ), 10, 1 );

	}

	/**
	 * Load scripts
	 *
	 * @since  1.0.0
	 */
	public function add_scripts() {

		wp_enqueue_style(
			'icclub-styles',
			ICCLUB_PLUGIN_URL . 'assets/styles.css',
			array(),
			ICCLUB_VERSION
		);

	}

	/**
	 * Make link to settings page
	 *
	 * @since 1.0.0
	 */
	public function add_link_to_menu() {

		add_submenu_page(
			'options-general.php',
			__( 'Cred Club API', 'icclub' ),
			__( 'Cred Club API', 'icclub' ),
			'manage_options',
			'icclub',
			array( $this, 'display_page' )
		);

	}

	/**
	 * Render settings page in admin side
	 *
	 * @since 1.0.0
	 */
	public function display_page() {

		$this->update_data();

		$secret_key = get_option( 'icclub-secret-key' );
		?>
        <div class="icclub-settings">
            <h1><?php echo get_admin_page_title(); ?></h1>
			<?php if ( $this->updated ) { ?>
                <div class="updated"><?php _e( 'Secret key updated successfully', 'icclub' ); ?></div>
			<?php } ?>
            <form action="" method="post" name="save_icclub">
                <label for="secret-key">
                    <span>Secret key</span>
                    <input type="text" id="secret-key" name="secret_key" value="<?php esc_attr_e( $secret_key ); ?>">
                </label>
                <input type="submit" value="<?php _e( 'Update', 'icclub' ); ?>">
            </form>
        </div>
		<?php

	}

	/**
	 * Update new secret key
	 *
	 * @since 1.0.0
	 */
	private function update_data() {

		if ( isset( $_POST['secret_key'] ) ) {
			update_option( 'icclub-secret-key', sanitize_text_field( $_POST['secret_key'] ) );
			$this->updated = true;
		}

	}

}

function icclub_settings_runner() {

	return new ICClub_Settings();
}

icclub_settings_runner();