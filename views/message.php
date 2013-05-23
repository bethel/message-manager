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

    <div id="content" class="clearfix message_manager_message">

        <div id="main" class="large-12 columns clearfix" role="main">

            <?php if (!empty($message)): ?>

            <p></p>

            <div class="row">
                <div class="eight columns">

                    <?php if (Message_Manager::has_video($message)): ?>
                        <?php Message_Manager::the_video($message); ?>
                        <hr>
                    <?php endif; ?>

                    <?php Message_Manager::the_image($message, array('align' => 'right')); ?>
                    <h1 class="message-manager-title"><?php Message_Manager::the_title($message); ?></h1>
                    <span class="message-manager-meta"><?php Message_Manager::the_speakers($message); ?>
                        / <?php Message_Manager::the_date($message); ?>
                        / <?php Message_Manager::the_verse($message); ?></span>

                    <?php Message_Manager::the_audio($message); ?>

                    <p></p>

                    <?php Message_Manager::the_content($message); ?>
                </div>
                <div class="four columns">

                    <?php Message_Manager::the_recent_series_list($series, $message); ?>
                    <hr>

                    <h4>Downloads</h4>
                    <?php Message_Manager::the_downloads($message); ?>
                    <hr>

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

                    <p></p>

                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
    <!-- end #content -->
<?php get_footer(); ?>