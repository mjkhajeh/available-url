<?php
namespace AvailableURL\Backend\Options;

use \AvailableURL\Includes\Classes\Functions as Functions;

class Users {
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
		
		$tabs['users'] = array(
			'title'	=> __( "Users", "availableurl" ),
			'url'	=> add_query_arg( "tab", "users", $main_url ),
			'callback'	=> array( $this, "view" )
		);

		return $tabs;
	}

	public function view() {
		$functions = Functions::get_instance();

		$main_url = menu_page_url( "availableurl", false ); // Get url of the page
		$main_url = add_query_arg( "tab", "users", $main_url ); // Add tab to main url

		$users = get_users();
		$default_user_id = $users[0]->ID;
		
		$active_user = $default_user_id;
		$user = $users[0];
		if( isset( $_GET['user'] ) && $_GET['user'] ) {
			$active_user = sanitize_text_field( $_GET['user'] );
			$user = get_user( $active_user );
		}

		$this->save( $active_user );

		// Get options of active user
		$urls = $functions->get_user_urls( $active_user );
		?>
		<h2><?php _e( "Users", "availableurl" ) ?></h2>
		<h3><?php echo "{$user->display_name} (#{$user->ID})" ?></h3>

		<table class="form-table">
			<tr>
				<th>
					<label for="availableurl_select_user">
						<?php _e( "Select user", "availableurl" ) ?>
					</label>
				</th>

				<td>
					<select id="availableurl_select_user" class="availableurl_select">
						<?php
						foreach( $users as $user_class ) {
							$user_url = add_query_arg( "user", $user_class->ID, $main_url );
							?>
							<option value="<?php echo $user_url ?>" <?php selected( $user_class->ID, $active_user ) ?>><?php echo "{$user_class->display_name} (#{$user_class->ID})" ?></option>
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
						<tr class="availableurl_user_url_row" id="availableurl_user_url_<?php echo $id ?>" data-id="<?php echo $id ?>">
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

	private function save( $active_user ) {
		if( !empty( $_POST ) ) {
			if( isset( $_POST['availableurl_url'] ) && !empty( $_POST['availableurl_url'] ) ) {
				$urls = $_POST['availableurl_url'];

				$result = array();
				foreach( $urls as $index => $url ) {
					if( $url ) {
						$index	= sanitize_text_field( $index );
						$url	= esc_url( $url );
						$url	= stripslashes( $url );
						$url	= trailingslashit( $url );
						
						// Get title
						$title 		= "";
						if( isset( $_POST['availableurl_title'][$index] ) && $_POST['availableurl_title'][$index] ) {
							if( isset( $_POST['availableurl_title'][$index] ) && $_POST['availableurl_title'][$index] ) {
								$title	= sanitize_text_field( $_POST['availableurl_title'][$index] );
							}
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
						
						$result[] = array(
							'title'		=> $title,
							'url'		=> $url,
							'settings'	=> $settings
						);
					}
				}
				update_user_meta( $active_user, "availableurl", $result );

				return true;
			}
		}
		return false;
	}
}
Users::get_instance();