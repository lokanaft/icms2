<?php

class actionGeoGetRegionsAjax extends cmsAction {

    public function run(){

        if (!$this->request->isAjax()){ return cmsCore::error404(); }

        $country_id = $this->request->get('value', 0);
        if (!$country_id) { return cmsCore::error404(); }

        $items = $this->model->getRegions($country_id)?:[];

        $list = [];
        foreach($items as $key => $item){
            $list[] = ['title'=>$item, 'value'=>$key];
        }

        return $this->cms_template->renderJSON($list);

    }

}
