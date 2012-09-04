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
						<th scope="row"><label for="<?php name('base-name'); ?>">Basename:</label></th>
						<td><span class="description" style="margin-right: 2px;"><?php echo get_site_url(); ?>/</span><input type="text" name="<?php name('message-slug'); ?>" value="<?php option('message-slug', 'messages'); ?>" class="regular-text" />
							<p class="description">Enter the location of where the message should show up on your site.</p>
						</td>
					</tr>
				
				
				
				
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
							<p class="description">Enter the location of where series should show up on your site.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php name('topic-slug'); ?>">Topic Slug:</label></th>
						<td><span class="description" style="margin-right: 2px;"><?php echo get_site_url(); ?>/</span><input type="text" name="<?php name('topic-slug'); ?>" value="<?php option('topic-slug', 'messages/topics'); ?>" class="regular-text" />
							<p class="description">Enter the location of where topics should show up on your site.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php name('venue-slug'); ?>">Venue Slug:</label></th>
						<td><span class="description" style="margin-right: 2px;"><?php echo get_site_url(); ?>/</span><input type="text" name="<?php name('venue-slug'); ?>" value="<?php option('venue-slug', 'messages/venues'); ?>" class="regular-text" />
							<p class="description">Enter the location of where venues should show up on your site.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php name('book-slug'); ?>">Book Slug:</label></th>
						<td><span class="description" style="margin-right: 2px;"><?php echo get_site_url(); ?>/</span><input type="text" name="<?php name('book-slug'); ?>" value="<?php option('book-slug', 'messages/books'); ?>" class="regular-text" />
							<p class="description">Enter the location of where books should show up on your site.</p>
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
				
				<h3>Podcasting</h3>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="<?php name('podcast-title'); ?>">Title:</label></th>
						<td><input type="text" name="<?php name('podcast-title'); ?>" value="<?php option('podcast-title', get_bloginfo_rss('name') . ' ' . get_bloginfo_rss()); ?>" class="regular-text" />
							<p class="description">The title of your podcast channel.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php name('podcast-subtitle'); ?>">Subtitle:</label></th>
						<td><input type="text" name="<?php name('podcast-subtitle'); ?>" value="<?php option('podcast-subtitle', get_bloginfo('description')); ?>" class="regular-text" />
							<p class="description">The podcast subtitle.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php name('podcast-link'); ?>">Link:</label></th>
						<td><input type="text" name="<?php name('podcast-link'); ?>" value="<?php option('podcast-link', home_url('/')); ?>" class="regular-text" />
							<p class="description">The link for your podcast channel.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php name('podcast-language'); ?>">Language:</label></th>
						<td><input type="text" name="<?php name('podcast-language'); ?>" value="<?php option('podcast-language', get_bloginfo_rss('language')); ?>" class="regular-text" />
							<p class="description">The language of your padcast channel. See <a href="http://www.rssboard.org/rss-language-codes">here</a> for valid language codes.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php name('podcast-copyright'); ?>">Copyright:</label></th>
						<td><input type="text" name="<?php name('podcast-copyright'); ?>" value="<?php option('podcast-copyright', '&#x2117; &amp; &#xA9; ' . get_bloginfo_rss('name')); ?>" class="regular-text" />
							<p class="description">The copyright information of your podcast content.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php name('podcast-author'); ?>">Author:</label></th>
						<td><input type="text" name="<?php name('podcast-author'); ?>" value="<?php option('podcast-author', get_bloginfo_rss('name')); ?>" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php name('podcast-keywords'); ?>">Keywords:</label></th>
						<td><input type="text" name="<?php name('podcast-keywords'); ?>" value="<?php option('podcast-keywords'); ?>" class="regular-text" />
							<p class="description">A comma seperated list of keywords for your channel.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php name('podcast-description'); ?>">Description:</label></th>
						<td><textarea name="<?php name('podcast-description'); ?>" class="regular-text"><?php option('podcast-description', get_bloginfo_rss('description')); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php name('podcast-owner-name'); ?>">Owner Name:</label></th>
						<td><input type="text" name="<?php name('podcast-owner-name'); ?>" value="<?php option('podcast-owner-name', get_bloginfo_rss('name')); ?>" class="regular-text" />
							<p class="description">The name of the podcast owner. Will likly be the same as Author.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php name('podcast-owner-email'); ?>">Owner Email:</label></th>
						<td><input type="text" name="<?php name('podcast-owner-email'); ?>" value="<?php option('podcast-owner-name', get_bloginfo_rss('admin_email')); ?>" class="regular-text" />
							<p class="description">The email address of the owner.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php name('podcast-image'); ?>">Image:</label></th>
						<td><input type="text" name="<?php name('podcast-image'); ?>" value="<?php option('podcast-image'); ?>" class="regular-text" />
							<p class="description">The podcast channel's image.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php name('podcast-categories'); ?>">Description:</label></th>
						<td><textarea name="<?php name('podcast-categories'); ?>" class="regular-text"><?php option('podcast-categories', 'Religion & Spirituality => Christianity, Spirituality'); ?></textarea>
							<p class="description">Specify each top category as a new line. You may add subcategories by using the => operator and then specifying a comma seperated list.</p>
						</td>
					</tr>
				</table>
				
				<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="Save Changes"></p>
			</form>
		</div>
	<?php	
	}
}