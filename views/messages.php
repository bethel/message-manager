<?php
/**
 * The Message Manager template the the main message page
 */
?>
<?php get_header();

?>

    <div class="row">
        <div id="primary" class="large-12 columns content-area" role="main">

            <header class="archive-header">
                <h1 class="archive-title"><?php _e('Messages', 'message-manager'); ?></h1>
            </header>

            <div class="row">
                <div class="large-4 columns">
                    <div class="panel">
                        <h5><?php _e('Latest Message', 'message-manager'); ?></h5>
                        <?php $message = mm_get_latest_message(); ?>
                        <a href="<?php mm_the_permalink(false, $message) ?>"
                           title="<?php echo esc_html(mm_the_title(false . $message)) ?>">
                            <?php mm_the_thumbnail(MM_CPT_MESSAGE . '_large', $attr = '', false, $message); ?>
                            <h4><?php mm_the_title(false, $message) ?></h4>
                            <span><?php mm_the_date(false, $message); ?></span>
                        </a>
                    </div>
                </div>
                <div class="large-4 columns">
                    <div class="panel">
                        <h5>Bethel Live</h5>
                        <a href="http://live.bethelfc.com" title="Watch Bethel Live">
                            <img
                                src="<?php echo Message_Manager::get_instance()->locate_view_url('watch_bethel_live_small.jpg') ?>"
                                title="Watch Bethel Live"/>
                        </a>

                        <p style="margin-top:14px;">Each Sunday, services are broadcast live at both 9:00 a.m. and 10:45
                            a.m.</p>
                    </div>
                </div>
                <div class="large-4 columns">
                    <div class="panel">
                        <h5>Subscribe</h5>
                        <ul>
                            <li> Subscribe using <a target="_blank" title="Subscribe to Podcast in   iTunes"
                                                    href="http://itunes.apple.com/podcast/bethel-church-fargo-nd-sermons/id360529763">iTunes</a>
                            </li>
                            <li>Subscribe using <a title="Subscribe to sermons RSS feed podcast"
                                                   href="http://feeds.feedburner.com/bethelchurchsermons"
                                                   target="_blank">RSS</a>&nbsp;
                            </li>
                            <li>Subscribe by <a
                                    href="http://feedburner.google.com/fb/a/mailverify?uri=bethelchurchsermons&amp;loc=en_US">email</a>
                            </li>
                        </ul>
                        <p>Visit our <a href="<?php echo home_url('/podcast'); ?>" title="Podcast">podcast page</a> if
                            you
                            have questions about podcasting.</p>

                        <p>Sermons can also be heard on LIFE 97.9 FM, Sundays at 8:30 a.m. and on FAITH 1200 AM Sundays
                            at
                            9:00 a.m. and 8:00 p.m.</p>
                    </div>
                </div>
            </div>

            <hr>

            <h3>All Messages</h3>

            <ul class="large-block-grid-4 small-block-grid-2 mm-message-grid">
                <?php while (have_posts()): the_post() ?>
                    <li>
                        <a href="<?php mm_the_permalink() ?>" title="<?php echo esc_html(mm_the_title()) ?>">
                            <?php mm_the_thumbnail(); ?>
                            <h4><?php mm_the_title() ?></h4>
                            <span><?php mm_the_date(); ?></span>
                        </a>
                    </li>
                <?php endwhile ?>
            </ul>
        </div>
        <!-- end #primary -->
    </div>
    <!-- end row -->

<?php get_footer(); ?>