<?php
/**
 * The Message Manager template for a speaker page
 */

$items = Message_Manager::get_items_from_posts(Message_Manager::$SERIES_OPT_NONE);
?>
<?php get_header(); ?>

    <div id="content" class="clearfix message_manager_speaker">

        <div id="main" class="eight columns clearfix" role="main">

            <h1 class="archive_title h2">
                <?php single_tag_title(); ?>
            </h1>

            <?php while (!empty($items)):
                $item = array_shift($items);
                ?>

                <article id="message-<?php Message_Manager::the_id($item); ?>" role="article">

                    <?php Message_Manager::the_image($item, array('align' => 'right')); ?>

                    <header>
                        <a href="<?php Message_Manager::the_link($item); ?>" rel="bookmark"><h4>
                                <?php Message_Manager::the_title($item); ?>
                            </h4></a> <span><?php Message_Manager::the_date($item); ?> </span>
                    </header>
                    <!-- end article header -->

                    <section class="post_content">

                        <?php Message_Manager::the_excerpt($item); ?>
                    </section>
                    <!-- end article section -->

                    <footer></footer>
                    <!-- end article footer -->

                </article>
                <!-- end article -->

            <?php endwhile; ?>

            <?php if (function_exists('page_navi')) { // if expirimental feature is active ?>

                <?php page_navi(); // use the page navi function ?>

            <?php } else { // if it is disabled, display regular wp prev & next links ?>
                <nav class="wp-prev-next">
                    <ul class="clearfix">
                        <li class="prev-link"><?php next_posts_link(_e('&laquo; Older Entries', "bonestheme")) ?></li>
                        <li class="next-link"><?php previous_posts_link(_e('Newer Entries &raquo;', "bonestheme")) ?></li>
                    </ul>
                </nav>
            <?php } ?>

        </div>
        <!-- end #main -->

        <div class="sidebar four columns" role="complementary">

            <div class="panel">

                <h5>&larr;<a href="<?php Message_Manager::the_link(); ?>" title="Return To Messages">Return to
                        Messages</a></h5>

                <hr>
                <h4>All Speakers:</h4>
                <?php Message_Manager::the_speaker_list(); ?>
            </div>

        </div>
    </div>
    <!-- end #content -->

<?php get_footer(); ?>