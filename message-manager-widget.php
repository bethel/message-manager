<?php
class Message_Manager_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
				'message_manager_widget', // Base ID
				'Message Manager Widget', // Name
				array( 'description' => 'Displays message manager messages.' )
		);
	}

	public function widget( $args, $instance ) {
		extract($args);
		$title = apply_filters('widget_title', $instance['title']);
		
		$item = Message_Manager::get_latest_message();
		if (!empty($item)) {
			if (!empty($item['post_title'])) {
				$title = str_ireplace('{title}', strip_tags($item['post_title']), $title);
			}
			include Message_Manager::find_theme_path('widget.php');
		}
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = strip_tags( $new_instance['title'] );
		
		return $instance;
	}

	public function form( $instance ) {
		if (isset($instance['title'])) {
			$title = $instance['title'];
		} else { 
			$title = 'Message: {title}'; 
		}
		?>
<p>
	<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?>
	</label> <input class="widefat"
		id="<?php echo $this->get_field_id( 'title' ); ?>"
		name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
		value="<?php echo esc_attr( $title ); ?>" />
</p>
<?php 
	}
}