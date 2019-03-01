<?php

function cms_data_geo_regions($controller){

    return array(
        'item_name' => 'region',
        'title_field' => 'name',
        'grid' => array(
            'help_url' => LANG_HELP_URL_GEO,
        ),
        'childs' => array('cities'),
        'parents' => array('countries')
    );

}
