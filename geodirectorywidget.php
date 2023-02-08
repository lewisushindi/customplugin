<?php

class Geodirectory_Image_Widget extends WP_Widget {

  function __construct() {
    parent::__construct(
      'Geodirectory_Image_widget',
      __('Geodirectory Image Widget', 'text_domain'),
      array( 'description' => __( 'A widget to display geodirectory images', 'text_domain' ), )
    );
  }

  public function widget( $args, $instance ) {
    $title = apply_filters( 'widget_title', $instance['title'] );
    echo $args['before_widget'];
    if ( ! empty( $title ) ) {
      echo $args['before_title'] . $title . $args['after_title'];
    }
    echo __( 'Hello, World!', 'text_domain' );
    echo $args['after_widget'];
  }

  public function form( $instance ) {
    $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Geodirectory Images', 'text_domain');
    ?>
    <p>
    <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'text_domain' ); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
    </p>
    <?php
    }
    
    public function update( $new_instance, $old_instance ) {
    $instance = array();
    $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
    return $instance;
    }
    
    }
    
    // Register Geodirectory_Image_Widget widget
    function register_geodirectory_image_widget() {
    register_widget( 'Geodirectory_Image_Widget' );
    }
    add_action( 'widgets_init', 'register_geodirectory_image_widget' );

