<?php

require_once MM_VENDOR_PATH . 'tax-meta-class/Tax-meta-class.php';

$series_meta = new MM_Tax_Meta_Class(array(
    'id' => 'mm_series_meta',
    'title' => 'Series Information',
    'pages' => array(MM_TAX_SERIES),
    'context' => 'normal',
    'fields' => array(),
    'local_images' => false,
    'use_with_theme' => MM_VENDOR_URL . 'tax-meta-class'
));

$series_meta->addImage(MM_META_PREFIX . 'series_image', array('name' => 'Series Image (16:9)'));

$series_meta->Finish();