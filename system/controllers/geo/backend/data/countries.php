<?php

function cms_data_countries($controller){

    return array(
        'item_name' => 'country',
        'gender' => 'F',
        'title_field' => 'name',
        'grid' => array(
            'help_url' => LANG_HELP_URL_GEO,
        ),
        'childs' => array('regions', 'cities')
    );

}
