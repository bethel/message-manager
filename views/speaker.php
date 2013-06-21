<?php
/**
 * The Message Manager template for a speaker page
 */
?>
<?php get_header(); ?>
    <div class="row">
        <div id="primary" class="large-8 columns content-area" role="main">
            <?php if (have_posts()) : ?>
                <header class="archive-header">
                    <h1 class="archive-title"><?php mm_the_term_title(); ?></h1>

                    <?php if (term_description()) : // Show an optional category description ?>
                        <div class="archive-meta"><?php echo term_description(); ?></div>
                    <?php endif; ?>
                </header><!-- .archive-header -->

                <?php /* The loop */ ?>
                <?php while (have_posts()) : the_post(); ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class('clearfix'); ?> role="article">
                        <div class="row">
                            <div class="large-9 small-8 columns">
                                <header>
                                    <h4><a href="<?php mm_the_permalink(); ?>" rel="bookmark" title="<?php esc_attr(mm_the_title()); ?>"><?php mm_the_title(); ?></a></h4>
                                    <span class="mm-date"><?php mm_the_date(); ?></span>
                                </header>
                                <section class="post_content">
                                    <?php mm_the_excerpt(); ?>
                                </section>
                            </div>
                            <div class="large-3 small-4 columns">
                                <a href="<?php mm_the_permalink(); ?>" title="<?php esc_attr(mm_the_title()); ?>"><?php mm_the_thumbnail(MM_CPT_MESSAGE, array('align' => 'right')) ?></a>
                            </div>
                        </div>
                    </article> <!-- end article -->
                <?php endwhile; ?>

                <?php foundation_pagination(); ?>

            <?php else : ?>
                <?php get_template_part('content', 'none'); ?>
            <?php endif; ?>
        </div>
        <!-- end #primary -->
        <div class="large-4 columns sidebar mm-sidebar" role="complementary">
            <?php mm_the_back_button(); ?>
            <div class="panel">
                <?php mm_the_speaker_list(); ?>

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
            </div>
        </div>
    </div>
<?php get_footer(); ?>