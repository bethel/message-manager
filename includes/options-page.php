<?php

class Message_Manager_Options {
	
	public static $prefix = 'mm_option_';
	
	function __construct() {
		add_action('admin_init', array($this, 'register'));
		add_action('admin_menu', array($this, 'menu'));
	}
	
	function register() {
		$this->register_option('message-slug');
		$this->register_option('speaker-slug');
	}
	
	function register_option($option, $callback = false) {
		register_setting(Message_Manager::$cpt_message, Message_Manager_Options::get_with_prefix($option), $callback);
	}
	
	function menu() {
		add_submenu_page('edit.php?post_type='.Message_Manager::$cpt_message, "Message Manager Settings", "Settings", 'manage_options', Message_Manager::$cpt_message, array($this, 'options_page'));
	}
	
	public static function get_with_prefix($option) {
		return Message_Manager_Options::$prefix.$option;
	}
	
	public static function set($name, $value) {
		return update_option(Message_Manager_Options::get_with_prefix($name), $value);
	}
	
	public static function get($name, $default = false) {
		return get_option(Message_Manager_Options::get_with_prefix($name), $default);
	}
	
	public static function delete($name) {
		return delete_option(Message_Manager_Options::get_with_prefix($name));
	}
	
	function options_page() {
		if (!current_user_can('manage_options')) {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		
		function get_name($option) {
			return Message_Manager_Options::get_with_prefix($option);
		}
		
		function name($option) {
			echo get_name($option);
		}
		
		function option($option, $default = false) {
			echo get_option(get_name($option), $default);
		}
		
		if (array_key_exists('settings-updated', $_REQUEST)) {
			flush_rewrite_rules(false);
		}
	?>
	
		<div class="wrap">
		    <?php screen_icon(); ?>	
			
		    <h2>Message Manager Options</h2>
		    
		    <form action="options.php" method="post" id="mm-options-form" name="mm-options-form">
		    	<?php settings_fields(Message_Manager::$cpt_message); ?>
	
				<h3>Permalinks</h3>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="<?php name('message-slug'); ?>">Message Slug:</label></th>
						<td><span class="description" style="margin-right: 2px;"><?php echo get_site_url(); ?>/</span><input type="text" name="<?php name('message-slug'); ?>" value="<?php option('message-slug', 'messages'); ?>" class="regular-text" />
							<p class="description">Enter the location of where the message should show up on your site.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php name('speaker-slug'); ?>">Speaker Slug:</label></th>
						<td><span class="description" style="margin-right: 2px;"><?php echo get_site_url(); ?>/</span><input type="text" name="<?php name('speaker-slug'); ?>" value="<?php option('speaker-slug', 'messages/speakers'); ?>" class="regular-text" />
							<p class="description">Enter the location of where speakers should show up on your site.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php name('series-slug'); ?>">Series Slug:</label></th>
						<td><span class="description" style="margin-right: 2px;"><?php echo get_site_url(); ?>/</span><input type="text" name="<?php name('series-slug'); ?>" value="<?php option('series-slug', 'messages/series'); ?>" class="regular-text" />
							<p class="description">Enter the location of where speakers should show up on your site.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php name('topic-slug'); ?>">Topic Slug:</label></th>
						<td><span class="description" style="margin-right: 2px;"><?php echo get_site_url(); ?>/</span><input type="text" name="<?php name('topic-slug'); ?>" value="<?php option('topic-slug', 'messages/topics'); ?>" class="regular-text" />
							<p class="description">Enter the location of where speakers should show up on your site.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php name('venue-slug'); ?>">Venue Slug:</label></th>
						<td><span class="description" style="margin-right: 2px;"><?php echo get_site_url(); ?>/</span><input type="text" name="<?php name('venue-slug'); ?>" value="<?php option('venue-slug', 'messages/venues'); ?>" class="regular-text" />
							<p class="description">Enter the location of where speakers should show up on your site.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php name('book-slug'); ?>">Book Slug:</label></th>
						<td><span class="description" style="margin-right: 2px;"><?php echo get_site_url(); ?>/</span><input type="text" name="<?php name('book-slug'); ?>" value="<?php option('book-slug', 'messages/books'); ?>" class="regular-text" />
							<p class="description">Enter the location of where speakers should show up on your site.</p>
						</td>
					</tr>
				</table>
				
				<h3>Media Player</h3>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="<?php name('message-slug'); ?>">Message Slug:</label></th>
						<td><span class="description" style="margin-right: 2px;"><?php echo get_site_url(); ?>/</span><input type="text" name="<?php name('message-slug'); ?>" value="<?php option('message-slug', 'messages'); ?>" class="regular-text" />
							<p class="description">Enter the location of where the message should show up on your site.</p>
						</td>
					</tr>
				</table>
				
				<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="Save Changes"></p>
			</form>
		</div>
	<?php	
	}
}