<?php 
/**
 * The Podcast Feed Template for Message Manager
 */

require_once(Message_Manager::$path.'includes/encoding.php');

function mm_feed_sanitize($text) {
	
		$text = strip_tags($text);
	
		$pattern[] = '/&nbsp;/';
        $replacement[] = ' ';
        $pattern[] = '/&ldquo;/';
        $replacement[] = '"';
        $pattern[] = '/&rdquo;/';
        $replacement[] = '"';
        $pattern[] = '/&lsquo;/';
        $replacement[] = "'";
        $pattern[] = '/&rsquo;/';
        $replacement[] = "'";
        $pattern[] = '/&[^\s]*;/'; //catch all others
        $replacement[] = "";
        $text = preg_replace($pattern, $replacement, $text);
        
        return Encoding::fixUTF8($text);
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
		<?php if (Message_Manager_Options::get('podcast-image')): 
			$image = Message_Manager_Options::get('podcast-image');
			$src = wp_get_attachment_image_src($image['id'], 'full');
			if (!empty($src)):
		?><itunes:image href="<?php echo esc_url(preg_replace('/^https/i', 'http', $src[0])); ?>" /><?php endif; endif; ?>
		
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
	
	$orig_date = strip_tags($mb->get_the_value('date'));
	$date = Message_Manager::format_date($orig_date, 'D, d M Y H:i:s O');
	if ($date == $orig_date) {
		$date = null;
	}
	
	$summary = $mb->get_the_value('summary');
	if (!$summary) {
		$summary = get_the_content();
	}
	$summary = mm_feed_sanitize(strip_tags($summary));
	
	$speakers = mm_feed_sanitize(get_the_term_list(get_the_ID(), Message_Manager::$tax_speaker, '', ' &amp; ', ''));
	$series = mm_feed_sanitize(get_the_term_list(get_the_ID(), Message_Manager::$tax_series, '', ' &amp; ', ''));
	$topics = mm_feed_sanitize( get_the_term_list(get_the_ID(), Message_Manager::$tax_topics, '', ', ', '' ));
	$topic = null;
	if (!empty($topics)) {
		$topic = mm_feed_sanitize( $topics ) ? sprintf( '<itunes:keywords>%s</itunes:keywords>', $topics ) : null;
	}
	
	$image = Message_Manager::get_the_image_rss(get_the_ID(), 'full');
	$image = preg_replace('/^https/i', 'http', $image);
	
	$mb = Message_Manager::$message_media_mb;
	$mb->the_meta();
	
	$audio_url = $mb->get_the_value('audio-url');
	$audio_info = Message_Manager::get_id3_info($audio_url);
	
	$audio_url = preg_replace('/^https/i', 'http', $audio_url);
	
	$audio_duration = false;
	if (!empty($audio_info['playtime_seconds'])) {
		$audio_duration = gmdate("H:i:s", round($audio_info['playtime_seconds']));
	}
	
	$audio_size = false;
	if (!empty($audio_info['filesize'])) {
		$audio_size = $audio_info['filesize'];
	}
	
	$attachments = get_post_meta(get_the_ID(), Message_Manager::$message_attachments_mb->get_the_id(), TRUE);
	$attachments = $attachments['attachment'];
	
	$pdf_attachments = array();
	
	if (!empty($attachments)) {
		foreach ($attachments as $attachment) {		
			if (empty($attachment['url'])) continue;
			$pathinfo = pathinfo($attachment['url']);
			
			if (!empty($pathinfo['extension'])) {
				if (strtolower($pathinfo['extension']) == 'pdf') {
					$attachment['size'] = Message_Manager::get_file_size($attachment['url']);
					$pdf_attachments[] = $attachment;
				}
			}
		}
	}
?>
<?php if (!empty($audio_url) && !empty($audio_duration) && !empty($audio_size)): ?>
		<item>
			<title><?php the_title_rss() ?></title>
			<link><?php the_permalink() ?></link>
			<description><?php echo $summary; ?></description>
			<itunes:author><?php echo $speakers; ?></itunes:author>
			<itunes:summary><?php echo $summary; ?></itunes:summary>
			<?php if (!empty($date)): ?><pubDate><?php echo $date; ?></pubDate><?php endif; ?>
			
			<enclosure url="<?php echo esc_url($audio_url); ?>" length="<?php echo $audio_size; ?>" type="audio/mpeg" />
			<guid><?php echo esc_url($audio_url); ?></guid>
			<itunes:duration><?php echo esc_html($audio_duration); ?></itunes:duration>
			<?php if (!empty($image)) : ?><itunes:image href="<?php echo $image; ?>" /><?php endif; ?>
			
			<?php if (!empty($topic)) : ?><?php echo $topic . "\n" ?><?php endif; ?>
		</item>
<?php endif; ?>
<?php if (!empty($pdf_attachments)): foreach($pdf_attachments as $attachment): extract($attachment); ?>
		<item>
			<title><?php echo get_the_title_rss() . ' - '; ?><?php echo empty($title)? mm_feed_sanitize(basename($url)): mm_feed_sanitize($title); ?></title>
			<description><?php echo mm_feed_sanitize($description); ?></description>
			<itunes:summary><?php echo mm_feed_sanitize($description); ?></itunes:summary>
			<?php if (!empty($date)): ?><pubDate><?php echo $date; ?></pubDate><?php endif; ?>
			
			<enclosure url="<?php echo esc_url($url); ?>" length="<?php echo $size; ?>" type="application/pdf" />
			<guid><?php echo esc_url($url); ?></guid>
			
			<?php if (!empty($topic)) : ?><?php echo $topic . "\n" ?><?php endif; ?>
		</item>	
<?php endforeach; endif; endwhile; endif;  ?>
	</channel>
</rss>
<?php die(); ?>