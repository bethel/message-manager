<?php 
/**
 * The Message Manager template the the main message page
 */

$items = Message_Manager::get_items_from_posts(true);
?>
<?php get_header(); ?>

			<div id="content" class="clearfix message_manager">
			
				<div id="main" class="twelve columns clearfix" role="main">
					<h1>Messages</h1>
			
					<?php
					$i = 1;
					while(!empty($items)):
					$item = array_shift($items);
					$end = empty($items);
					?>
					
					<div class="three columns<?php echo ($end)? ' end' : ''; ?> message_manager_series_box">
		
						<a href="<?php Message_Manager::the_link($item); ?>">
							<?php Message_Manager::the_image($item, Message_Manager::$tax_series); ?>
							<h4><?php Message_Manager::the_title($item); ?></h4>
							<span><?php Message_Manager::the_date($item); ?></span>
						</a>
		
					</div>
			
					<?php if(!($i % 4) || $end): ?>
					<div class="clearfix"></div>
					<?php endif;?>
					
					<?php $i++; endwhile; ?>
			
				</div>
				<!-- end #main -->
			
			</div>
			<!-- end #content -->

<?php get_footer(); ?>