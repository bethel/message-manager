<?php 
/**
 * The Message Manager template the the main message page
 */

$items = Message_Manager::get_items_from_posts(Message_Manager::$SERIES_OPT_ONLY);
?>
<?php get_header(); ?>

<div id="content" class="clearfix message_manager">

	<div id="main" class="twelve columns clearfix" role="main">
		<h1>Messages</h1>


			<div class="four columns">

				<div class="panel">
					
					<h5>Latest Message</h5>
					
					<?php $latest = Message_Manager::get_latest_message(); ?>
					
					<a href="<?php Message_Manager::the_link($latest); ?>"> <?php Message_Manager::the_image($latest, array('size'=>'bethel-home-box')); ?>
						<h4>
							<?php Message_Manager::the_title($latest); ?>
						</h4> <span><?php Message_Manager::the_date($latest); ?> </span>
					</a>

				</div>

			</div>

			<div class="four columns">

				<div class="panel">
					<h5>Bethel Live</h5>
					<a href="http://live.bethelfc.com" title="Watch Bethel Live">
					<img src="<?php echo Message_Manager::find_theme_url('watch_bethel_live_small.jpg')?>" title="Watch Bethel Live" />
					</a>
					<p style="margin-top:14px;">Each Sunday, services are broadcast live at both 9:00 a.m. and 10:45 a.m.</p>
				</div>

			</div>

			<div class="four columns end">

				<div class="panel">
					<h5>Subscribe</h5>
					
					<ul><li> Subscribe using <a target="_blank" title="Subscribe to Podcast in   iTunes" href="http://itunes.apple.com/podcast/bethel-church-fargo-nd-sermons/id360529763">iTunes</a></li>
					<li>Subscribe using <a title="Subscribe to sermons RSS feed podcast" href="http://feeds.feedburner.com/bethelchurchsermons" target="_blank">RSS</a>&nbsp;</li>
					<li>Subscribe by <a href="http://feedburner.google.com/fb/a/mailverify?uri=bethelchurchsermons&amp;loc=en_US">email</a> </li>
					</ul>
					
					<p>Visit our <a href="<?php echo home_url('/podcast'); ?>" title="Podcast">podcast page</a> if you have questions about podcasting.</p>
					<p>Sermons can also be heard on LIFE 97.9 FM, Sundays at 8:30 a.m. and on FAITH 1200 AM Sundays at 9:00 a.m. and 8:00 p.m.</p>
				</div>

			</div>
			
			<hr>

		

		<h3>All Messages</h3>

		<?php
		$i = 1;
		while(!empty($items)):
		$item = array_shift($items);
		$end = empty($items);
		?>

		<div class="three columns<?php echo ($end)? ' end' : ''; ?> message_manager_series_box">

			<a href="<?php Message_Manager::the_link($item); ?>"> <?php Message_Manager::the_image($item, array('size' => Message_Manager::$tax_series)); ?>
				<h4>
					<?php Message_Manager::the_title($item); ?>
				</h4> <span><?php Message_Manager::the_date($item); ?> </span>
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