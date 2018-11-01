<?php
/**
 * Google Analytics Autoloader.
 *
 * @since 2.0.0
 * @package GeoDir_Google_Analytics
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Google_Analytics_Autoloader class.
 */
class GeoDir_Google_Analytics_Autoloader {

    /**
     * Path to the includes directory.
     *
     * @var string
     */
    private $include_path = '';

    /**
     * The Constructor.
     */
    public function __construct() {
        if ( function_exists( "__autoload" ) ) {
            spl_autoload_register( "__autoload" );
        }

        spl_autoload_register( array( $this, 'autoload' ) );

        $this->include_path = untrailingslashit( plugin_dir_path( GEODIR_GA_PLUGIN_FILE ) ) . '/includes/';
    }

    /**
     * Take a class name and turn it into a file name.
     *
     * @param  string $class
     * @return string
     */
    private function get_file_name_from_class( $class ) {
        return 'class-' . str_replace( '_', '-', $class ) . '.php';
    }

    /**
     * Include a class file.
     *
     * @param  string $path
     * @return bool successful or not
     */
    private function load_file( $path ) {
        if ( $path && is_readable( $path ) ) {
            include_once( $path );
            return true;
        }
        return false;
    }

    /**
     * Auto-load GeoDir classes on demand to reduce memory consumption.
     *
     * @param string $class
     */
    public function autoload( $class ) {
        $class = strtolower( $class );

        if ( 0 !== strpos( $class, 'geodir_google_analytics_' ) ) {
            return;
        }

        $file  = $this->get_file_name_from_class( $class );
        $path  = '';

        if ( strpos( $class, 'geodir_google_analytics_admin' ) === 0 ) {
            $path = $this->include_path . 'admin/';
        } else if ( strpos( $class, 'geodir_google_analytics_settings' ) === 0 ) {
            $path = $this->include_path . 'admin/settings/';
        } else if ( strpos( $class, 'geodir_google_analytics_shortcode_' ) === 0 ) {
            $path = $this->include_path . 'shortcodes/';
        } else if ( strpos( $class, 'geodir_google_analytics_widget' ) === 0 ) {
            $path = $this->include_path . 'widgets/';
        }

        if ( empty( $path ) || ! $this->load_file( $path . $file ) ) {
            $this->load_file( $this->include_path . $file );
        }
    }
}

new GeoDir_Google_Analytics_Autoloader();
