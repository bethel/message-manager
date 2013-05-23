<?php

 class MM_Uploader
{

	public $button_class_name = 'mediabutton';
	public $field_class_name = 'mediafield';
    public $uploader_title = 'Upload File';
	public $insert_button_label = null;
    public $multiple = false;
	private $groupname = null;

	public function __construct(array $a = array())
	{
		foreach ($a as $n => $v)
		{
			$this->$n = $v;
		}

        add_action('admin_enqueue_scripts', array($this, 'wp_enqueue_media'));
		add_action('admin_footer', array($this, 'footer_scripts'));
	}

    public function wp_enqueue_media() {
        wp_enqueue_media();
    }


	/**
	 * Used to generate short unique/random names
	 *
	 * @since	0.1
	 * @access	public
	 * @return	string
	 */
	private function getName()
	{
		return substr(md5(microtime() . rand()), rand(0,25), 6);
	}

	/**
	 * Used to set the insert button label in the media upload box, this can be
	 * set once or per field and button pair.
	 *
	 * @since	0.1
	 * @access	public
	 * @param	string $label button label/title
	 * @return	object $this
	 * @see		setGroupName()
	 */
	public function setInsertButtonLabel($label = 'Insert')
	{
		$this->insert_button_label = $label;

		return $this;
	}

	public function setTab($name)
	{
		$this->tab = $name;

		$this;
	}

	/**
	 * Used before calls to getField(), getButton() or getButtonClass() to set
	 * the groupname to pair a field and button element.
	 *
	 * @since	0.1
	 * @access	public
	 * @param	string $name unique name per pair of field and button
	 * @return	object $this
	 * @see		setInsertButtonLabel()
	 */
	public function setGroupName($name)
	{
		$this->groupname = $name;

		return $this;
	}

	/**
	 * Used to insert a form field of type "text", this should be paired with a
	 * button element. The name and value attributes are required.
	 *
	 * @since	0.1
	 * @access	public
	 * @param	array $attr INPUT tag parameters
	 * @return	HTML
	 * @see		getButton()
	 */
	public function getField(array $attr)
	{
		$groupname = isset($attr['groupname']) ? $attr['groupname'] : $this->groupname ;
		
		$attr_default = array
		(
			'type' => 'text',
			'class' => $this->field_class_name . '-' . $groupname,
		);

		###

		if (isset($attr['class']))
		{
			$attr['class'] = $attr_default['class'] . ' ' . trim($attr['class']);
		}

		$attr = array_merge($attr_default, $attr);

		###

		$elem_attr = array();

		foreach ($attr as $n => $v)
		{
			array_push($elem_attr, $n . '="' . $v . '"');
		}

		###

		return '<input ' . implode(' ', $elem_attr) . '/>';
	}

	/**
	 * Used to get the link used for the button element. If creating custom
	 * buttons, this method should be used to get the link needed for proper
	 * functionality.
	 *
	 * @since	0.1
	 * @access	public
	 * @param	string $tab name that the media upload box will initially load
	 * @return	string link
	 * @see		getButtonClass(), getButton()
	 */
	public function getButtonLink($tab = null)
	{
		// this is set even for new posts/pages
		global $post_ID; //wp

		$tab = ! empty($tab) ? $tab : $this->tab ;

		$tab = ! empty($tab) ? $tab : 'library' ;
		
		return 'media-upload.php?post_id=' . $post_ID . '&tab=' . $tab . '&TB_iframe=1';
	}

	/**
	 * Used to get the CSS class name(s) used for the button element. If
	 * creating custom buttons, this method should be used to get the css class
	 * names needed for proper functionality.
	 *
	 * @since	0.1
	 * @access	public
	 * @param	string $groupname name used when pairing a text field and button
	 * @return	string css class(es)
	 * @see		getButtonLink(), getButton()
	 */
	public function getButtonClass($groupname = null)
	{
		$groupname = isset($groupname) ? $groupname : $this->groupname ;
		
		return $this->button_class_name . '-' . $groupname . ' thickbox';
	}

	/**
	 * Used to get the CSS class name used for the field element. If
	 * creating a custom field, this method should be used to get the css class
	 * name needed for proper functionality.
	 *
	 * @since	0.2
	 * @access	public
	 * @param	string $groupname name used when pairing a text field and button
	 * @return	string css class(es)
	 * @see		getButtonClass(), getField()
	 */
	public function getFieldClass($groupname = null)
	{
		$groupname = isset($groupname) ? $groupname : $this->groupname ;

		return $this->field_class_name . '-' . $groupname;
	}

	/**
	 * Used to insert a WordPress styled button, should be paired with a text
	 * field element.
	 *
	 * @since	0.1
	 * @access	public
	 * @return	HTML
	 * @see		getField(), getButtonClass(), getButtonLink()
	 */
	public function getButton(array $attr = array())
	{
		$groupname = isset($attr['groupname']) ? $attr['groupname'] : $this->groupname ;

		$tab = isset($attr['tab']) ? $attr['tab'] : $this->tab ;
		
		$attr_default = array
		(
			'label' => 'Add Media',
			'href' => $this->getButtonLink($tab),
			'class' => $this->getButtonClass($groupname) . ' button',
		);

		if (isset($this->insert_button_label))
		{
			$attr_default['class'] .= " {label:'" . $this->insert_button_label . "'}";
		}

		###

		if (isset($attr['class']))
		{
			$attr['class'] = $attr_default['class'] . ' ' . trim($attr['class']);
		}

		$attr = array_merge($attr_default, $attr);

		$label = $attr['label'];

		unset($attr['label']);

		###

		$elem_attr = array();

		foreach ($attr as $n => $v)
		{
			array_push($elem_attr, $n . '="' . $v . '"');
		}

		###

		return '<input type="button" ' . implode(' ', $elem_attr) . ' value="' .$label. '" />';
	}

	public function footer_scripts()
	{
		$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : NULL ;

		$file = basename(parse_url($uri, PHP_URL_PATH));

		if ($uri AND in_array($file, array('post.php', 'post-new.php')))
		{
			// include javascript for special functionality
			?><script type="text/javascript">
			/* <![CDATA[ */

				jQuery(function($)
				{
                    var custom_uploader;
                    var formlabel = 0;

                    $('[class*=<?php echo $this->button_class_name; ?>]').live('click', function(e)
                    {
                        e.preventDefault();

                        // If the frame already exists, re-open it.
                        if (custom_uploader) {
                            custom_uploader.open();
                        return;
                        }

                        // Get our Parent element
                        formlabel = jQuery(this).parent();

                        custom_uploader = wp.media.frames.file_frame = wp.media({
                            title: '<?php echo $this->uploader_title ?>,
                            button: {
                                text: <?php echo $this->insert_button_label ?>
                            },
                            multiple: <?php echo $this->multiple ?>
                        });

                        custom_uploader.on('select', function() {
                            attachment = custom_uploader.state().get('selection').first().toJSON();
                            formlabel.find('input[type="text"]').val(attachment.url);
                        });

                        //Open the uploader dialog
                        custom_uploader.open();
                    })
				});
			/* ]]> */
			</script><?php
		}
	}
}

/* End of file */
