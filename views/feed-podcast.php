<?php
/**
 * The Podcast Feed Template for Message Manager
 */

header("Content-Type: application/rss+xml; charset=UTF-8");
echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

    <rss xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" xmlns:atom="http://www.w3.org/2005/Atom"
         version="2.0">
        <channel>
            <title><?php mm_the_podcast_title(); ?></title>
            <link><?php mm_the_podcast_link(); ?></link>
            <atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml"/>
            <language><?php mm_the_podcast_language(); ?></language>
            <copyright><?php mm_the_podcast_copyright(); ?></copyright>
            <itunes:subtitle><?php mm_the_podcast_subtitle(); ?></itunes:subtitle>
            <itunes:author><?php mm_the_podcast_author(); ?></itunes:author>
            <itunes:summary><?php mm_the_podcast_summary(); ?></itunes:summary>
            <description><?php mm_the_podcast_description(); ?></description>
            <itunes:owner>
                <itunes:name><?php mm_the_podcast_owner_name(); ?></itunes:name>
                <itunes:email><?php mm_the_podcast_owner_email(); ?></itunes:email>
            </itunes:owner>
            <itunes:explicit>no</itunes:explicit>
            <?php mm_the_podcast_image(); ?>
            <?php mm_the_podcast_keywords(); ?>
            <?php mm_the_podcast_categories(); ?>
            <?php while (have_posts()) : the_post(); ?>
                <?php $audio = mm_get_the_podcast_audio(); ?>
                <?php if ($audio): ?>
                    <item>
                        <title><?php mm_the_title_rss() ?></title>
                        <link><?php the_permalink() ?></link>
                        <description><?php mm_the_description_rss(); ?></description>
                        <itunes:summary><?php mm_the_description_rss(); ?></itunes:summary>
                        <?php mm_the_author_rss(); ?>
                        <?php mm_the_date_rss(); ?>
                        <?php mm_the_topics_rss(); ?>
                        <?php mm_the_image_rss() ?>
                        <?php mm_the_enclosure_rss($audio); ?>
                    </item>
                <?php endif; ?>
                <?php $attachments = mm_get_the_podcast_attachments(); ?>
                <?php foreach ($attachments as $attachment): ?>
                    <item>
                        <title><?php mm_the_title_rss($attachment); ?></title>
                        <description><?php mm_the_description_rss($attachment); ?></description>
                        <itunes:summary><?php mm_the_description_rss($attachment); ?></itunes:summary>
                        <?php mm_the_date_rss(); ?>
                        <?php mm_the_topics_rss(); ?>
                        <?php mm_the_enclosure_rss($attachment); ?>
                    </item>
                <?php endforeach; ?>
            <?php endwhile; ?>
        </channel>
    </rss>
<?php
exit;