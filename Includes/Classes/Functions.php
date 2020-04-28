<?php
namespace AvailableURL\Includes\Classes;

/**
 * Functions required for this plugin.
 */
class Functions {
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
	
	/**
	 * Standard function for get current url
	 *
	 * @param boolean $full  Get full url.
	 * @return string
	 */
	public function get_url( $full = false ) : string {
		if( $full == true ) {
			if( isset( $_SERVER['HTTPS'] ) &&
				( $_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1 ) ||
				isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) &&
				$_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ) {
				$protocol = 'https://';
			} else {
				$protocol = 'http://';
			}
			$url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		} else {
			global $wp;
			$url = home_url( $wp->request );
		}

		$url = esc_url( $url );
		$url = trailingslashit( $url );

		return $url;
	}

	/**
	 * Get default template of each url is saved in plugin.
	 *
	 * @return array
	 */
	private function options_default_template() : array {
		return array(
			'title'			=> "",
			'url'			=> "",
			'settings'		=> array(
				'frontend'		=> false,
				'exactly_url'	=> false
			)
		);
	}
	
	/**
	 * Get all roles options from options.
	 *
	 * @return array
	 */
	public function get_all_roles_options() : array {
		global $wp_roles;
		$roles = $wp_roles->get_names(); // Get name and id of roles

		$urls = get_option( "availableurl_role", array() );
		if( !$urls || !is_array( $urls ) ) {
			$urls = array();
		}
		
		foreach( $roles as $role_id => $role ) {
			if( !isset( $urls[$role_id] ) || empty( $urls[$role_id] ) || count( $urls[$role_id] ) < 3 ) {
				$default_template = $this->options_default_template();
				if( !isset( $urls[$role_id] ) || empty( $urls[$role_id] ) ) {
					$urls[$role_id] = array();
				}
				for( $index = 0; $index <= 2; $index++ ) {
					if( !isset( $urls[$role_id][$index] ) || empty( $urls[$role_id][$index] ) ) {
						$urls[$role_id][$index] = $default_template;
					}
				}
			}
		}

		return $urls;
	}

	/**
	 * Get each role option
	 *
	 * @param string $role_id  It's role name. Like: editor
	 * @return array
	 */
	public function get_role_options( string $role_id ) : array {
		$options = $this->get_all_roles_options();
		return $options[$role_id];
	}

	public function get_user_urls( int $user_id ) {
		$urls = get_user_meta( $user_id, "availableurl", true );
		if( !$urls || !is_array( $urls ) || ( count( $urls ) < 3 ) ) {
			$default_template = $this->options_default_template();
			for( $index = 0; $index <= 2; $index++ ) {
				if( !isset( $urls[$index] ) || empty( $urls[$index] ) ) {
					$urls[$index] = $default_template;
				}
			}
		}
		
		return $urls;
	}

	/**
	 * Get settings of the plugin.
	 *
	 * @return array
	 */
	public function get_settings() : array {
		$settings = get_option( "availableurl", array() );
		if( !$settings || !is_array( $settings ) ) {
			$settings = array();
		}

		if( !isset( $settings['administrator_status'] ) ) {
			$settings['administrator_status'] = false;
		}

		if( !isset( $settings['backend_redirect_to'] ) || !$settings['backend_redirect_to'] ) {
			$settings['backend_redirect_to'] = trailingslashit( admin_url() );
		} else {
			$settings['backend_redirect_to'] = trailingslashit( $settings['backend_redirect_to'] );
		}

		if( !isset( $settings['frontend_redirect_to'] ) || !$settings['frontend_redirect_to'] ) {
			$settings['frontend_redirect_to'] = trailingslashit( home_url() );
		} else {
			$settings['frontend_redirect_to'] = trailingslashit( $settings['frontend_redirect_to'] );
		}
		
		return $settings;
	}

	/**
	 * Get standard url for plugin.
	 *
	 * @param string $url  Url for get other types
	 * @param boolean $pathinfo  Return array of path info
	 * @return array/string
	 */
	public function other_url_types( string $url, $pathinfo = false ) {
		$extensions = array(
			'php'	=> 'php',
			'html'	=> 'html',
			'phtml'	=> 'phtml'
		);
		$extensions = apply_filters( "availableurl/extensions", $extensions );

		$urls = array(
			trailingslashit( $url )
		);

		$url_info = pathinfo( $url );
		if( isset( $url_info['extension'] ) ) {
			if( $pathinfo ) {
				$url_info['extension'] = explode( "?", $url_info['extension'] ); // Convert extensions to array
				if( !isset( $url_info['extension'][1] ) ) { // Create second child
					$url_info['extension'][1] = "";
				}
				$urls = array(); // Destroy array to convert it standard type for this plugin.
				$urls[] = $url_info;
			}
			return $urls;
		}
		// Convert extensions element to array
		$url_info['extension'] = array(
			'',
			''
		);

		// Add index if url hasn't.
		if( !$url_info['extension'][0] ) { // Hasn't parameters or filename
			$url_info['filename'] = "index";
			if( strpos( $url_info['basename'], "?" ) ) {
				$basename = explode( "?", $url_info['basename'] );
				$url_info['dirname'] .= "/{$basename[0]}";
				$url_info['extension'][1] = $basename[1];
			} else {
				$url_info['dirname'] .= "/{$url_info['basename']}";
			}
		}

		// Add other extensions.
		foreach( $extensions as $extension ) {
			if( $pathinfo ) {
				$urls[] = $url_info;
			} else {
				$_url = "{$url_info['dirname']}/{$url_info['filename']}.{$extension}";
				if( $url_info['extension'][1] ) {
					$_url .= "?{$url_info['extension'][1]}";
				}
				$urls[] = trailingslashit( $_url );
			}
		}

		return $urls;
	}
}