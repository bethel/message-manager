<?php echo $before_widget; ?>
<a href="<?php Message_Manager::the_link($item); ?>" title="<?php Message_Manager::the_title($item); ?>">
<?php Message_Manager::the_image($item, array('size'=>'bethel-home-box')); ?>
<?php if (!empty($title)) echo $before_title . $title . $after_title; ?>
</a>
<?php echo $after_widget; ?>