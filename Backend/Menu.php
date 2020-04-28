<?php
namespace AvailableURL\Backend;

class Menu {
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
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	public function add_menu() {
		$hook = add_options_page(
			__( "Available URL", "availableurl" ),	// Page title
			__( "Available URL", "availableurl" ),	// Menu title
			'manage_options',						// Capability
			'availableurl',							// Menu slug
			array( $this, "view" )					// Callback
		);
		add_action( "admin_print_styles-{$hook}", array( $this, 'enqueue' ) );
	}

	public function admin_init() {
		// Register settings by user roles
		global $wp_roles;
		$roles = $wp_roles->get_names(); // Get name and id of roles
		foreach( $roles as $role_id => $role_name ) {
			register_setting( 'availableurl_role_options', "availableurl_role_{$role_id}_status", array(
				'type'	=> "boolean"
			) );

			register_setting( 'availableurl_role_options', "availableurl_role_{$role_id}_urls" );

			register_setting( 'availableurl_role_options', "availableurl_role_{$role_id}_frontend", array(
				'type'	=> "boolean"
			) );
		}
	}

	public function enqueue() {
		wp_enqueue_script( "availableurl-backend-script" );
		wp_localize_script( "availableurl-backend-script", "availableurl", array(
			'i18n'	=> array(
				'url_placeholder'		=> __( "http://... or https://...", "availableurl" ),
				'settings_placeholder'	=> __( "Select settings", "availableurl" ),
				'setting_frontend'		=> __( "It's a frontend URL", "availableurl" ),
				'setting_exactly_url'	=> __( "Exactly this address", "availableurl" )
			)
		) );
		
		wp_enqueue_style( "availableurl-backend-style" );
	}

	public function view() {
		$tabs = array();
		$tabs = apply_filters( "AvailableURL/Backend/Tabs", $tabs );

		$default_tab = "";
		if( $tabs && is_array( $tabs ) ) {
			$default_tab = array_key_first( $tabs );
		}
		$default_tab = apply_filters( "AvailableURL/Backend/Tabs/DefaultTab", $default_tab );

		$active_tab = $default_tab;
		if( isset( $_GET['tab'] ) && $_GET['tab'] ) {
			$request_tab = sanitize_text_field( $_GET['tab'] );
			if( isset( $tabs[$request_tab] ) && is_array( $tabs[$request_tab] ) ) {
				$active_tab = $request_tab;
			}
		}
		?>
		<div class="wrap" id="availableurl_options">
			<h2><?php _e( "Available URL", "availableurl" ) ?></h2>
			<?php if( $tabs && is_array( $tabs ) ) { ?>
				<div class="nav-tab-wrapper">
					<?php
					foreach( $tabs as $tab_id => $tab ) {
						$tab_classes = array(
							'nav-tab'
						);
						if( $active_tab == $tab_id ) {
							$tab_classes[] = "nav-tab-active";
						}
						$tab_classes = apply_filters( "AvailableURL/Backend/Tab/{$tab_id}/Classes", $tab_classes );
						$tab_classes = implode( " ", $tab_classes ); // Convert to string
						?>
						<a class="<?php echo $tab_classes ?>" href="<?php echo $tab['url'] ?>"><?php echo $tab['title'] ?></a>
					<?php } ?>
				</div>
			<?php } ?>

			<?php
			if( $active_tab ) {
				$callback = $tabs[$active_tab]['callback'];
				$callback();
			} else {
				?>
				<div class="notice notice-error">
					<p><?php _e( "No tabs actived", "availableurl" ) ?></p>
				</div>
			<?php } ?>
		</div>
		<?php
	}
}
Menu::get_instance();