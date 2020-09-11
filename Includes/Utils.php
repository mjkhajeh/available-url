<?php
namespace AvailableURL;

class Utils {
	/**
	 * Standard function for get current url
	 *
	 * @param boolean $full  Get full url.
	 * @return string
	 */
	public static function get_url( $full = false ) {
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
	private static function options_default_template() {
		$default = array(
			'title'			=> "",
			'url'			=> "",
			'settings'		=> array()
		);
		$url_settings = self::get_url_settings();
		if( $url_settings ) {
			foreach( $url_settings as $setting_id => $setting_data ) {
				$default['settings'][$setting_id] = $setting_data['default'];
			}
		}
		return apply_filters( 'availableurl/URL/default_options', $default );
	}

	/**
	 * Get each role option
	 *
	 * @param string $role_id  It's role name. Like: editor
	 * @return array
	 */
	public static function get_data( $id, $type ) {
		if( $type == 'role' ) {
			$data = get_option( "availableurl_role_{$id}", array() );
		} else if( $type == 'user' ) {
			$data = get_user_meta( $id, "availableurl", true );
		} else {
			return array();
		}
		if( !is_array( $data ) ) {
			$data = array();
		}
		if( !$data || ( count( $data ) < 3 ) ) {
			for( $index = 0; $index <= 2; $index++ ) {
				if( empty( $data[$index] ) ) {
					$data[$index] = self::options_default_template();
				}
			}
		}
		foreach( $data as $index => $url ) {
			if( $url ) {
				$url['url'] = urldecode( $url['url'] );
				$url['url'] = htmlspecialchars_decode( $url['url'] );
				$url['url'] = str_replace( "&#039;", "'", $url['url'] );

				$data[$index]['url'] = $url['url'];
			}
		}
		
		return $data;
	}

	/**
	 * Get settings of the plugin.
	 *
	 * @return array
	 */
	public static function get_settings() {
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
		
		return $settings;
	}

	/**
	 * Convert a url to other types to check
	 *
	 * @param string $url  Url for get other types
	 * @param boolean $pathinfo  Return array of path info
	 * @return array/string
	 */
	public static function other_url_types( $url ) {
		$extensions = array(
			'php'	=> 'php',
			'html'	=> 'html',
			'phtml'	=> 'phtml'
		);
		$extensions = apply_filters( "availableurl/extensions", $extensions );

		$parsed = parse_url( $url );
		$parsed['path'] = trailingslashit( $parsed['path'] );
		$pathinfo = pathinfo( $parsed['path'] );

		$urls = array();
		if( !empty( $pathinfo['extension'] ) ) { // Is file
			$urls[] = $url;
		} else { // Is route
			$urls[] = trailingslashit( sprintf( "%s://%s%s", $parsed['scheme'], $parsed['host'], $parsed['path'] ) ); // Main url
			$basename = '';
			$filename = "index";
			foreach( $extensions as $extension ) {
				$result = "{$urls[0]}index.{$extension}"; // add index.{$extension} to main url
				if( !empty( $parsed['query'] ) ) {
					$result .= "?{$parsed['query']}";
					if( !empty( $parsed['fragment'] ) ) {
						$result .= $parsed['fragment'];
					}
				}
				$urls[] = $result;
			}
		}

		return $urls;
	}

	/**
	 * Each URL has some settings. By this function we can get them. For process settings should add action in before redirect
	 *
	 * @return array
	 */
	public static function get_url_settings() {
		$settings = array(
			'inaccessibility' => array(
				'title'		=> __( "Inaccessibility URL", "availableurl" ),
				'default'	=> false
			),
			'regardless_params' => array(
				'title'		=> __( "Regardless other URL parameters", "availableurl" ),
				'default'	=> false
			),
		);
		return apply_filters( "availableurl/URL_settings", $settings );
	}

	public static function sort_url_queries( $url ) {
		$result_url = $url;
		$parsed = parse_url( $url );
		if( !empty( $parsed['query'] ) ) {
			$queries = array();
			foreach( explode( "&", $parsed['query'] ) as $query ) {
				$data = explode( "=", $query );
				if( count( $data ) == 2 ) {
					$key	= $data[0];
					$value	= $data[1];
					$queries[$key] = $value;
				}
			}
			if( !empty( $queries ) ) {
				ksort( $queries );
				$main_url = sprintf( "%s://%s%s", $parsed['scheme'], $parsed['host'], $parsed['path'] );
				$result_url = add_query_arg( $queries, $main_url );
			}
		}

		return $result_url;
	}
}