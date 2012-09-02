<?php global $wpalchemy_media_access; ?>
<div class="mm_admin_box mm_admin_attachments">

	<p>The attachments section allows you upload or reference files that support the message. e.g. transcripts, outlines, notes, slides, etc.
	<?php while($mb->have_fields_and_multi('attachment')): ?>
	<?php $mb->the_group_open(); ?>
	<table class="form-table">
	<tbody>
		<?php $mb->the_field('url'); ?>
		<?php $wpalchemy_media_access->setGroupName('url-n'. $mb->get_the_index())->setInsertButtonLabel('Use File'); ?>			
		<tr class="mm-attachment-url">
			<th scope="row"><label>URL:</label></th>
			<td>
			<a href="#" class="dodelete button" style="float:right; clear: both;">Remove Attachment</a>
			<?php echo $wpalchemy_media_access->getField(array('name' => $mb->get_the_name(), 'value' => $mb->get_the_value())); ?>
			<?php echo $wpalchemy_media_access->getButton(array('label' => 'Upload/Select Attachment')); ?>
			<p class="description">Enter the URL to an attachment, upload a file from your computer, or select a file from the media library. e.g. http://www.example.com/homework.pdf</p>
			</td>
		</tr>
		<?php $mb->the_field('title'); ?>
		<tr class="mm-attachment-title">
			<th scope="row"><label for="<?php $mb->the_name(); ?>">Title:</label></th>
			<td><input type="text" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>" class="regular-text" />
				<p class="description">(Optional) Enter the title of the attachment. e.g. Growth Group Homework</p>
			</td>
		</tr>
		<?php $mb->the_field('description'); ?>
		<tr class="mm-attachment-description">
			<th scope="row"><label for="<?php $mb->the_name(); ?>">Description:</label></th>
			<td><textarea name="<?php $mb->the_name(); ?>" rows="5" class="large-text"><?php $mb->the_value(); ?></textarea>
			<p class="description">(Optional) Enter a discription of the attachment content.</p>
			</td>
		</tr>
	</tbody>
	</table>
	<?php $mb->the_group_close(); ?>
	<?php endwhile; ?>
	<p>
		<a href="#" class="docopy-attachment button" style="">Add Another Attachment</a>
	</p>
</div>

<script type="text/javascript">
//<![CDATA[
	jQuery(document).ready(function($) {
		$.wpalchemy.bind('wpa_copy', function(the_clone) {
			toggleFirstRemoveButton();
    	});

		$.wpalchemy.bind('wpa_delete', function(the_clone) {
			toggleFirstRemoveButton();
    	});
    	
		function toggleFirstRemoveButton() {
			if ($('.wpa_group-attachment').length > 2) {
				$('.wpa_group-attachment:first .dodelete').fadeIn('fast');
			} else {
				$('.wpa_group-attachment:first .dodelete').fadeOut('fast');
			}
		}
		toggleFirstRemoveButton();
	});
//]]>
</script>