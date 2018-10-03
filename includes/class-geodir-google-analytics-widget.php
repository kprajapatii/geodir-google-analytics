<?php
/**
* GeoDirectory Detail Google Analytics Widget
*
* @since 1.0.0
*
* @package GeoDirectory
*/

class GeoDir_Google_Analytics_Widget extends WP_Super_Duper {
	
	public $arguments;
	
	/**
     * Sets up a new Detail Google Analytics widget instance.
     *
     * @since 1.0.0
     * @access public
     */
    public function __construct() {
        $options = array(
            'textdomain'    => 'geodir-ga',
            'block-icon'    => 'chart-bar',
            'block-category'=> 'widgets',
            'block-keywords'=> "['analytics','geodirectory','google']",
            'block-output'  => array(
                'element::img'   => array(
                    'src' => geodir_plugin_url()."/assets/images/block-placeholder-map.png",
                    'alt' => __('Placeholder','geodirectory'),
                    'title' => '[%title%]',
                    'user_roles' => '[%user_roles%]',
                )
            ),
            'class_name'    => __CLASS__,
            'base_id'       => 'gd_google_analytics', // this us used as the widget id and the shortcode id.
            'name'          => __('GD > Google Analytics','geodir-ga'), // the name of the widget.
            'widget_ops'    => array(
                'classname'   => 'geodir-google-analytics', // widget class
                'description' => esc_html__('Show google analytics on detail page.','geodir-ga'), // widget description
                'geodirectory' => true,
            ),
            'arguments'     => array(
				'title'  => array(
					'title' => __('Title:', 'geodir-ga'),
					'desc' => __('The widget title:', 'geodir-ga'),
					'type' => 'text',
//					'placeholder' => __('The widget placeholder', 'geodir-ga'),
					'desc_tip' => true,
					'default'  => '',
					'advanced' => false
				),
				'button_text'  => array(
					'title' => __('Button text:', 'geodir-ga'),
					'desc' => __('The text to use for the button to show the analytics:', 'geodir-ga'),
					'type' => 'text',
					'placeholder' => __('Show Google Analytics', 'geodir-ga'),
					'desc_tip' => true,
					'default'  => '',
					'advanced' => true
				),
				'user_roles'  => array(
					'title' => __('Google Analytics visible to:', 'geodir-ga'),
					'desc' => __('Google Analytics will be visible to selected users only.', 'geodir-ga'),
					'type' => 'select',
					'options'   =>  array(
						"owner,administrator" => __('Owner/Administrator', 'geodir-ga'),
						"administrator" => __('Administrator', 'geodir-ga'),
						"all" => __('Everyone', 'geodir-ga'),
					),
					'desc_tip' => true,
					'advanced' => false
				),
			),
        );


        parent::__construct( $options );
    }

    
	/**
	 * This is the output function for the widget, shortcode and block (front end).
	 *
	 * @param array $args The arguments values.
	 * @param array $widget_args The widget arguments when used.
	 * @param string $content The shortcode content argument
	 *
	 * @return string
	 */
	public function output( $args = array(), $widget_args = array(), $content = '' ) {
        global $post, $preview;
		
		if ( !geodir_is_page( 'detail' ) || $preview || empty( $post ) ) {
            return;
        }
        
        /**
         * Filters the widget title.
         *
         * @since 1.0.0
         *
         * @param string $title    The widget title. Default 'Pages'.
         * @param array  $widget_args An array of the widget's settings.
         * @param mixed  $id_base  The widget ID.
         */
       // $title = apply_filters( 'widget_title', empty( $widget_args['title'] ) ? '' : $widget_args['title'], $widget_args, $this->id_base );
		
		$allow_roles = ! empty( $widget_args['user_roles'] ) && is_array( $widget_args['user_roles'] ) ? $widget_args['user_roles'] : array( 'owner' );
		$allow_roles = apply_filters( 'geodir_ga_widget_user_roles', $allow_roles, $widget_args, $this->id_base );
		if ( empty( $allow_roles ) ) {
			return;
		}

		if ( ! in_array( 'all', $allow_roles ) ) {
			$user_id = is_user_logged_in() ? get_current_user_id() : 0;
			if ( empty( $user_id ) ) {
				return;
			}
			
			$allow = false;
			if ( ! empty( $post->post_author ) && $post->post_author == $user_id && in_array( 'owner', $allow_roles ) ) {
				$allow = true; // Listing owner
			}

			if ( ! $allow ) {
				$user_data = get_userdata( $user_id );
				if ( empty( $user_data->roles ) ) {
					return;
				}
				
				$allow = false;
				foreach ( $user_data->roles as $user_role ) {
					if ( in_array( $user_role, $allow_roles ) ) {
						$allow = true;
						break;
					}
				}
			}
			
			if ( ! $allow ) {
				return;
			}
		}

		// options
		$defaults = array(
			'title'      => '',
			'button_text' => '',
			'user_roles'  => '',
		);

		/**
		 * Parse incoming $args into an array and merge it with $defaults
		 */
		$options = wp_parse_args( $args, $defaults );
		
        ob_start();
        
        do_action( 'geodir_widget_before_gd_google_analytics' );
        
        geodir_ga_display_analytics($options);
        
        do_action( 'geodir_widget_after_gd_google_analytics' );
        
        return ob_get_clean();
    }

}
