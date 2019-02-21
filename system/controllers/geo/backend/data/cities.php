<?php

function cms_data_cities($controller){

    return array(
        'item_name' => 'city',
        'title_field' => 'name',
        'grid' => [
            'help_url' => LANG_HELP_URL_GEO,
        ],
        'parents' => array('regions','countries'),
        'methods' => [
            'item' => 'getCity'
        ]
    );

}
