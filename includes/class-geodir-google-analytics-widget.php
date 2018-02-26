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
            'block-icon'    => 'location-alt',
            'block-category'=> 'common',
            'block-keywords'=> "['analytics','geodir','geodirectory','google','google analytics']",
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
            ),
            'arguments'     => array(
				'title'  => array(
					'name' => 'title',
					'title' => __('Title:', 'geodir-ga'),
					'desc' => __('The widget title:', 'geodir-ga'),
					'type' => 'text',
					'placeholder' => __('The widget placeholder', 'geodir-ga'),
					'desc_tip' => true,
					'default'  => '',
					'advanced' => false
				),
				'user_roles'  => array(
                    'name' => 'user_roles', 'geodir-ga',
                    'title' => __('Google Analytics visible to:', 'geodir-ga'),
                    'desc' => __('Google Analytics will be visible to selected users only.', 'geodir-ga'),
                    'type' => 'checkbox',
                    'placeholder' => '',
                    'desc_tip' => true,
                    'default'  => '0',
                    'advanced' => false
                )
			),
			'geodirectory' => true,
        );


        parent::__construct( $options );
		
		$widget_ops = array(
            'classname' => 'geodir-widget gd-widget-detail-google-analytics',
            'description' => __( 'Show google analytics on detail page.', 'geodir-ga' ),
            'customize_selective_refresh' => true,
            'geodirectory' => true,
            'gd_show_pages' => array( 'detail' ),
        );
        //parent::__construct( 'gd_google_analytics', __( 'GD > Detail Google Analytics', 'geodir-ga' ), $widget_ops );
    }

    /**
     * Outputs the content for the current Detail Google Analytics widget instance.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $args     Display arguments including 'before_title', 'after_title',
     *                        'before_widget', and 'after_widget'.
     * @param array $instance Settings for the current Detail Rating widget instance.
     */
    public function widget( $args, $instance ) {
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
         * @param array  $instance An array of the widget's settings.
         * @param mixed  $id_base  The widget ID.
         */
        $title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
		
		$allow_roles = ! empty( $instance['user_roles'] ) && is_array( $instance['user_roles'] ) ? $instance['user_roles'] : array( 'owner' );
		$allow_roles = apply_filters( 'geodir_ga_widget_user_roles', $allow_roles, $instance, $this->id_base );
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
        
        ob_start();
        
        do_action( 'geodir_widget_before_gd_google_analytics' );
        
        geodir_ga_display_analytics();
        
        do_action( 'geodir_widget_after_gd_google_analytics' );
        
        $content = ob_get_clean();
        
        $content = trim( $content );
        if ( empty( $content ) ) {
            return;
        }
        
        echo $args['before_widget'];
        
        if ( $title ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        
        echo $content;
        
        echo $args['after_widget'];
    }

    /**
     * Handles updating settings for the current Detail Google Analytics widget instance.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $new_instance New settings for this instance as input by the user via
     *                            WP_Widget::form().
     * @param array $old_instance Old settings for this instance.
     * @return array Updated settings to save.
     */
    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        
        $instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['user_roles'] = $new_instance['user_roles'];
		
		if ( in_array( 'all', $instance['user_roles'] ) ) {
			$instance['user_roles'] = array( 'all' );
		}

        return $instance;
    }
    
    /**
     * Outputs the settings form for the Detail Google Analytics widget.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $instance Current settings.
     */
    public function form( $instance ) {
        // Defaults
        $instance = wp_parse_args( (array)$instance, 
            array( 
                'title' => '',
				'user_roles' => array(
					'owner'
				)
            )
        );
		
		$roles = geodir_user_roles();
		$roles = array_merge( array( 'all' => __( 'Everyone', 'geodir-fa' ), 'owner' => __( 'Listing Owner', 'geodir-fa' ) ), $roles );

        $title = sanitize_text_field( $instance['title'] );
		$user_roles = $instance['user_roles'];

		if ( empty( $user_roles ) || ! is_array( $user_roles ) ) {
			$user_roles = array( 'owner' );
		}
        ?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:', 'geodir-ga' ); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></p>
		<p style="margin-bottom:3px"><label for="<?php echo $this->get_field_id('user_roles'); ?>"><?php _e( 'Google Analytics visible to:', 'geodir-ga' ); ?></label>
		<?php foreach ( $roles as $value => $name ) { ?>
			<p style="margin:0;padding:0 0 0 20px">
				<label for="<?php echo $this->get_field_id('user_roles');?>_<?php echo $value; ?>">
					<input type="checkbox" id="<?php echo $this->get_field_id('user_roles');?>_<?php echo $value; ?>" name="<?php echo $this->get_field_name('user_roles'); ?>[]" <?php checked( in_array( $value, $user_roles ), true ); ?> value="<?php echo $value; ?>"/>&nbsp;<?php echo $name; ?>
				</label>
			</p>
		<?php } ?>
        </p>
        <?php
    }
}

/**
 * GeoDir_Google_Analytics_Widget class.
 *
 * @since 1.0.0
 */
class GeoDir_Google_Analytics_Widget_OLD extends WP_Widget {
    
    /**
     * Sets up a new Detail Google Analytics widget instance.
     *
     * @since 1.0.0
     * @access public
     */
    public function __construct() {
        $widget_ops = array(
            'classname' => 'geodir-widget gd-widget-detail-google-analytics',
            'description' => __( 'Show google analytics on detail page.', 'geodir-ga' ),
            'customize_selective_refresh' => true,
            'geodirectory' => true,
            'gd_show_pages' => array( 'detail' ),
        );
        parent::__construct( 'gd_google_analytics', __( 'GD > Detail Google Analytics', 'geodir-ga' ), $widget_ops );
    }

    /**
     * Outputs the content for the current Detail Google Analytics widget instance.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $args     Display arguments including 'before_title', 'after_title',
     *                        'before_widget', and 'after_widget'.
     * @param array $instance Settings for the current Detail Rating widget instance.
     */
    public function widget( $args, $instance ) {
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
         * @param array  $instance An array of the widget's settings.
         * @param mixed  $id_base  The widget ID.
         */
        $title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
		
		$allow_roles = ! empty( $instance['user_roles'] ) && is_array( $instance['user_roles'] ) ? $instance['user_roles'] : array( 'owner' );
		$allow_roles = apply_filters( 'geodir_ga_widget_user_roles', $allow_roles, $instance, $this->id_base );
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
        
        ob_start();
        
        do_action( 'geodir_widget_before_gd_google_analytics' );
        
        geodir_ga_display_analytics();
        
        do_action( 'geodir_widget_after_gd_google_analytics' );
        
        $content = ob_get_clean();
        
        $content = trim( $content );
        if ( empty( $content ) ) {
            return;
        }
        
        echo $args['before_widget'];
        
        if ( $title ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        
        echo $content;
        
        echo $args['after_widget'];
    }

    /**
     * Handles updating settings for the current Detail Google Analytics widget instance.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $new_instance New settings for this instance as input by the user via
     *                            WP_Widget::form().
     * @param array $old_instance Old settings for this instance.
     * @return array Updated settings to save.
     */
    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        
        $instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['user_roles'] = $new_instance['user_roles'];
		
		if ( in_array( 'all', $instance['user_roles'] ) ) {
			$instance['user_roles'] = array( 'all' );
		}

        return $instance;
    }
    
    /**
     * Outputs the settings form for the Detail Google Analytics widget.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $instance Current settings.
     */
    public function form( $instance ) {
        // Defaults
        $instance = wp_parse_args( (array)$instance, 
            array( 
                'title' => '',
				'user_roles' => array(
					'owner'
				)
            )
        );
		
		$roles = geodir_user_roles();
		$roles = array_merge( array( 'all' => __( 'Everyone', 'geodir-fa' ), 'owner' => __( 'Listing Owner', 'geodir-fa' ) ), $roles );

        $title = sanitize_text_field( $instance['title'] );
		$user_roles = $instance['user_roles'];

		if ( empty( $user_roles ) || ! is_array( $user_roles ) ) {
			$user_roles = array( 'owner' );
		}
        ?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:', 'geodir-ga' ); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></p>
		<p style="margin-bottom:3px"><label for="<?php echo $this->get_field_id('user_roles'); ?>"><?php _e( 'Google Analytics visible to:', 'geodir-ga' ); ?></label>
		<?php foreach ( $roles as $value => $name ) { ?>
			<p style="margin:0;padding:0 0 0 20px">
				<label for="<?php echo $this->get_field_id('user_roles');?>_<?php echo $value; ?>">
					<input type="checkbox" id="<?php echo $this->get_field_id('user_roles');?>_<?php echo $value; ?>" name="<?php echo $this->get_field_name('user_roles'); ?>[]" <?php checked( in_array( $value, $user_roles ), true ); ?> value="<?php echo $value; ?>"/>&nbsp;<?php echo $name; ?>
				</label>
			</p>
		<?php } ?>
        </p>
        <?php
    }
}
