<?php
namespace AvailableURL\Backend\Options;

use AvailableURL\Utils as Utils;

class Roles {
	PRIVATE $PREFIX = "availableurl_";
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
		
		$tabs['roles'] = array(
			'title'	=> __( "Roles", "availableurl" ),
			'url'	=> add_query_arg( "tab", "roles", $main_url ),
			'callback'	=> array( $this, "view" )
		);

		return $tabs;
	}

	public function view() {
		$main_url = menu_page_url( "availableurl", false ); // Get url of the page
		$main_url = add_query_arg( "tab", "roles", $main_url ); // Add tab to main url
		
		global $wp_roles;
		$roles = $wp_roles->get_names(); // Get name and id of roles

		$default_role = array_keys( $roles )[0];

		$active_role = $default_role;
		if( isset( $_GET['role'] ) && $_GET['role'] ) {
			$active_role = sanitize_text_field( $_GET['role'] );
		}

		$this->save( $active_role );

		// Get options of active role
		$urls = Utils::get_data( $active_role, 'role' );

		$url_settings = Utils::get_url_settings();
		?>
		<h2><?php _e( "Roles", "availableurl" ) ?></h2>
		<h3><?php echo translate_user_role( $roles[$active_role] ) ?></h3>

		<table class="form-table">
			<tr>
				<th>
					<label for="<?php echo $this->PREFIX ?>select_role">
						<?php _e( "Select role", "availableurl" ) ?>
					</label>
				</th>

				<td>
					<select id="<?php echo $this->PREFIX ?>select_options" class="availableurl_select">
						<?php
						foreach( $roles as $role_id => $role_name ) {
							$role_url = add_query_arg( "role", $role_id, $main_url );
							?>
							<option value="<?php echo $role_url ?>" <?php selected( $role_id, $active_role ) ?>><?php echo translate_user_role( $role_name ) ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>
		</table>

		<hr>
		<div id="availableurl_templates">
			<?php
			$template_type = "role";
			include_once( AVAILABLEURL_DIR . "Includes/Templates/url_row.php" );
			?>
		</div>
		<form method="post" action="">
			<table class="form-table" id="<?php echo $this->PREFIX ?>table_urls">
				<thead>
					<tr>
						<th><?php _e( "Title" ) ?></th>
						<th><?php _e( "URL", "availableurl" ) ?></th>
						<th><?php _e( "Settings" ) ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach( $urls as $id => $data ) { ?>
						<tr class="<?php echo $this->PREFIX ?>role_url_row" id="<?php echo $this->PREFIX ?>role_url_<?php echo $id ?>" data-id="<?php echo $id ?>">
							<td>
								<input type="text" name="<?php echo $this->PREFIX ?>title[]" id="<?php echo $this->PREFIX ?>title_<?php echo $id ?>" value="<?php echo $data['title'] ?>">
							</td>

							<td>
								<input type="url" name="<?php echo $this->PREFIX ?>url[]" id="<?php echo $this->PREFIX ?>url_<?php echo $id ?>" placeholder="<?php _e( "http://... or https://...", 'availableurl' ) ?>" value="<?php echo $data['url'] ?>">
							</td>

							<td>
								<select name="<?php echo $this->PREFIX ?>settings_<?php echo $id ?>[]" id="<?php echo $this->PREFIX ?>settings_<?php echo $id ?>" class="availableurl_select" data-placeholder="<?php _e( "Select settings", "availableurl" ) ?>" multiple data-width="100%">
									<?php
									if( $url_settings ) {
										foreach( $url_settings as $setting_id => $setting_data ) {
											if( empty( $data['settings'][$setting_id] ) ) {
												$data['settings'][$setting_id] = $setting_data['default'];
											}
											?>
											<option value="<?php echo $setting_id ?>" <?php selected( true, $data['settings'][$setting_id] ) ?>><?php echo $setting_data['title'] ?></option>
											<?php
										}
									}
									?>
								</select>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
			<button class="button" id="<?php echo $this->PREFIX ?>add_url">
				<?php _e( "+ Add URL", "availableurl" ) ?>
			</button>
			<?php submit_button() ?>
		</form>
		<?php
	}

	private function save( $active_role ) {
		if( !empty( $_POST ) ) {
			if( isset( $_POST["{$this->PREFIX}url"] ) && !empty( $_POST["{$this->PREFIX}url"] ) ) {
				$urls = $_POST["{$this->PREFIX}url"];

				$result = array();
				foreach( $urls as $index => $url ) {
					if( $url ) {
						$index	= sanitize_text_field( $index );
						$url	= esc_url( $url );
						$url	= stripslashes( $url );
						
						// Get title
						$title = "";
						if( isset( $_POST["{$this->PREFIX}title"][$index] ) && $_POST["{$this->PREFIX}title"][$index] ) {
							$title	= sanitize_text_field( $_POST["{$this->PREFIX}title"][$index] );
						}

						// Get setting data
						$settings = array();
						if( !empty( $_POST["{$this->PREFIX}settings_{$index}"] ) ) {
							foreach( $_POST["{$this->PREFIX}settings_{$index}"] as $setting_name ) {
								$settings[$setting_name] = true;
							}
						}
						
						$result[] = array(
							'title'		=> $title,
							'url'		=> $url,
							'settings'	=> $settings
						);
					}
				}
				update_option( "{$this->PREFIX}role_{$active_role}", $result );

				return true;
			}
		}
		return false;
	}
}
Roles::get_instance();