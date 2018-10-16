<?php
/**
 * The dashboard-specific functionality of the plugin.
 *
 * @link       https://wpgeodirectory.com
 * @since      1.0.0
 *
 * @package    GeoDir_Google_Analytics
 */

/**
 * The dashboard-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    GeoDir_Google_Analytics
 * @subpackage GeoDir_Google_Analytics/admin
 * @author     GeoDirectory <info@wpgeodirectory.com>
 */
class GeoDir_Google_Analytics_Admin {

    public function __construct() {
		add_filter( 'geodir_get_settings_pages', array( $this, 'load_settings_page' ), 10, 1 );
		add_action( 'wp_ajax_geodir_ga_deauthorize', array( $this, 'deauthorize' ) );
		add_action( 'wp_ajax_geodir_ga_callback', array( $this, 'callback' ) );
		add_action( 'geodir_admin_field_google_analytics', array( $this, 'google_analytics_field' ), 10, 1 );
		add_action( 'geodir_pricing_package_settings', array( $this, 'pricing_package_settings' ), 10, 3 );
		add_action( 'geodir_pricing_process_data_for_save', array( $this, 'pricing_process_data_for_save' ), 1, 3 );
    }
    
    /**
     * Short Description.
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public function activate( $network_wide = false ) {
        global $wpdb;

        if ( is_multisite() && $network_wide ) {
            foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs LIMIT 100" ) as $blog_id ) {
                switch_to_blog( $blog_id );

                $updated = $this->run_install();

                do_action( 'geodir_google_analytics_network_activate', $blog_id, $updated );

                restore_current_blog();
            }
        } else {
            $updated = $this->run_install();

            do_action( 'geodir_google_analytics_activate', $updated );
        }
        
        // Bail if activating from network, or bulk
        if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
            return;
        }

        // Add the transient to redirect
        set_transient( '_geodir_ga_activation_redirect', true, 30 );
    }
    
    /**
     * Short Description.
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public function deactivate() {
        do_action( 'geodir_google_analytics_deactivate' );
    }

    public function run_install() {
        global $geodir_options;
		
		// Add Upgraded From Option
        $current_version = get_option( 'geodir_ga_version' );

        if ( $current_version ) {
            update_option( 'geodir_ga_version_upgraded_from', $current_version );
        }

        if ( ! empty( $geodir_options ) && is_array( $geodir_options ) ) {
			set_transient( '_geodir_ga_installed', $geodir_options, 30 );

			do_action( 'geodir_google_analytics_install' );
        }

        update_option( 'geodir_ga_version', GEODIR_GA_VERSION );
    }
    
    public function activation_redirect() {        
        // Bail if no activation redirect
        if ( ! get_transient( '_geodir_ga_activation_redirect' ) ) {
            return;
        }

        // Delete the redirect transient
        delete_transient( '_geodir_ga_activation_redirect' );

        // Bail if activating from network, or bulk
        if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
            return;
        }
        
        wp_safe_redirect( admin_url( 'admin.php?page=gd-settings&tab=analytics' ) );
        exit;
    }
	
	public function load_settings_page( $settings_pages ) {
		$post_type = ! empty( $_REQUEST['post_type'] ) ? sanitize_text_field( $_REQUEST['post_type'] ) : 'gd_place';

		if ( ! ( ! empty( $_REQUEST['page'] ) && $_REQUEST['page'] == $post_type . '-settings' ) ) {
			$settings_pages[] = include( GEODIR_GA_PLUGIN_DIR . 'includes/admin/settings/class-geodir-settings-analytics.php' );
		}

		return $settings_pages;
	}
	
	/**
	 * Deauthorize Google Analytics
	 */
	public static function deauthorize(){
		// security
		check_ajax_referer( 'gd_ga_deauthorize', '_wpnonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		geodir_update_option( 'ga_auth_token', '' );
		geodir_update_option( 'ga_auth_code', '' );
		geodir_update_option( 'ga_uids', '' );
		geodir_update_option( 'ga_account_id', '' );

		echo admin_url( 'admin.php?page=gd-settings&tab=analytics' );

		wp_die();
	}
	
	public static function callback(){
		if ( ! empty( $_REQUEST['code'] )) {
			$oAuthURL = "https://www.googleapis.com/oauth2/v3/token?";
			$code = "code=" . sanitize_text_field( $_REQUEST['code'] );
			$grant_type = "&grant_type=authorization_code";
			$redirect_uri = "&redirect_uri=" . admin_url( 'admin-ajax.php' ) . "?action=geodir_ga_callback";
			$client_id = "&client_id=" . geodir_get_option( 'ga_client_id' );
			$client_secret = "&client_secret=" . geodir_get_option( 'ga_client_secret' );

			$auth_url = $oAuthURL . $code . $redirect_uri .  $grant_type . $client_id . $client_secret;

			$response = wp_remote_post( $auth_url, array( 'timeout' => 15 ) );

			$error_msg =  __('Something went wrong','geodirectory');
			if ( ! empty( $response['response']['code'] ) && $response['response']['code'] == 200 ) {
				$parts = json_decode( $response['body'] );
				if ( ! isset( $parts->access_token ) ) {
					echo $error_msg . " - #1";
					exit;
				} else {
					geodir_update_option( 'gd_ga_access_token', $parts->access_token );
					geodir_update_option( 'gd_ga_refresh_token', $parts->refresh_token );
					?><script>window.close();</script><?php
				}
			} elseif ( ! empty( $response['response']['code'] ) ) {
				$parts = json_decode( $response['body'] );

				if ( isset( $parts->error ) ) {
					echo $parts->error . ": " . $parts->error_description;
					exit;
				} else {
					echo $error_msg . " - #2";
					exit;
				}
			} else {
				echo $error_msg . " - #3";
				exit;
			}
		}
		exit;
	}
	
	public function google_analytics_field( $field ) {
		global $gd_ga_errors;
		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php echo $field['name'] ?></th>
			<td class="forminp">
				<?php if ( geodir_get_option( 'ga_auth_token' ) ) { ?>
					<span class="button-primary" onclick="geodir_ga_deauthorize('<?php echo wp_create_nonce( 'gd_ga_deauthorize' ); ?>');"><?php _e( 'Deauthorize', 'geodir-ga' ); ?></span> 
					<span style="color:green;font-weight:bold;"><?php _e( 'Authorized', 'geodir-ga' ); ?></span>
					<?php
					if ( ! empty( $gd_ga_errors ) ) {
						print_r( $gd_ga_errors );
					}
				} else {
					?>
					<span class="button-primary" onclick="window.open('<?php echo GeoDir_Settings_Analytics::activation_url();?>', 'activate','width=700, height=600, menubar=0, status=0, location=0, toolbar=0')"><?php _e( 'Authorize', 'geodir-ga' ); ?></span>
					<?php
				}
				?>
				<script type="text/javascript">
				function geodir_ga_deauthorize(nonce) {
					var result = confirm(geodir_params.ga_confirm_delete);
					if (result) {
						jQuery.ajax({
							url: geodir_params.ajax_url,
							type: 'POST',
							dataType: 'html',
							data: {
								action: 'geodir_ga_deauthorize',
								_wpnonce: nonce
							},
							beforeSend: function() {},
							success: function(data, textStatus, xhr) {
								if (data) {
									window.location.assign(data);
								}
							},
							error: function(xhr, textStatus, errorThrown) {
								alert(textStatus);
							}
						}); // end of ajax
					}
				}
				</script>
			</td>
		</tr>
		<?php
	}

	public function pricing_package_settings( $settings, $package_data ) {
		$new_settings = array();

		foreach ( $settings as $key => $setting ) {
			if ( ! empty( $setting['id'] ) && $setting['id'] == 'package_features_settings' && ! empty( $setting['type'] ) && $setting['type'] == 'sectionend' ) {
				$new_settings[] = array(
					'type' => 'checkbox',
					'id' => 'package_google_analytics',
					'title'=> __( 'Google Analytics', 'geodir-ga' ),
					'desc' => __( 'Tick to enable google analytics.', 'geodir-ga' ),
					'std' => '0',
					'advanced' => true,
					'value'	=> ( ! empty( $package_data['google_analytics'] ) ? '1' : '0' )
				);
			}
			$new_settings[] = $setting;
		}

		return $new_settings;
	}

	public function pricing_process_data_for_save( $package_data, $data, $package ) {
		if ( isset( $data['google_analytics'] ) ) {
			$package_data['meta']['google_analytics'] = ! empty( $data['google_analytics'] ) ? 1 : 0;
		} else if ( isset( $package['google_analytics'] ) ) {
			$package_data['meta']['google_analytics'] = $package['google_analytics'];
		} else {
			$package_data['meta']['google_analytics'] = 0;
		}

		return $package_data;
	}
}