<?php
/*
Plugin Name: Available url
Plugin URI: https://wordpress.org/plugins/available-url
Description: Let users just view urls you want
Version: 1.0.0.0
Author: Mohammad Jafar Khajeh
Author URI: https://zantium.ir
Text Domain: availableurl
Domain Path:  /languages
*/

namespace AvailableURL;

use \AvailableURL\Includes\Classes\Functions as Functions;

class Init {
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
		// Bootstrap actions
		$this->i18n();
		$this->constants();
		$this->includes();

		add_action( 'after_setup_theme', array( $this, "redirect" ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'backend_scripts_register' ), 2 );
	}

	private function i18n() {
		load_plugin_textdomain( 'availableurl', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	private function constants() {
		if( ! defined( 'AVAILABLEURL_DIR' ) )
			define( 'AVAILABLEURL_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
		
		if( ! defined( 'AVAILABLEURL_URI' ) )
			define( 'AVAILABLEURL_URI', trailingslashit( plugin_dir_url( __FILE__ ) ) );
	}

	private function includes() {
		include_once( AVAILABLEURL_DIR . "Includes/Classes/Functions.php" );
		include_once( AVAILABLEURL_DIR . "Backend/Menu.php" );

		include_once( AVAILABLEURL_DIR . "Backend/Options/Roles.php" );
		include_once( AVAILABLEURL_DIR . "Backend/Options/Users.php" );
		include_once( AVAILABLEURL_DIR . "Backend/Options/Settings.php" );
	}

	public function backend_scripts_register() {
		// Enqueue select2 scripts
		if( !wp_script_is( 'select2' ) && !wp_script_is( "select2js" ) ) {
			wp_enqueue_style( 'select2style', AVAILABLEURL_URI . 'assets/css/select2/select2.min.css' );
			wp_enqueue_script( 'select2js', AVAILABLEURL_URI . 'assets/js/select2/select2.min.js', array( 'jquery' ), false, true );
		}
		wp_register_script( "availableurl-backend-script", AVAILABLEURL_URI . "assets/js/backend/backend.min.js", array( "jquery" ), false, true );

		wp_register_style( "availableurl-backend-style", AVAILABLEURL_URI . "assets/css/backend/backend.min.css" );
	}

	public function redirect() {
		if( is_user_logged_in() ) {
			$functions = Functions::get_instance();

			$user = wp_get_current_user();

			$settings = $functions->get_settings();

			// Check this user is administrator and 'availableurl' is disabled for administrators so exit from this function.
			if( in_array( "administrator", $user->roles ) ) {
				if( !$settings['administrator_status'] ) {
					return false;
				}
			}
			
			// Get current url
			$current_url = array(
				'original' => $functions->get_url( true )
			);
			$current_url_info = $functions->other_url_types( $current_url['original'], true );
			if( isset( $current_url_info[0] ) && is_array( $current_url_info[0] ) ) {
				$current_url = array_merge( $current_url, $functions->other_url_types( $current_url['original'], true )[0] ); // This works for urls that have filename and file extension. Like: /options.php
			} else {
				$current_url = array_merge( $current_url, $functions->other_url_types( $current_url['original'], true )[1] ); // This works for urls thats just filename. Like: /wp-admin
			}
			$current_url = apply_filters( "availableurl/current_url", $current_url );

			$urls			= array();
			$options		= array();

			// Get user role urls
			foreach( $user->roles as $role ) {
				$role_urls = $functions->get_role_options( $role );
				foreach( $role_urls as $data ) {
					if( isset( $data['url'] ) && $data['url'] ) {
						$urls[]		= $data['url'];
						$options[]	= $data['settings'];
					}
				}
			}

			// Get user urls
			$user_urls = $functions->get_user_urls( $user->ID );
			foreach( $user_urls as $data ) {
				if( isset( $data['url'] ) && $data['url'] ) {
					$urls[]		= $data['url'];
					$options[]	= $data['settings'];
				}
			}

			$urls = apply_filters( "availableurl/urls/urls", $urls );
			$options = apply_filters( "availableurl/urls/options", $options );

			// Create available URLs
			if( !empty( $urls ) ) {
				$available_urls = array();
				
				// Add backend redirect to URL in available URLs
				$settings['backend_redirect_to'] = $functions->other_url_types( $settings['backend_redirect_to'] );
				$settings['backend_redirect_to'][] = add_query_arg( "redirect_by", "availableurl", $settings['backend_redirect_to'][0] );
				$available_urls = array_merge( $available_urls, $settings['backend_redirect_to'] );

				// Add frontend redirect to URL in available URLs
				$settings['frontend_redirect_to'] = $functions->other_url_types( $settings['frontend_redirect_to'] );
				$settings['frontend_redirect_to'][] = add_query_arg( "redirect_by", "availableurl", $settings['frontend_redirect_to'][0] );
				$available_urls = array_merge( $available_urls, $settings['frontend_redirect_to'] );
				
				// Set redirect url
				if( is_admin() ) {
					$key = array_key_last( $settings['backend_redirect_to'] );
					$redirect_url = $settings['backend_redirect_to'][$key];
				} else {
					$key = array_key_last( $settings['frontend_redirect_to'] );
					$redirect_url = $settings['frontend_redirect_to'][$key];
				}

				// Add urls to available urls
				foreach( $urls as $index => $url ) {
					if( !$options[$index]['frontend'] ) {
						$url_info = $functions->other_url_types( $url, true )[0];
						if( $options[$index]['exactly_url'] ) { // For this, parameters should be in available url
							$available_url = "{$url_info['dirname']}/{$url_info['filename']}.{$url_info['extension'][0]}";
							if( $url_info['extension'][1] ) {
								$available_url .= "?{$url_info['extension'][1]}";
							}
						} else {
							$available_url = "{$url_info['dirname']}/{$url_info['filename']}.{$url_info['extension'][0]}";
						}
						$available_urls[] = trailingslashit( $available_url ); // Add to available urls
					}
				}
				
				foreach( $available_urls as $url_index => $available_url ) { // Just for sure!
					$available_urls[$url_index] = trailingslashit( $available_url );
				}

				$available_urls = array_unique( $available_urls ); // Remove duplicate items
				
				$available_url = apply_filters( "availableurl/available_urls", $available_urls );

				if( !empty( $available_urls ) ) { // Redirect
					if( !in_array( $current_url['original'], $available_urls ) ) {
						$redirect_url = do_action( "availableurl/redirect_url", $redirect_url );
						do_action( "availableurl/before_redirect", $redirect_url );

						wp_redirect( $redirect_url );
						die;
					}
				}
			}
		}
	}
}
Init::get_instance();

// Todo: Regardless other parameters