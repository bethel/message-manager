<?php
/**
 * The Message Manager template for a series
 */

$items = Message_Manager::get_items_from_posts();
$series = array_pop($items);
$messages = $series['messages'];
?>
<?php get_header(); ?>

    <div id="content" class="clearfix message_manager_series">

        <div id="main" class="large-12 columns clearfix" role="main">

            <div class="row clearfix">&nbsp;</div>

            <div class="row clearfix">
                <div
                    class="three columns"><?php Message_Manager::the_image($series, array('size' => Message_Manager::$tax_series)); ?></div>
                <div class="eight columns"><h1><?php Message_Manager::the_title($series); ?></h1>
                    <?php Message_Manager::the_content($series); ?>
                </div>
                <div class="one columns end">
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

            <div class="message_manager_messages">

                <?php
                $i = 1;
                while (!empty($messages)) :
                    $message = array_shift($messages);
                    $end = empty($messages);
                    ?>

                    <div class="three columns<?php echo ($end) ? ' end' : ''; ?> message_manager_message_box">

                        <a href="<?php Message_Manager::the_link($message); ?>">
                            <?php Message_Manager::the_image($message, array('size' => Message_Manager::$cpt_message)); ?>
                            <h4><?php Message_Manager::the_title($message); ?></h4>
                            <span><?php Message_Manager::the_date($message); ?></span>
                        </a>

                    </div>

                    <?php if (!($i % 4) || $end): ?>
                    <div class="clearfix"></div>
                <?php endif; ?>

                    <?php $i++; endwhile; ?>
            </div>

            <div class="clearfix"></div>

        </div>
        <!-- end #main -->

    </div> <!-- end #content -->

<?php get_footer(); ?>