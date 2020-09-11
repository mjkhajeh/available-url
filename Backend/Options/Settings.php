<?php
namespace AvailableURL\Backend\Options;

use AvailableURL\Utils as Utils;

class Settings {
	PRIVATE $PREFIX = 'availableurl_';
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

		$settings = Utils::get_settings();
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
						<label for="<?php echo $this->PREFIX ?>administrator_status">
							<?php _e( "Administrators status", "availableurl" ) ?>
						</label>
					</th>

					<td>
						<label>
							<input type="checkbox" name="<?php echo $this->PREFIX ?>administrator_status" id="<?php echo $this->PREFIX ?>administrator_status" <?php checked( true, $settings['administrator_status'] ) ?>>
							<span><?php _e( "Check for active changes for administrators(Role and Users)", "availableurl" ) ?></span>
						</label>
					</td>
				</tr>

				<tr>
					<th>
						<label for="<?php echo $this->PREFIX ?>backend_redirect_to">
							<?php _e( "Default redirect location(Backend)", "availableurl" ) ?>
						</label>
					</th>

					<td>
						<input type="url" name="<?php echo $this->PREFIX ?>backend_redirect_to" id="<?php echo $this->PREFIX ?>backend_redirect_to" value="<?php echo $settings['backend_redirect_to'] ?>">
					</td>
				</tr>
				<?php do_action( "availableurl/settings/settings_row", $settings ) ?>
			</table>
			<?php submit_button() ?>
		</form>
		<?php
	}

	private function save() {
		if( !empty( $_POST ) ) {
			$administrator_status = false;
			if( isset( $_POST["{$this->PREFIX}administrator_status"] ) && $_POST["{$this->PREFIX}administrator_status"] ) {
				$administrator_status = true;
			}

			if( isset( $_POST["{$this->PREFIX}backend_redirect_to"] ) ) {
				$backend_redirect_to = esc_url( $_POST["{$this->PREFIX}backend_redirect_to"] );
				$backend_redirect_to = stripslashes( $backend_redirect_to );
				$backend_redirect_to = trailingslashit( $backend_redirect_to );
			}

			$settings = array(
				'administrator_status'	=> $administrator_status,
				'backend_redirect_to'	=> $backend_redirect_to
			);
			$settings = apply_filters( "availableurl/settings/saving_data", $settings );
			do_action( "availableurl/settings/save" );
			update_option( "availableurl", $settings );

			return true;
		}
		return false;
	}
}
Settings::get_instance();