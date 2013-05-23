<div class="mm_admin_box mm_admin_details">
    <table class="form-table">
        <tbody>
        <?php $mb->the_field('date'); ?>
        <tr>
            <th scope="row"><label for="<?php $mb->the_name(); ?>">Date:</label></th>
            <td><input id="mm-message-date" type="text" name="<?php $mb->the_name(); ?>"
                       value="<?php $mb->the_value(); ?>" class="regular-text"/>

                <p class="description">Enter/Select the date the message was given on.</p>
            </td>
        </tr>

        <?php $mb->the_field('verses'); ?>
        <tr>
            <th scope="row"><label for="<?php $mb->the_name(); ?>">Bible Verses:</label></th>
            <td><input id="mm-message-verses" type="text" name="<?php $mb->the_name(); ?>"
                       value="<?php $mb->the_value(); ?>" class="regular-text"/>

                <p class="description">(Optional) Enter one or more verses sperated by semi-colons that relate to the
                    message. To see sepecific formatting details, look at the <a href="http://reftagger.com/#tagging"
                                                                                 title="Reftagger Website"
                                                                                 target="_blank">Reftagger website</a>.
                </p>
            </td>
        </tr>
        <?php $mb->the_field('summary'); ?>
        <tr>
            <th scope="row"><label for="<?php $mb->the_name(); ?>">Summary:</label></th>
            <td>
                <textarea name="<?php $mb->the_name(); ?>" rows="3"
                          class="large-text"><?php $mb->the_value(); ?></textarea>

                <p class="description">(Optional) Type a brief plain-text description about this message for podcasts,
                    search summaries, and intro pages. If a summary is not entered, it will be derived (not always well)
                    from the content below.</p>
            </td>
        </tr>
        </tbody>
    </table>
</div>

<script type="text/javascript">
    //<![CDATA[
    jQuery(document).ready(function ($) {
        $("#mm-message-date").datepicker({
            showOn: "both",
            dateFormat: "yy-mm-dd",
            buttonImage: "<?php echo MM_IMG_URL; ?>calendar.gif"
        });
    });
    //]]>
</script>