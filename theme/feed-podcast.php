<?php 
/**
 * The Podcast Feed Template for Message Manager
 */

function mm_feed_sanitize($text) {
	$text = htmlspecialchars_decode(htmlspecialchars(strip_tags($text), ENT_QUOTES | ENT_XML1), ENT_QUOTES | ENT_XML1);
	return $text;
}


?>
<?php header("Content-Type: application/rss+xml; charset=UTF-8"); ?>
<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

<rss xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" xmlns:atom="http://www.w3.org/2005/Atom" version="2.0">
	<channel>
		<title><?php echo esc_html(Message_Manager_Options::get('podcast-title', get_bloginfo_rss('name') . ' ' . get_bloginfo_rss())); ?></title>
		<link><?php echo esc_html(Message_Manager_Options::get('podcast-link', home_url('/'))); ?></link>
		<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
		<language><?php echo esc_html(Message_Manager_Options::get('podcast-language', get_bloginfo_rss('language'))); ?></language>
		<copyright><?php echo esc_html(Message_Manager_Options::get('podcast-copyright', '&#x2117; &amp; &#xA9; ' . get_bloginfo_rss('name'))); ?></copyright>
		<itunes:subtitle><?php echo esc_html(Message_Manager_Options::get('podcast-subtitle', get_bloginfo('description'))); ?></itunes:subtitle>
		<itunes:author><?php echo esc_html(Message_Manager_Options::get('podcast-author', get_bloginfo_rss('name'))); ?></itunes:author>
		<itunes:summary><?php echo esc_html(Message_Manager_Options::get('podcast-summary', get_bloginfo_rss('description'))); ?></itunes:summary>
		<description><?php echo esc_html(Message_Manager_Options::get('podcast-description', get_bloginfo_rss('description'))); ?></description>
		<itunes:owner>
			<itunes:name><?php echo esc_html(Message_Manager_Options::get('podcast-owner-name', get_bloginfo_rss('name'))); ?></itunes:name>
			<itunes:email><?php echo esc_html(Message_Manager_Options::get('podcast-owner-email', get_bloginfo_rss('admin_email'))); ?></itunes:email>
		</itunes:owner>
		<itunes:explicit>no</itunes:explicit>
		<?php if (Message_Manager_Options::get('podcast-image')): ?><itunes:image href="<?php echo esc_url(preg_replace('/^https/i', 'http', Message_Manager_Options::get('podcast-image'))); ?>" /><?php endif; ?>
		
		<itunes:keywords><?php echo esc_html(Message_Manager_Options::get('podcast-keywords')); ?></itunes:keywords>
		<?php 
			$categories = Message_Manager_Options::get('podcast-categories', 'Religion & Spirituality => Christianity, Spirituality');
			$categories = preg_split("/(\r\n|\n|\r)/", $categories);
			foreach ($categories as $category) {
				$peices = explode('=>', $category);
				$category = esc_attr(trim($peices[0]));
				$subcats = array();
				if (count($peices) > 1) {
					$subcats = explode(',', $peices[1]);
				}
				
				echo '<itunes:category text="'.$category.'">';
				foreach ($subcats as $subcat) {
					$subcat =  esc_attr(trim($subcat));
					if (!empty($subcat))
						echo "\n".'			<itunes:category text="'.$subcat.'" />';
				}
				echo "\n".'		</itunes:category>'."\n";
			}
		?>
<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); 
	
	$mb = Message_Manager::$message_details_mb;
	$mb->the_meta();
	
	$date = strip_tags($mb->get_the_value('date'));
	$parts = explode('-', $date);
	if ($parts >= 3) {
		$date = mktime(0, 0, 0, $parts[1], $parts[2], $parts[0]);
		$date = date('D, d M Y H:i:s O', $date);
	} else {
		$date == null;
	}
	
	$summary = $mb->get_the_value('summary');
	if (!$summary) {
		$summary = get_the_content();
	}
	$summary = mm_feed_sanitize($summary);
	
	$speakers = mm_feed_sanitize(get_the_term_list(get_the_ID(), Message_Manager::$tax_speaker, '', ' &amp; ', ''));
	$series = mm_feed_sanitize(get_the_term_list(get_the_ID(), Message_Manager::$tax_series, '', ' &amp; ', ''));
	$topics = mm_feed_sanitize( get_the_term_list(get_the_ID(), Message_Manager::$tax_topics, '', ', ', '' ));
	$topic = mm_feed_sanitize( $topics ) ? sprintf( '<itunes:keywords>%s</itunes:keywords>', $topics ) : null;
	
	$image = Message_Manager::get_the_image(get_the_ID(), 'full');
	$image = preg_replace('/^https/i', 'http', $image);
	
	$mb = Message_Manager::$message_media_mb;
	$mb->the_meta();
	
	$audio_url = $mb->get_the_value('audio-url');
	$audio_info = Message_Manager::get_id3_info($audio_url);
	
	$audio_url = preg_replace('/^https/i', 'http', $audio_url);
	
	$audio_duration = false;
	if (!empty($audio_info['playtime_string'])) {
		$audio_duration = $audio_info['playtime_string'];
	}
	
	$audio_size = false;
	if (!empty($audio_info['filesize'])) {
		$audio_size = $audio_info['filesize'];
	}
?>
<?php if (!empty($audio_url) && !empty($audio_duration) && !empty($audio_size)): ?>
		<item>
			<title><?php the_title_rss() ?></title>
			<link><?php the_permalink() ?></link>
			<description><?php echo $summary; ?></description>
			<itunes:author><?php echo $speakers; ?></itunes:author>
			<itunes:subtitle><?php echo $series; ?></itunes:subtitle>
			<itunes:summary><?php echo $summary; ?></itunes:summary>
			<?php if ($date): ?><pubDate><?php echo $date; ?></pubDate><?php endif; ?>
			
			<enclosure url="<?php echo esc_url($audio_url); ?>" length="<?php echo $audio_size; ?>" type="audio/mpeg" />
			<guid><?php echo esc_url($audio_url); ?></guid>
			<itunes:duration><?php echo esc_html($audio_duration); ?></itunes:duration>
			<?php if ($image) : ?><itunes:image href="<?php echo $image; ?>" /><?php endif; ?>
			
			<?php if ( $topic ) : ?><?php echo $topic . "\n" ?><?php endif; ?>
		</item>
<?php endif; endwhile; endif;  ?>
	</channel>
</rss>
<?php die(); ?>