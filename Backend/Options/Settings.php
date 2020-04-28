<?php
namespace AvailableURL\Backend\Options;

use \AvailableURL\Includes\Classes\Functions as Functions;

class Settings {
	/**
	 * Creates or returns an instance of this class.
	 *
	 * @return	A single instance of this class.
	 */
	public static function get_instance() {
		static $instance = null;
		if($instance === null){
			$instance = new self;
		}
		return $instance;
	}

	private function __construct() {
		add_filter( "AvailableURL/Backend/Tabs", array( $this, "add_tab" ) );
	}

	public function add_tab( $tabs ) {
		$main_url = menu_page_url( "availableurl", false ); // Get url of the page
		
		$tabs['settings'] = array(
			'title'	=> __( "Settings" ),
			'url'	=> add_query_arg( "tab", "settings", $main_url ),
			'callback'	=> array( $this, "view" )
		);

		return $tabs;
	}

	public function view() {
		$update = $this->save();

		$functions = Functions::get_instance();
		$settings = $functions->get_settings();
		?>
		<h2><?php _e( "Settings" ) ?></h2>
		<?php if( $update ) { ?>
			<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"> 
				<p>
					<strong><?php _e( "Settings saved." ) ?></strong>
				</p>
				<button type="button" class="notice-dismiss">
					<span class="screen-reader-text"><?php _e( "Dismiss this notice." ) ?></span>
				</button>
			</div>
		<?php } ?>

		<form method="post" action="">
			<table class="form-table" style="width:auto">
				<tr>
					<th>
						<label for="availableurl_administrator_status">
							<?php _e( "Administrators status", "availableurl" ) ?>
						</label>
					</th>

					<td>
						<label>
							<input type="checkbox" name="availableurl_administrator_status" id="availableurl_administrator_status" <?php checked( true, $settings['administrator_status'] ) ?>>
							<span><?php _e( "Check for active changes for administrators(Role and Users)", "availableurl" ) ?></span>
						</label>
					</td>
				</tr>

				<tr>
					<th>
						<label for="availableurl_backend_redirect_to">
							<?php _e( "Default redirect location(Backend)", "availableurl" ) ?>
						</label>
					</th>

					<td>
						<input type="url" name="availableurl_backend_redirect_to" id="availableurl_backend_redirect_to" value="<?php echo $settings['backend_redirect_to'] ?>">
					</td>
				</tr>

				<tr>
					<th>
						<label for="availableurl_frontend_redirect_to">
							<?php _e( "Default redirect location(Frontend)", "availableurl" ) ?>
						</label>
					</th>

					<td>
						<input type="url" name="availableurl_frontend_redirect_to" id="availableurl_frontend_redirect_to" value="<?php echo $settings['frontend_redirect_to'] ?>">
					</td>
				</tr>
			</table>
			<?php submit_button() ?>
		</form>
		<?php
	}

	private function save() {
		if( !empty( $_POST ) ) {
			$administrator_status = false;
			if( isset( $_POST['availableurl_administrator_status'] ) && $_POST['availableurl_administrator_status'] ) {
				$administrator_status = true;
			}

			if( isset( $_POST['availableurl_backend_redirect_to'] ) && $_POST['availableurl_backend_redirect_to'] ) {
				$backend_redirect_to = esc_url( $_POST['availableurl_backend_redirect_to'] );
				$backend_redirect_to = stripslashes( $backend_redirect_to );
				$backend_redirect_to = trailingslashit( $backend_redirect_to );
			}

			if( isset( $_POST['availableurl_frontend_redirect_to'] ) && $_POST['availableurl_frontend_redirect_to'] ) {
				$frontend_redirect_to = esc_url( $_POST['availableurl_frontend_redirect_to'] );
				$frontend_redirect_to = stripslashes( $frontend_redirect_to );
				$frontend_redirect_to = trailingslashit( $frontend_redirect_to );
			}

			$settings = array(
				'administrator_status'	=> $administrator_status,
				'backend_redirect_to'	=> $backend_redirect_to,
				'frontend_redirect_to'	=> $frontend_redirect_to
			);
			update_option( "availableurl", $settings );

			return true;
		}
		return false;
	}
}
Settings::get_instance();