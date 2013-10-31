<?php

add_action( 'widgets_init', 'VirtualPostsWidgetListing::register' );

class VirtualPostsWidgetListing extends WP_Widget {

	static function register(){

		register_widget( 'VirtualPostsWidgetListing' );

	}

	function __construct() {

		$widget_ops  = array( 'classname' => 'VirtualPostsWidgetListing', 'description' => 'List posts from cache' );
		$control_ops = array( 'id_base' => 'virtualposts-widget-listing' );
		$this->WP_Widget( 'virtualposts-widget-listing', 'Virtual Posts Listing', $widget_ops, $control_ops );

	}

	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] );

		echo wp_kses_post( $args['before_widget'] );
		if ( ! empty( $title ) )
			echo wp_kses_post( $args['before_title'] . $title . $args['after_title'] );

		$posts  = phpFastCache::get( VirtualPostsFeeds::posts_cache_key );

		// sort by pubdate
		function cmp($a, $b)
		{
			$a = strtotime( $a['date'] );
			$b = strtotime( $b['date'] );

			if ( $a[''] == $b[''] ) {
				return 0;
			}
			return ($a < $b) ? -1 : 1;
		}

		usort( $posts, "cmp" );

		foreach ( $posts as $post ) {
			echo wp_kses_post( '<div class="virtualposts_post"><a title="' . substr( strip_tags( $post['excerpt'] ) . '...', 0, 100 ) . '..." href="/virtualposts/' . $post['link'] . '"><h3>' . $post['title'] . '</h3></a></p>' );
		}

		echo wp_kses_post( $args['after_widget'] );
	}

	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'New title', 'vpp_' );
		}
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
	<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;
	}

}
