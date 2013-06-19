<?php
/**
 * The Message Manager template for individual messages
 */
?>
<?php get_header(); ?>
    <div class="row">
        <?php while (have_posts()) : the_post(); ?>
            <div id="primary" class="large-8 columns content-area" role="main">
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?> role="article" itemscope
                         itemtype="http://schema.org/BlogPosting">

                    <?php if (mm_has_video()): ?>
                        <div class="entry-video">
                            <?php mm_the_video() ?>
                        </div>
                        <hr>
                    <?php endif ?>

                    <header class="entry-header">
                        <?php if (!post_password_required()) : ?>
                            <div class="entry-thumbnail">
                                <?php mm_the_thumbnail(MM_CPT_MESSAGE, array('align' => 'right')); ?>
                            </div>
                        <?php endif; ?>

                        <h1 class="entry-title" itemprop="headline"><?php mm_the_title(); ?></h1>

                        <div class="entry-meta">
                            <?php mm_the_meta(); ?>
                        </div>
                        <!-- .entry-meta -->
                    </header>
                    <!-- .entry-header -->

                    <?php if (mm_has_audio()) : ?>
                        <div class="entry-audio">
                            <?php mm_the_audio(); ?>
                        </div>
                    <?php endif; ?>

                    <p></p>

                    <section itemprop="articleBody">
                        <div class="entry-content">
                            <?php mm_the_content(); ?>
                        </div>
                        <!-- .entry-content -->
                    </section>
                </article>
                <!-- #post -->
            </div>
            <!-- end #primary -->
            <div class="large-4 columns sidebar mm-sidebar" role="complementary">
                <?php mm_the_back_button(); ?>
                <div class="panel">
                    <?php mm_the_series_list(); ?>
                    <?php mm_the_downloads(); ?>

                    <div class="mm-share-widget">
                        <h4>Share</h4>
                        <!-- AddThis Button BEGIN -->
                        <div class="addthis_toolbox addthis_counter_style">
                            <a class="addthis_button_facebook_like" fb:like:layout="box_count"
                               style="float:left; z-index:100000;"></a>
                            <a class="addthis_button_google_plusone" g:plusone:size="tall"
                               style="float:left; margin-left: 5px;"></a>
                            <a class="addthis_counter" style="float:left; margin-left: 10px;"></a>
                        </div>
                        <script type="text/javascript">
                            var addthis_config = { ui_click: true };
                        </script>
                        <script type="text/javascript"
                                src="https://s7.addthis.com/js/250/addthis_widget.js#pubid=xa-50482fcb481a8273"></script>
                        <!-- AddThis Button END -->
                    </div>

                    </ul>
                </div>
            </div>
            <!-- end sidebar -->
        <?php endwhile; ?>
    </div>
    <!-- end row -->
<?php get_footer(); ?>