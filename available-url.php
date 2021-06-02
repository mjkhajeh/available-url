<?php
/*
Plugin Name: Available url
Plugin URI: https://wordpress.org/plugins/available-url
Description: Let users just view urls you want
Version: 1.3.2.9
Author: Mohammad Jafar Khajeh
Author URI: https://mjkhajeh.ir
Text Domain: availableurl
Domain Path: /languages
*/
namespace AvailableURL;

class Init {
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
		// Bootstrap actions
		$this->i18n();
		$this->constants();
		$this->includes();

		add_action( 'after_setup_theme', array( $this, "reset" ), 1 );
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
		include_once( AVAILABLEURL_DIR . "Includes/Utils.php" );
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
		if( file_exists( AVAILABLEURL_DIR . "assets/js/backend/backend.min.js" ) ) {
			wp_register_script( "availableurl-backend-script", AVAILABLEURL_URI . "assets/js/backend/backend.min.js", array( "jquery" ), false, true );
		} else {
			wp_register_script( "availableurl-backend-script", AVAILABLEURL_URI . "assets/js/backend/backend.js", array( "jquery" ), false, true );
		}

		if( file_exists( AVAILABLEURL_DIR . "assets/css/backend/backend.min.css" ) ) {
			wp_register_style( "availableurl-backend-style", AVAILABLEURL_URI . "assets/css/backend/backend.min.css" );
		} else {
			wp_register_style( "availableurl-backend-style", AVAILABLEURL_URI . "assets/css/backend/backend.css" );
		}
	}

	public function reset() {
		if( is_user_logged_in() ) {
			if( !empty( $_GET['availableurl_reset_admin'] ) && $_GET['availableurl_reset_admin'] == 'true' ) {
				$settings = Utils::get_settings();
				$settings['administrator_status'] = false;
				update_option( "availableurl", $settings );
			}
		}
	} 

	public function redirect() {
		if( is_user_logged_in() ) {
			$redirect = false;
			$user = wp_get_current_user();

			$settings = Utils::get_settings();

			// Check this user is administrator and 'availableurl' is disabled for administrators so exit from this function.
			if( in_array( "administrator", $user->roles ) ) {
				if( !$settings['administrator_status'] ) {
					return false;
				}
			}
			
			// Get current url
			$current_url = Utils::get_url( true );
			$current_url = apply_filters( "availableurl/redirect/current_url", $current_url );
			$current_url_parse = parse_url( $current_url );
			$current_url_main = trailingslashit( sprintf( "%s://%s%s", $current_url_parse['scheme'], $current_url_parse['host'], $current_url_parse['path'] ) );

			$urls		= array();
			$options	= array();
			// Get user role urls
			foreach( $user->roles as $role ) {
				$role_urls = Utils::get_data( $role, 'role' );
				$urls = array_merge( $urls, wp_list_pluck( $role_urls, 'url' ) );
				$options = array_merge( $options, wp_list_pluck( $role_urls, 'settings' ) );
			}

			// Get user urls
			$user_urls = Utils::get_data( $user->ID, 'user' );
			$urls = array_merge( $urls, wp_list_pluck( $user_urls, 'url' ) );
			$options = array_merge( $options, wp_list_pluck( $user_urls, 'settings' ) );

			$urls = apply_filters( "availableurl/redirect/urls/urls", $urls );
			$options = apply_filters( "availableurl/redirect/urls/options", $options );
			
			// Unset empty arrays
			foreach( $urls as $index => $url ) {
				if( !$url ) {
					unset( $urls[$index] );
					unset( $options[$index] );
				}
			}

			// Create available URLs
			$available_urls = array();
			if( !empty( $urls ) ) {
				// Add backend redirect URL to available URLs
				$settings['backend_redirect_to'] = add_query_arg( "redirect_by", "availableurl", $settings['backend_redirect_to'] ); // Add query arg to base URL
				$settings['backend_redirect_to'] = Utils::other_url_types( $settings['backend_redirect_to'] );
				$available_urls = array_merge( $available_urls, $settings['backend_redirect_to'] );

				// Set redirect url
				$redirect_url = add_query_arg( "redirect_by", "availableurl", $settings['backend_redirect_to'][0] );

				// Add urls to available urls
				// Set URL settings
				$available_urls = apply_filters( "availableurl/urls/before_set_settings", $available_urls, $urls, $options );
				foreach( $urls as $index => $url ) {
					$url = apply_filters( "availableurl/url/before_set_settings", $url, $options[$index] );

					$url_parse = parse_url( $url );
					$url_main = trailingslashit( sprintf( "%s://%s%s", $url_parse['scheme'], $url_parse['host'], $url_parse['path'] ) );
					if( !empty( $options[$index]['inaccessibility'] ) && !empty( $options[$index]['regardless_params'] ) ) {
						if( $current_url_main == $url_main ) {
							$redirect = true;
							break;
						}
					}
					
					if( !empty( $options[$index]['inaccessibility'] ) ) {
						continue;
					}

					if( !empty( $options[$index]['regardless_params'] ) ) {
						if( $current_url_main == $url_main ) {
							$available_urls[] = $current_url;
							break;
						}
					}
					
					if( !empty( $url ) && is_string( $url ) ) {
						$other_types = Utils::other_url_types( $url );
						$available_urls = array_merge( $available_urls, $other_types );
					}
				}
				if( !$redirect ) {
					$available_urls = apply_filters( "availableurl/urls/after_set_settings", $available_urls, $urls, $options );
					
					foreach( $available_urls as $url_index => $available_url ) { // Just for sure!
						$available_urls[$url_index] = trailingslashit( $available_url );
					}

					// Sort all url queries
					$current_url = Utils::sort_url_queries( $current_url );
					foreach( $available_urls as $url_index => $available_url ) {
						$available_urls[$url_index] = Utils::sort_url_queries( $available_url );
					}

					$available_urls = array_unique( $available_urls ); // Remove duplicate items

					if( !in_array( $current_url, $available_urls ) ) {
						$redirect = true;
					}
				}
			}

			$redirect = apply_filters( "availableurl/redirect", $redirect, $available_urls, $current_url );

			// Check and redirect user
			if( $redirect ) {
				$redirect_url = apply_filters( "availableurl/redirect_url", $redirect_url, $available_urls, $current_url );
				if( $redirect_url ) {
					do_action( "availableurl/before_redirect", $redirect_url, $available_urls, $current_url );
					wp_redirect( $redirect_url );
					die;
				}
			}
		}
	}
}
Init::get_instance();