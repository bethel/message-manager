<?php
/**
 * The Message Manager template for a series
 */
?>
<?php get_header(); ?>

    <div class="row">
        <div id="primary" class="large-12 columns content-area" role="main">

            <div class="row">
                <div class="large-4 columns"><?php mm_the_series_image(MM_CPT_MESSAGE . '_large') ?></div>
                <div class="large-7 columns">
                    <h1><?php mm_the_term_title() ?></h1>
                    <?php mm_the_term_description() ?>
                </div>
                <div class="large-1 columns">
                    <!-- AddThis Button BEGIN -->
                    <div class="addthis_toolbox addthis_counter_style">
                        <a class="addthis_button_facebook_like" fb:like:layout="box_count"></a>
                        <a class="addthis_button_google_plusone" g:plusone:size="tall"></a>
                        <a class="addthis_counter"></a>
                    </div>
                    <script type="text/javascript">
                        var addthis_config = { ui_click: true };
                    </script>
                    <script type="text/javascript"
                            src="https://s7.addthis.com/js/250/addthis_widget.js#pubid=xa-50482fcb481a8273"></script>
                    <!-- AddThis Button END -->
                </div>
            </div>

            <hr>

            <h3>Series Messages</h3>

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
    </div> <!-- end row -->

<?php get_footer(); ?>