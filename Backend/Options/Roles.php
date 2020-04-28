<?php
namespace AvailableURL\Backend\Options;

use \AvailableURL\Includes\Classes\Functions as Functions;

class Roles {
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
		$functions = Functions::get_instance();

		$main_url = menu_page_url( "availableurl", false ); // Get url of the page
		$main_url = add_query_arg( "tab", "roles", $main_url ); // Add tab to main url
		
		global $wp_roles;
		$roles = $wp_roles->get_names(); // Get name and id of roles

		$default_role = array_key_first( $roles );

		$active_role = $default_role;
		if( isset( $_GET['role'] ) && $_GET['role'] ) {
			$active_role = sanitize_text_field( $_GET['role'] );
		}

		$this->save( $active_role );

		// Get options of active role
		$urls = $functions->get_role_options( $active_role );
		?>
		<h2><?php _e( "Roles", "availableurl" ) ?></h2>
		<h3><?php echo $roles[$active_role] ?></h3>

		<table class="form-table">
			<tr>
				<th>
					<label for="availableurl_select_role">
						<?php _e( "Select role", "availableurl" ) ?>
					</label>
				</th>

				<td>
					<select id="availableurl_select_options" class="availableurl_select">
						<?php
						foreach( $roles as $role_id => $role_name ) {
							$role_url = add_query_arg( "role", $role_id, $main_url );
							?>
							<option value="<?php echo $role_url ?>" <?php selected( $role_id, $active_role ) ?>><?php echo $role_name ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>
		</table>

		<hr>

		<form method="post" action="">
			<table class="form-table" id="availableurl_table_urls">
				<thead>
					<tr>
						<th><?php _e( "Title" ) ?></th>
						<th><?php _e( "URL", "availableurl" ) ?></th>
						<th><?php _e( "Settings" ) ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach( $urls as $id => $data ) { ?>
						<tr class="availableurl_role_url_row" id="availableurl_role_url_<?php echo $id ?>" data-id="<?php echo $id ?>">
							<td>
								<input type="text" name="availableurl_title[]" id="availableurl_title_<?php echo $id ?>" value="<?php echo $data['title'] ?>">
							</td>

							<td>
								<input type="url" name="availableurl_url[]" id="availableurl_url_<?php echo $id ?>" placeholder="<?php _e( "http://... or https://...", 'availableurl' ) ?>" value="<?php echo $data['url'] ?>">
							</td>

							<td>
								<select name="availableurl_settings_<?php echo $id ?>[]" id="availableurl_settings_<?php echo $id ?>" class="availableurl_select" data-placeholder="<?php _e( "Select settings", "availableurl" ) ?>" multiple data-width="100%">
									<option value="frontend" <?php selected( true, $data['settings']['frontend'] ) ?>><?php _e( "It's a frontend URL", "availableurl" ) ?></option>
									<option value="exactly_url" <?php selected( true, $data['settings']['exactly_url'] ) ?>><?php _e( "Exactly this address", "availableurl" ) ?></option>
								</select>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
			<button class="button" id="availableurl_add_url">
				<?php _e( "+ Add URL", "availableurl" ) ?>
			</button>
			<?php submit_button() ?>
		</form>
		<?php
	}

	private function save( $active_role ) {
		if( !empty( $_POST ) ) {
			if( isset( $_POST['availableurl_url'] ) && !empty( $_POST['availableurl_url'] ) ) {
				$functions = Functions::get_instance();

				$urls = $_POST['availableurl_url'];

				$result = array();
				foreach( $urls as $index => $url ) {
					if( $url ) {
						$index	= sanitize_text_field( $index );
						$url	= esc_url( $url );
						$url	= stripslashes( $url );
						
						// Get title
						$title 		= "";
						if( isset( $_POST['availableurl_title'][$index] ) && $_POST['availableurl_title'][$index] ) {
							$title	= sanitize_text_field( $_POST['availableurl_title'][$index] );
						}

						// Get setting data
						$settings	= array(
							'frontend'		=> false,
							'exactly_url'	=> false
						);
						if( isset( $_POST["availableurl_settings_{$index}"] ) && !empty( $_POST["availableurl_settings_{$index}"] ) ) {
							foreach( $settings as $setting_name => $value ) {
								foreach( $_POST["availableurl_settings_{$index}"] as $post_setting_index => $post_setting_value ) {
									if( $post_setting_value == $setting_name ) {
										$settings[$setting_name] = true;
									}
								}
							}
						}
						
						$result[$active_role][] = array(
							'title'		=> $title,
							'url'		=> $url,
							'settings'	=> $settings
						);
					}
				}
				update_option( "availableurl_role", $result );

				return true;
			}
		}
		return false;
	}
}
Roles::get_instance();