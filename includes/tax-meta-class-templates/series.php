<?php

$series_meta =  new MM_Tax_Meta_Class(array(
	'id' => 'mm_series_meta',
	'title' => 'Series Information',
	'pages' => array(Message_Manager::$tax_series),
	'context' => 'normal',
	'fields' => array(),
	'local_images' => false,
	'use_with_theme' => false
));

$series_meta->addImage(Message_Manager::$meta_prefix.'series_image',array('name'=> 'Series Image'));

$series_meta->Finish();