<?php
namespace AvailableURL\Backend\Options;

use AvailableURL\Utils as Utils;

class Users {
	PRIVATE $PREFIX = 'availableurl_';
	/**
	 * Creates or returns an instance of this class.
	 *
	 * @return	A single instance of this class.
	 */
	public static function get_instance() {
		static $instance = null;
		if( $instance === null ) {
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
		$main_url = menu_page_url( "availableurl", false ); // Get url of the page
		$main_url = add_query_arg( "tab", "users", $main_url ); // Add tab to main url

		$users = get_users();
		$default_user_id = $users[0]->ID;
		
		$active_user = $default_user_id;
		$user = $users[0];
		if( isset( $_GET['user'] ) && $_GET['user'] ) {
			$active_user = sanitize_text_field( $_GET['user'] );
			$user = get_user_by( 'id', $active_user );
		}

		$this->save( $active_user );

		// Get options of active user
		$urls = Utils::get_data( $active_user, 'user' );

		$url_settings = Utils::get_url_settings();
		?>
		<h2><?php _e( "Users", "availableurl" ) ?></h2>
		<h3><?php echo "{$user->display_name} (#{$user->ID})" ?></h3>

		<table class="form-table">
			<tr>
				<th>
					<label for="<?php echo $this->PREFIX ?>select_user">
						<?php _e( "Select user", "availableurl" ) ?>
					</label>
				</th>

				<td>
					<select id="<?php echo $this->PREFIX ?>select_user" class="availableurl_select">
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
		<div id="availableurl_templates">
			<?php
			$template_type = "user";
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
						<tr class="<?php echo $this->PREFIX ?>user_url_row" id="<?php echo $this->PREFIX ?>user_url_<?php echo $id ?>" data-id="<?php echo $id ?>">
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

	private function save( $active_user ) {
		if( !empty( $_POST ) ) {
			if( !empty( $_POST["{$this->PREFIX}url"] ) ) {
				$urls = $_POST["{$this->PREFIX}url"];

				$result = array();
				foreach( $urls as $index => $url ) {
					if( $url ) {
						$index	= sanitize_text_field( $index );
						$url	= esc_url( $url );
						$url	= stripslashes( $url );
						
						// Get title
						$title = "";
						if( isset( $_POST["{$this->PREFIX}title"][$index] ) ) {
							$title = sanitize_text_field( $_POST["{$this->PREFIX}title"][$index] );
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
				update_user_meta( $active_user, "availableurl", $result );

				return true;
			}
		}
		return false;
	}
}
Users::get_instance();