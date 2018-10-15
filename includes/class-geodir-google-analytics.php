<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the dashboard.
 *
 * @link       https://wpgeodirectory.com
 * @since      1.0.0
 *
 * @package    GeoDir_Google_Analytics
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, dashboard-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    GeoDir_Google_Analytics
 * @author     GeoDirectory <info@wpgeodirectory.com>
 */
class GeoDir_Google_Analytics {
    protected static $instance = null;
    
    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the Dashboard and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->define_constants();
        $this->setup_globals();
        $this->includes();
        $this->setup_actions();

        do_action( 'geodir_google_analytics_loaded' );
    }
    
    private function define_constants() {
        $wp_upload_dir = wp_upload_dir();

        if ( ! defined( 'GEODIR_GA_PLUGIN_DIR' ) ) {
            define( 'GEODIR_GA_PLUGIN_DIR', plugin_dir_path( GEODIR_GA_PLUGIN_FILE ) );
        }
        if ( ! defined( 'GEODIR_GA_PLUGIN_URL' ) ) {
            define( 'GEODIR_GA_PLUGIN_URL', plugin_dir_url( GEODIR_GA_PLUGIN_FILE ) );
        }
		
		// Google Analytic app settings
		if ( ! defined( 'GEODIR_GA_CLIENTID' ) ) {
			define( 'GEODIR_GA_CLIENTID', '687912069872-sdpsjssrdt7t3ao1dnv1ib71hkckbt5s.apps.googleusercontent.com' );
		}
		if ( ! defined( 'GEODIR_GA_CLIENTSECRET' ) ) {
			define( 'GEODIR_GA_CLIENTSECRET', 'yBVkDpqJ1B9nAETHy738Zn8C' ); // don't worry - this don't need to be secret in our case
		}
		if ( ! defined( 'GEODIR_GA_REDIRECT' ) ) {
			define( 'GEODIR_GA_REDIRECT', 'urn:ietf:wg:oauth:2.0:oob' );
		}
		if ( ! defined( 'GEODIR_GA_SCOPE' ) ) {
			define( 'GEODIR_GA_SCOPE', 'https://www.googleapis.com/auth/analytics' ); // .readonly
		}
    }
    
    private function setup_globals() {
        $this->version      = GEODIR_GA_VERSION;
        $this->plugin_file  = GEODIR_GA_PLUGIN_FILE;
        $this->plugin_dir   = GEODIR_GA_PLUGIN_DIR;
        $this->plugin_url   = GEODIR_GA_PLUGIN_URL;
    }
    
    private function includes() {
        global $geodirectory;

		// Functions
		require_once( $this->plugin_dir . 'includes/functions.php' );

        // Classes
        require_once( $this->plugin_dir . 'includes/class-geodir-google-analytics-widget.php' );
        require_once( $this->plugin_dir . 'includes/admin/class-geodir-google-analytics-admin.php' );

		if ( $this->is_request( 'admin' ) || $this->is_request( 'test' ) || $this->is_request( 'cli' ) ) {
			require_once( $this->plugin_dir . 'includes/admin/class-geodir-google-analytics-api.php' );
		}
        
        $this->admin = new GeoDir_Google_Analytics_Admin();
    }
    
    private function setup_actions() {
        register_activation_hook( $this->plugin_file, array( $this->admin, 'activate' ) );
        register_deactivation_hook( $this->plugin_file, array( $this->admin, 'deactivate' ) );
        
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
        add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'widgets_init', array( $this, 'register_widgets' ), 11 );
		add_action( 'geodir_get_post_package', array( $this, 'package_default_value' ), -1, 3 );
		add_action( 'geodir_detail_page_widget_id_bases', array( $this, 'set_widget_id_bases' ), 10, 1 );
		add_filter( 'geodir_params', array( $this, 'scripts_params' ), 10, 1 );
        
        if ( is_admin() ) {
            add_action( 'admin_init', array( $this->admin, 'activation_redirect' ) );
        } else {
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'wp_head', 'geodir_ga_add_tracking_code' );
        }
		
		add_action( 'wp_ajax_gdga', array( $this, 'show_ga_stats' ) );
		add_action( 'wp_ajax_nopriv_gdga', array( $this, 'show_ga_stats' ) );

        do_action( 'geodir_google_analytics_actions', $this );
    }
	
	public function plugins_loaded() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		
		if ( ! class_exists( 'GeoDirectory' ) ) {
			deactivate_plugins( plugin_basename( GEODIR_GA_PLUGIN_FILE ) );

			function geodir_ga_requires_core_plugin() {
				echo '<div class="notice notice-warning is-dismissible"><p><strong>' . sprintf( __( '%s requires %sGeoDirectory%s to be installed & activated.', 'geodir-ga' ), 'GeoDirectory - Google Analytics', '<a href="https://wordpress.org/plugins/geodirectory/" target="_blank" title="GeoDirectory – Business Directory Plugin">', '</a>' ) . '</strong></p></div>';
			}

			add_action( 'admin_notices', 'geodir_ga_requires_core_plugin' );

			return;
		}
	}
    
    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
	
	/**
     * Request type.
     *
     * @param  string $type admin, frontend, ajax, cron, test or CLI.
     * @return bool
     */
    private function is_request( $type ) {
        switch ( $type ) {
            case 'admin' :
                return is_admin();
                break;
            case 'ajax' :
                return defined( 'DOING_AJAX' );
                break;
            case 'cli' :
                return ( defined( 'WP_CLI' ) && WP_CLI );
                break;
            case 'cron' :
                return defined( 'DOING_CRON' );
                break;
            case 'frontend' :
                return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
                break;
            case 'test' :
                return defined( 'GD_TESTING_MODE' );
                break;
        }
        
        return null;
    }
    
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'geodir-ga',
            FALSE,
            $this->plugin_dir . 'languages/'
        );
    }
    
    public function enqueue_scripts() {
        // scripts
    }
	
	public function register_widgets() {
		register_widget( 'GeoDir_Google_Analytics_Widget' );
    }
	
	public function show_ga_stats() {
		if ( isset( $_REQUEST['ga_start'] ) ) {
			$ga_start = $_REQUEST['ga_start'];
		} else {
			$ga_start = '';
		}
		if ( isset( $_REQUEST['ga_end'] ) ) {
			$ga_end = $_REQUEST['ga_end'];
		} else {
			$ga_end = '';
		}
		try {
			geodir_ga_get_analytics( $_REQUEST['ga_page'], $ga_start, $ga_end );
		} catch ( Exception $e ) {
			geodir_error_log( wp_sprintf( __( 'GD Google Analytics API Error(%s) : %s', 'geodir-ga' ), $e->getCode(), $e->getMessage() ) );
		}
		geodir_die();
	}
	
	public function scripts_params( $params ) {
		$params['ga_confirm_delete'] = __( 'Are you wish to Deauthorize and break Analytics?', 'geodir-ga' );
		
		return $params;
	}
	
	public function package_default_value( $package, $post, $post_type ) {

		$package->google_analytics = 1;

		return $package;
	}
	
	public function set_widget_id_bases( $id_bases ) {
		$id_bases[] = 'gd_google_analytics';
		
		return $id_bases;
	}
}