<?php 
/**
 * The Message Manager template for individual messages
 */

$item = array_pop(Message_Manager::get_items_from_posts());
$message = null;
$series = null;

if (!empty($item['messages'])) {
	$series = $item;
	$message = array_pop($item['messages']);
} else {
	$message = $item;
}

?>
<?php get_header(); ?>

<div id="content" class="clearfix">

	<div id="main" class="twelve columns clearfix" role="main">

		<?php if (!empty($message)): ?>


		<?php if (Message_Manager::has_video($message)): ?>
		<div class="row">
			<div class="eight columns">
				<?php Message_Manager::the_video($message); ?>
			</div>
			<div class="four columns">
				<?php Message_Manager::the_recent_series_list($series, $message); ?>
			</div>
		</div>

		<div class="row">
			<div class="eight columns">
				<hr>

				<a href="<?php Message_Manager::the_link($series); ?>"
					title="<?php Message_Manager::the_title($series); ?>"><h4
						class="subheader">
						<?php Message_Manager::the_title($series); ?>
					</h4> </a>

				<h1>
					<?php Message_Manager::the_title($message); ?>
				</h1>

				<dl class="tabs two-up">
					<dd class="active">
						<a href="#description">DESCRIPTION</a>
					</dd>
					<dd>
						<a href="#downloads">DOWNLOADS</a>
					</dd>
				</dl>

				<ul class="tabs-content">
					<li class="active" id="descriptionTab">
					
						<span class="meta"><?php Message_Manager::the_speakers($message); ?> / <?php Message_Manager::the_date($message); ?></span>
					
						<?php Message_Manager::the_content($message); ?>
						
						<span class="topics"><?php Message_Manager::the_topics($message); ?></span>
					
					</li>
					<li id="downloadsTab">
						<?php Message_Manager::the_downloads($message); ?>
					</li>
				</ul>
			</div>
			<div class="four columns">
				<hr>

				<h4>Subscribe</h4>

				<hr>

				<h4>Share</h4>

			</div>
		</div>
		<?php else: ?>
		<div class="row">
			<div class="eight columns">
				<hr>

				<a href="<?php Message_Manager::the_link($series); ?>"
					title="<?php Message_Manager::the_title($series); ?>"><h4
						class="subheader">
						<?php Message_Manager::the_title($series); ?>
					</h4> </a>

				<h1>
					<?php Message_Manager::the_title($message); ?>
				</h1>
				
				
				<?php Message_Manager::the_audio($message); ?>
				<p></p>

				<dl class="tabs two-up">
					<dd class="active">
						<a href="#description">DESCRIPTION</a>
					</dd>
					<dd>
						<a href="#downloads">DOWNLOADS</a>
					</dd>
				</dl>

				<ul class="tabs-content">
					<li class="active" id="descriptionTab">
					
						<span class="meta"><?php Message_Manager::the_speakers($message); ?> / <?php Message_Manager::the_date($message); ?></span>
					
						<?php Message_Manager::the_content($message); ?>
						
						<span class="topics"><?php Message_Manager::the_topics($message); ?></span>
					
					</li>
					<li id="downloadsTab">
						<?php Message_Manager::the_downloads($message); ?>
					</li>
				</ul>
			</div>
			
			<div class="four columns">
				<?php Message_Manager::the_recent_series_list($series, $message); ?>
				
				<hr>

				<h4>Subscribe</h4>

				<hr>

				<h4>Share</h4>
				<!-- AddThis Button BEGIN -->
						<div class="addthis_toolbox addthis_counter_style">
						<a class="addthis_button_facebook_like" fb:like:layout="box_count"></a>
						<a class="addthis_button_tweet" tw:count="vertical"></a>
						<a class="addthis_button_google_plusone" g:plusone:size="tall"></a>
						<a class="addthis_counter"></a>
						</div>
						<script type="text/javascript">
						var addthis_config = { ui_click:true };
						</script>
						<script type="text/javascript" src="https://s7.addthis.com/js/250/addthis_widget.js#pubid=xa-50482fcb481a8273"></script>
						<!-- AddThis Button END -->
			</div>
		</div>
		<?php endif; ?>


	</div>
	<?php endif; ?>

</div>
<!-- end #content -->

<?php get_footer(); ?>