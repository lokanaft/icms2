<?php

class cmsBackend extends cmsController {

    private $h1 = '';

    public $maintained_ctype = false;

    protected $backend_menu = array();

    public $queue = array(
        'queues'           => array(),
        'queue_name'       => '',
        'use_queue_action' => false
    );

    protected $useDefaultModerationAction = false;
    protected $useModerationTrash = false;

    public function __construct( cmsRequest $request){

        $this->name = str_replace('backend', '', strtolower(get_called_class()));

        parent::__construct($request);

        $this->root_path = $this->root_path . 'backend/';

        // Устанавливаем корень для URL внутри бэкенда
        $admin_controller_url = 'admin';
        $controller_alias = cmsCore::getControllerAliasByName($admin_controller_url);
        if ($controller_alias) { $admin_controller_url = $controller_alias; }
        $this->setRootURL($admin_controller_url.'/controllers/edit/'.$this->name);

        if(!empty($this->queue['use_queue_action'])){
            $this->backend_menu[] = array(
                'title' => sprintf(LANG_CP_QUEUE_TITLE, $this->queue['queue_name']),
                'url'   => href_to($this->root_url, 'queue')
            );
        }

        if(!empty($this->useDefaultModerationAction)){
            $this->backend_menu[] = array(
                'title' => LANG_MODERATORS,
                'url'   => href_to($this->root_url, 'moderators')
            );
        }

    }

    public function setH1($title, $change = true) {

        if (is_array($title)){ $title = implode(' -> ', $title); }
        if($change){
            $this->h1 = ' -> '.$title;
        }elseif($title){
            $this->h1 .= ' -> '.$title;
        }

    }

    public function getH1() {
        return $this->h1;
    }

//============================================================================//
//============================================================================//

    public function getBackendMenu(){
        return $this->backend_menu;
    }

    public function getOptionsToolbar(){
        return array();
    }

    public function route($uri){

        $uri = explode('/', $uri);
        $uri_action = str_replace(['.', '\\'], '', array_shift($uri));

        $action_name = '';

        if(strpos($uri_action, '_') !== false){
            $parts = explode('_', $uri_action);
            $data_name = '';
            while($parts){
                $data_name .= ($data_name ? '_' : '').array_shift($parts);
                if(is_readable($this->root_path.'data/'.$data_name.'.php')){
                    break;
                }
            }
            $action_name = implode('_', $parts);
        }else{
            $data_name = $uri_action;
        }
        if(!$action_name){
            $action_name = 'grid';
        }

        switch($action_name){
            case 'grid':
                return $this->actionGrid($data_name);
            case 'childs_grid':
                return $this->actionChildsGrid($data_name, isset($uri[0]) ? $uri[0] : 0);
            case 'add':
                return $this->actionAdd($data_name, isset($uri[0]) ? $uri[0] : 0, isset($uri[1]) ? $uri[1] : 0, isset($uri[2]) ? $uri[2] : 0, isset($uri[3]) ? $uri[3] : 0);
            case 'edit':
                return $this->actionEdit($data_name, isset($uri[0]) ? $uri[0] : 0); // $uri[0] проверится внутри actionEdit
            default: return cmsCore::error404(); // если нет подходящего data, то сработает именно это
        }

    }

    protected function getData($data_name){
        if(!is_readable($this->root_path.'data/'.$data_name.'.php')){
            return false;
        }
        include_once $this->root_path.'data/'.$data_name.'.php';
        $function_name = 'cms_data_'.$data_name;
        if( !function_exists($function_name)
                ||
            !($data = call_user_func_array($function_name, array($this)))
        ){
            return false;
        }
        if(!isset($data['items_name'])){
            $data['items_name'] = $data_name;
        }
        if(!isset($data['item_title'])){
            $name = 'LANG_'.strtoupper($this->name).'_'.strtoupper($data['item_name']);
            $data['item_title'] = defined($name) ? constant($name) : '';
        }
        if(!isset($data['items_title'])){
            $name = 'LANG_'.strtoupper($this->name).'_'.strtoupper($data['items_name']);
            $data['items_title'] = defined($name) ? constant($name) : '';
        }
        if(!isset($data['table_name'])){
            $data['table_name'] = $this->name.'_'.$data_name;
        }
        if(!isset($data['labels']['create'])){
            $name = 'LANG_'.strtoupper($this->name).'_'.strtoupper($data['item_name']).'_LABELS_CREATE';
            $data['labels']['create'] = defined($name) ? constant($name) : '';
        }
        if(!isset($data['title_field'])){
            $data['title_field'] = 'title';
        }
        if(!isset($data['gender'])){
            $data['gender'] = 'M';
        }
        if(empty($data['parents'])){
            $data['parents'] = [];
        }
        if(empty($data['childs'])){
            $data['childs'] = [];
        }

        return $data;
    }

    protected function prepareDataList($parent, $list){ // он нужен чтобы в getData() не делать этого лишний раз
        $new = array();
        foreach($parent[$list]?:[] as $item){
            if(!is_array($item)){ // значит здесь просто название набора
                $item_data = $this->getData($item);
                if(!$item_data){
                    $item = array(
                        'items_name'=> $item,
                        'parent_id_field'  => $item.'_id', // это поле в этой ($parent) таблице с ид родителя $item
                        'table_name'=> $this->name.'_'.$item,
                        'title_field'=> '',
                        'child_id_field' => $parent['item_name'].'_id', // это поле в таблицах потомков с ид $parent
                        'grid_name' => $item
                    );
                }else{
                    $item = array(
//                        'items_name'=> $item_data['items_name'],
                        'parent_id_field'  => $item_data['item_name'].'_id',
//                        'table_name'=> $item_data['table_name'],
//                        'title_field'=> $item_data['title_field'],
                        'child_id_field'=> $parent['item_name'].'_id',
                        'grid_name' => $item_data['items_name'],
//                        'data' => $item_data
                    );
                    $item = array_merge($item_data, $item);
                }
            }else{
                $item_data = $this->getData($item['items_name']);
                if($item_data){
                    $item = array_merge($item_data, $item);
                }
            }
            $new[$item['items_name']] = $item;
        }
        return $new;
    }

    protected function getDataParents($data, $parent_item = []){ // возвращает цепочку родителей
        $ret = array();

        while(!empty($data['parents'])){
            $parents = $this->prepareDataList($data, 'parents');
            $parent = current($parents);
            if($parent_item){
                $parent_id = !empty($parent_item[$parent['parent_id_field']]) ? $parent_item[$parent['parent_id_field']] : 0;
                if($parent_id){
                    $parent_item = $this->model->getItemById($parent['table_name'], $parent_id);
                    if($parent_item){
                        $parent['item'] = $parent_item;
                    }
                }
            }
            $ret[] = $parent;
            $data = $parent;
        }

        return $ret;
    }

    protected function getDataCrumbs($data, $item = []){
        $back_url = '';
        $crumbs = $titles = $parents = array();

        if(!empty($item[$data['title_field']])){
            $crumbs[] = ['title' => $item[$data['title_field']], 'link' => ''];
            $titles[] = $item[$data['title_field']];
        }

        if(!empty($data['parents'])){
            $parents = $this->getDataParents($data, $item);
            foreach($parents as $parent){
                if(!empty($parent['item'])){
                    $url = $this->cms_template->href_to($parent['items_name'].'_childs_grid/'.$parent['item']['id']);
                    $crumbs[] = ['title' => $parent['item'][$parent['title_field']], 'link' => $url];
                    $titles[] = $parent['item'][$parent['title_field']];
                    if(!$back_url){
                        $back_url = $parent['items_name'].'_childs_grid/'.$parent['item']['id'];
                    }
                }else{
                    $url = $this->cms_template->href_to($parent['items_name'].'_childs_grid');
                    $crumbs[] = ['title' => $parent['items_title'], 'link' => $url];
                    $titles[] = $parent['items_title'];
                    if(!$back_url){
                        $back_url = $parent['items_name'].'_childs_grid';
                    }
                }
            }
            $crumbs = array_reverse($crumbs);
        }else
        if(!$item){ // для обычных гридов
            $crumbs[] = ['title' => $data['items_title'], 'link' => $this->cms_template->href_to($data['items_name'])];
            $titles[] = $data['items_title'];
        }

        return array($crumbs, $titles, $back_url, $parents);
    }

    protected function setH1Data($titles, $cut_last = false){
        if($cut_last){
            array_shift($titles);
        }
        if($titles){
            $titles = array_reverse($titles);
            $this->setH1($titles, false);
        }
    }

    protected function getDataItem($data, $item_id){
        return !empty($data['methods']['item']) ?
            $this->model->{$data['methods']['item']}($item_id) :
            $this->model->getItemById($data['table_name'], $item_id);
    }

    protected function addDataItem($data, $item){
        return !empty($data['methods']['add']) ?
            $this->model->{$data['methods']['add']}($item) :
            $this->model->insert($data['table_name'], $item);
    }

    protected function updateDataItem($data, $item_id, $item){
        return !empty($data['methods']['update']) ?
            $this->model->{$data['methods']['update']}($item_id, $item) :
            $this->model->update($data['table_name'], $item_id, $item);
    }

    protected function getDataItems($data){
        return !empty($data['methods']['items']) ?
            $this->model->{$data['methods']['items']}() :
            $this->model->get($data['table_name']);
    }

//============================================================================//
//============================================================================//
//=========              ШАБЛОНЫ ОСНОВНЫХ ДЕЙСТВИЙ                   =========//
//============================================================================//
//============================================================================//

    public function actionGrid($data_name){
        if( !($data = $this->getData($data_name))
                ||
            !($grid = $this->loadDataGrid($data_name, false, 'admin.grid_'.$this->name.'.'.$data_name))
        ){
            return cmsCore::error404();
        }

        if($this->request->isAjax()){
            if(!empty($grid['options']['is_pagination'])){$this->model->setPerPage(admin::perpage);}
            if(!empty($grid['options']['is_sortable']) || !empty($grid['options']['is_filter']) || !empty($grid['options']['is_pagination'])){
                $filter_str = cmsUser::getUPSActual('admin.grid_'.$this->name.'.'.$data_name, $this->request->get('filter', ''));
//                var_dump('admin.grid_'.$this->name.'.'.$data['items_name'], $filter_str);die;
                if($filter_str){
                    $filter = array();
                    parse_str($filter_str, $filter);
                    if(empty($grid['options']['is_pagination'])){unset($filter['page']);}
                    $this->model->applyGridFilter($grid, $filter);
                }
            }
            $total = $this->model->getCount($data['table_name']);
            if(!empty($grid['options']['is_pagination'])){
                $perpage = isset($filter['perpage']) ? $filter['perpage'] : admin::perpage;
                $pages = ceil($total / $perpage);
            }else{
                $pages = 1;
            }
            $items = $this->getDataItems($data);

            cmsTemplate::getInstance()->renderGridRowsJSON($grid, $items, $total, $pages);
            return $this->halt();
        }

        list($crumbs, $titles) = $this->getDataCrumbs($data);

//        if(empty($data['form']['no_h1']) && $titles){
//            $this->setH1Data($titles);
//        }

        $to_template = array(
            'grid' => $grid,
            'data' => $data,
            'crumbs' => $crumbs,
            'titles' => $titles
                );

        return $this->cms_template->getTemplateFileName('controllers/'.$this->name.'/backend/'.$data_name, true) ?
                $this->cms_template->render('backend/'.$data_name, $to_template) :
                $this->cms_template->renderAsset('ui/data_grid', $to_template);
    }

    public function actionChildsGrid($data_name, $item_id, $child_name = ''){
        if( !($item_id = intval($item_id))
                ||
            !($data = $this->getData($data_name))
                ||
            empty($data['childs'])
                ||
            !($item = $this->getDataItem($data, $item_id))
        ){
            return cmsCore::error404();
        }

        $data['childs'] = $this->prepareDataList($data, 'childs');

        if(!$child_name){ // если не указан явно, то первый по списку
            $current = current($data['childs']);
            if(empty($current['items_name'])){return cmsCore::error404();}
            $child_name = $current['items_name'];
        }
        if(
            empty($data['childs'][$child_name])
                ||
            !($grid = $this->loadDataGrid($data['childs'][$child_name]['grid_name'], false, 'admin.grid_'.$this->name.'.'.$data['childs'][$child_name]['grid_name']))
        ){
            return cmsCore::error404();
        }
        $child = $data['childs'][$child_name];
//        $child_data = !empty($child['data']) ? $child['data'] : array();

        if($this->request->isAjax()){
            if(!empty($grid['options']['is_pagination'])){$this->model->setPerPage(admin::perpage);}
            if(!empty($grid['options']['is_sortable']) || !empty($grid['options']['is_filter']) || !empty($grid['options']['is_pagination'])){
                $filter_str = cmsUser::getUPSActual('admin.grid_'.$this->name.'.'.$data_name, $this->request->get('filter', ''));
//                var_dump('admin.grid_'.$this->name.'.'.$data['items_name'], $filter_str);die;
                if($filter_str){
                    $filter = array();
                    parse_str($filter_str, $filter);
                    if(empty($grid['options']['is_pagination'])){unset($filter['page']);}
                    $this->model->applyGridFilter($grid, $filter);
                }
            }
            $this->model->filterEqual($child['child_id_field'], $item_id);
//            var_dump($child['parent_id_field'], $item_id);die;
            $total = $this->model->getCount($child['table_name']);
            if(!empty($grid['options']['is_pagination'])){
                $perpage = isset($filter['perpage']) ? $filter['perpage'] : admin::perpage;
                $pages = ceil($total / $perpage);
            }else{
                $pages = 1;
            }
            $items = $this->getDataItems($child);

            cmsTemplate::getInstance()->renderGridRowsJSON($grid, $items, $total, $pages);
            return $this->halt();
        }

        list($crumbs, $titles) = $this->getDataCrumbs($data, $item);

        if(empty($data['form']['no_h1']) && $titles){
            $this->setH1Data($titles);
        }

        $to_template = array(
            'grid' => $grid,
            'data' => $child,
            'parent_data' => $data,
            'crumbs' => $crumbs,
            'parent' => $data,
            'item_id' => $item_id,
            'titles' => $titles
        );
        return $this->cms_template->getTemplateFileName('controllers/'.$this->name.'/backend/'.$data_name, true) ?
                $this->cms_template->render('backend/'.$data_name, $to_template) :
                $this->cms_template->renderAsset('ui/data_grid', $to_template);
    }

    public function actionAdd($data_name, $item_id = 0, $extra0 = null, $extra1 = null, $extra2 = null){
        if( !($data = $this->getData($data_name))
                ||
            !($form = $this->getForm($data['item_name'], array('add')))
        ){
            return cmsCore::error404();
        }

        $extra = [$extra0, $extra1, $extra2];
//        var_dump($extra);die;

        if($this->request->has('submit')){
            $item = $form->parse($this->request, true);
            $errors = $form->validate($this, $item);
            if(!$errors){
                $item_id = $this->addDataItem($data, $item);
                if($item_id){
                    cmsUser::addSessionMessage(sprintf(LANG_CP_COMPLETE_SUCCESS, $data['item_title'], $item[$data['title_field']], constant('LANG_CP_SUCCESS_ADDED_'.$data['gender'])), 'success');
                    // для редиректа
                    $item = $this->getDataItem($data, $item_id);
                    list($crumbs, $titles, $back_url) = $this->getDataCrumbs($data, $item);
                }

                return $this->redirectToAction(!empty($back_url) ? $back_url : $data_name);
            }else{
                cmsUser::addSessionMessage(LANG_FORM_ERRORS, 'error');
            }
        }else{
            $errors = array();
            $item_id = (int)$item_id;
            $item = $item_id ? $this->getDataItem($data, $item_id) : array();
            if(!$item){
                if($data['parents']){
                    $parents = $this->prepareDataList($data, 'parents');
                    $i = 0;
                    foreach($parents as $parent){
                        $item[$parent['parent_id_field']] = $extra[$i++];
                        if($i === 3){break;}
                    }
                }
                $item_id = 0;
            }
        }
        list($crumbs, $titles, $back_url, $parents) = $this->getDataCrumbs($data, $item);

        if(!$item_id){
            foreach($parents as $parent){
                if(!empty($parent['item']['id']) && isset($item[$parent['parent_id_field']])){
                    $item[$parent['parent_id_field']] = $parent['item']['id'];
                }
            }
        }

        if(empty($data['form']['no_h1']) && $titles){
            $this->setH1Data($titles, (bool)$item_id);
        }

        $to_template = array(
            'do' => 'add',
            'item' => $item,
            'form' => $form,
            'errors' => $errors,
            'data' => $data,
            'crumbs' => $crumbs,
            'titles' => $titles,
            'item_id' => $item_id,
            'back_url' => $back_url
        );
        return $this->cms_template->getTemplateFileName('controllers/'.$this->name.'/backend/'.$data['item_name'], true) ?
                $this->cms_template->render('backend/'.$data['item_name'], $to_template) :
                $this->cms_template->renderAsset('ui/data_form', $to_template);

    }

    public function actionEdit($data_name, $item_id){
        if( !($item_id = intval($item_id))
                ||
            !($data = $this->getData($data_name))
                ||
            !($form = $this->getForm($data['item_name'], array('edit')))
        ){
            return cmsCore::error404();
        }

        if($this->request->has('submit')){
            $item = $form->parse($this->request, true);
            $errors = $form->validate($this, $item);
            if(!$errors){
                if($this->updateDataItem($data, $item_id, $item)){
                    cmsUser::addSessionMessage(sprintf(LANG_CP_COMPLETE_SUCCESS, $data['item_title'], $item[$data['title_field']], constant('LANG_CP_SUCCESS_UPDATED_'.$data['gender'])), 'success');
                }
                // для редиректа
                $item = $this->getDataItem($data, $item_id);
                list($crumbs, $titles, $back_url) = $this->getDataCrumbs($data, $item);

                return $this->redirectToAction($back_url);
            }else{
                cmsUser::addSessionMessage(LANG_FORM_ERRORS, 'error');
            }
        }else{
            $errors = array();
            $item = $this->getDataItem($data, $item_id);
        }

        list($crumbs, $titles, $back_url) = $this->getDataCrumbs($data, $item);

        if(empty($data['form']['no_h1']) && $titles){
            $this->setH1Data($titles, true);
        }

        $to_template = array(
            'do' => 'edit',
            'item' => $item,
            'form' => $form,
            'errors' => $errors,
            'data' => $data,
            'crumbs' => $crumbs,
            'titles' => $titles,
            'back_url' => $back_url
        );
        return $this->cms_template->getTemplateFileName('controllers/'.$this->name.'/backend/'.$data['item_name'], true) ?
                $this->cms_template->render('backend/'.$data['item_name'], $to_template) :
                $this->cms_template->renderAsset('ui/data_form', $to_template);

    }
//============================================================================//
//=========                Скрытие/показ записей                     =========//
//============================================================================//

    public function actionToggleItem($item_id=false, $table=false, $field='is_pub'){

		if (!$item_id || !$table || !is_numeric($item_id) || $this->validate_regexp("/^([a-z0-9\_{}]*)$/", urldecode($table)) !== true){
			$this->cms_template->renderJSON(array(
				'error' => true,
			));
		}

        $i = $this->model->getItemByField($table, 'id', $item_id);

		if (!$i || !array_key_exists($field, $i)){
			$this->cms_template->renderJSON(array(
				'error' => true,
			));
		}

        $i[$field] = $i[$field] ? 0 : 1;

		$this->model->update($table, $item_id, array(
			$field => $i[$field]
		));

        $this->processCallback('actiontoggle_'.$table.'_'.$field, array($i));

		$this->cms_template->renderJSON(array(
			'error' => false,
			'is_on' => $i[$field]
		));

    }

//============================================================================//
//=========                  ОПЦИИ КОМПОНЕНТА                        =========//
//============================================================================//

    public function addControllerSeoOptions($form) {

        if($this->useSeoOptions){
            $form->addFieldset(LANG_ROOT_SEO, 'seo_basic', array(
                'childs' => array(
                    new fieldString('seo_keys', array(
                        'title' => LANG_SEO_KEYS,
                        'hint' => LANG_SEO_KEYS_HINT,
                        'options'=>array(
                            'max_length'=> 256,
                            'show_symbol_count'=>true
                        )
                    )),
                    new fieldText('seo_desc', array(
                        'title' => LANG_SEO_DESC,
                        'hint' => LANG_SEO_DESC_HINT,
                        'options'=>array(
                            'max_length'=> 256,
                            'show_symbol_count'=>true
                        )
                    ))
                )
            ));
        }

        if($this->useItemSeoOptions){
            $form->addFieldset(LANG_CP_SEOMETA, 'seo_items', array(
                'childs' => array(
                    new fieldString('tag_title', array(
                        'title' => LANG_CP_SEOMETA_ITEM_TITLE,
                        'hint' => LANG_CP_SEOMETA_ITEM_HINT
                    )),
                    new fieldString('tag_desc', array(
                        'title' => LANG_CP_SEOMETA_ITEM_DESC,
                        'hint'  => LANG_CP_SEOMETA_ITEM_HINT
                    )),
                    new fieldString('tag_h1', array(
                        'title' => LANG_CP_SEOMETA_ITEM_H1,
                        'hint'  => LANG_CP_SEOMETA_ITEM_HINT
                    ))
                )
            ));
        }

        return $form;

    }

    public function actionOptions(){

        if (empty($this->useDefaultOptionsAction)){ cmsCore::error404(); }

        $form = $this->getForm('options');
        if (!$form) { cmsCore::error404(); }

        $form = $this->addControllerSeoOptions($form);

        $options = cmsController::loadOptions($this->name);

        if ($this->request->has('submit')){

            $options = array_merge( $options, $form->parse($this->request, true) );
            $errors  = $form->validate($this, $options);

            if (!$errors){

                cmsUser::addSessionMessage(LANG_CP_SAVE_SUCCESS, 'success');

                cmsController::saveOptions($this->name, $options);

                $this->processCallback(__FUNCTION__, array($options));

                $this->redirectToAction('options');

            }

            if ($errors){

                cmsUser::addSessionMessage(LANG_FORM_ERRORS, 'error');

            }

        }

        $template_params = array(
            'toolbar' => $this->getOptionsToolbar(),
            'options' => $options,
            'form'    => $form,
            'errors'  => isset($errors) ? $errors : false
        );

        // если задан шаблон опций в контроллере
        if($this->cms_template->getTemplateFileName('controllers/'.$this->name.'/backend/options', true)){

            return $this->cms_template->render('backend/options', $template_params);

        } else {

            $default_admin_tpl = $this->cms_template->getTemplateFileName('controllers/admin/controllers_options');

            return $this->cms_template->processRender($default_admin_tpl, $template_params);

        }

    }

//============================================================================//
//=========                  УПРАВЛЕНИЕ ДОСТУПОМ                     =========//
//============================================================================//

    public function actionPerms($subject=''){

        if (empty($this->useDefaultPermissionsAction)){ cmsCore::error404(); }

        $rules  = cmsPermissions::getRulesList($this->name);
        $values = cmsPermissions::getPermissions($subject);

        // добавляем правила доступа от типа контента, если контроллер на его основе
		$ctype = cmsCore::getModel('content')->getContentTypeByName($this->name);
        if ($ctype && $subject == $this->name) {
            $rules = array_merge(cmsPermissions::getRulesList('content'), $rules);
        }

        list($rules, $values) = cmsEventsManager::hook("controller_{$this->name}_perms", array($rules, $values));

        $groups = cmsCore::getModel('users')->getGroups(false);

        $template_params = array(
            'rules'   => $rules,
            'values'  => $values,
            'groups'  => $groups,
            'subject' => $subject
        );

        // если задан шаблон опций в контроллере
        if($this->cms_template->getTemplateFileName('controllers/'.$this->name.'/backend/perms', true)){

            return $this->cms_template->render('backend/perms', $template_params);

        } else {

            $default_admin_tpl = $this->cms_template->getTemplateFileName('controllers/admin/controllers_perms');

            return $this->cms_template->processRender($default_admin_tpl, $template_params);

        }

    }

    public function actionPermsSave($subject=''){

        if (empty($this->useDefaultPermissionsAction)){ cmsCore::error404(); }

        $values = $this->request->get('value', array());
        $rules  = cmsPermissions::getRulesList($this->name);

        // добавляем правила доступа от типа контента, если контроллер на его основе
		$ctype = cmsCore::getModel('content')->getContentTypeByName($this->name);
        if ($ctype) {
            $rules = array_merge(cmsPermissions::getRulesList('content'), $rules);
        }

        list($rules, $values) = cmsEventsManager::hook("controller_{$this->name}_perms", array($rules, $values));

        $groups = cmsCore::getModel('users')->getGroups(false);

        // перебираем правила
        foreach($rules as $rule){

            // если для этого правила вообще ничего нет,
            // то присваиваем null
            if (!isset($values[$rule['id']])) {
                $values[$rule['id']] = null; continue;
            }

            // перебираем группы, заменяем на нуллы
            // значения отсутствующих правил
            foreach($groups as $group){
                if (!isset($values[$rule['id']][$group['id']])) {
                    $values[$rule['id']][$group['id']] = null;
                }
            }

        }

        cmsUser::addSessionMessage(LANG_CP_PERMISSIONS_SUCCESS, 'success');

        cmsPermissions::savePermissions($subject, $values);

        $this->redirectBack();

    }

//============================================================================//
//=========                           Очереди                        =========//
//============================================================================//

    public function actionQueue(){

        if (empty($this->queue['use_queue_action'])){ cmsCore::error404(); }

        $grid = $this->controller_admin->loadDataGrid('queue', array('contex_controller' => $this));

        if ($this->request->isAjax()) {

            $filter     = array();
            $filter_str = $this->request->get('filter', '');

            if($filter_str){
                parse_str($filter_str, $filter);
            }

            $this->controller_admin->model->filterIn('queue', $this->queue['queues']);

            $total = $this->controller_admin->model->getCount(cmsQueue::getTableName());

            $perpage = isset($filter['perpage']) ? $filter['perpage'] : admin::perpage;
            $page    = isset($filter['page']) ? intval($filter['page']) : 1;

            $pages = ceil($total / $perpage);

            $this->controller_admin->model->limitPage($page, $perpage);

            $this->controller_admin->model->orderByList(array(
                array('by' => 'date_started', 'to' => 'asc'),
                array('by' => 'priority', 'to' => 'desc'),
                array('by' => 'date_created', 'to' => 'asc')
            ));

            $jobs = $this->controller_admin->model->get(cmsQueue::getTableName());

            $this->cms_template->renderGridRowsJSON($grid, $jobs, $total, $pages);

            $this->halt();

        }

        $template_params = array(
            'grid'       => $grid,
            'page_title' => sprintf(LANG_CP_QUEUE_TITLE, $this->queue['queue_name']),
            'source_url' => href_to($this->root_url, 'queue'),
        );

        return $this->cms_template->processRender($this->cms_template->getTemplateFileName('assets/ui/grid'), $template_params);

    }

    public function actionQueueRestart($job_id){

        if (empty($this->queue['use_queue_action'])){ cmsCore::error404(); }

        cmsQueue::restartJob(array('id' => $job_id));

        $this->redirectBack();

    }

    public function actionQueueDelete($job_id){

        if (empty($this->queue['use_queue_action'])){ cmsCore::error404(); }

        $csrf_token = $this->request->get('csrf_token', '');
        if (!cmsForm::validateCSRFToken( $csrf_token )){
            cmsCore::error404();
        }

        cmsQueue::deleteJob(array('id' => $job_id));

        $this->redirectBack();

    }

    //============================================================================//
    //=========                         Модераторы                       =========//
    //============================================================================//

    public function actionModerators(){

        if (empty($this->useDefaultModerationAction)){ cmsCore::error404(); }

        $moderators = $this->model_moderation->getContentTypeModerators($this->name);

        $template_params = array(
            'title'         => $this->title,
            'not_use_trash' => !$this->useModerationTrash,
            'moderators'    => $moderators
        );

        $this->cms_template->addToolButton(array(
            'class'  => 'settings',
            'title'  => LANG_MODERATORATION_OPTIONS,
            'href'   => href_to('admin', 'controllers', array('edit', 'moderation', 'options'))
        ));

        $this->cms_template->addToolButton(array(
            'class'  => 'help',
            'title'  => LANG_HELP,
            'target' => '_blank',
            'href'   => LANG_HELP_URL_CTYPES_MODERATORS
        ));

        // если задан шаблон в контроллере
        if($this->cms_template->getTemplateFileName('controllers/'.$this->name.'/backend/moderators', true)){

            return $this->cms_template->render('backend/moderators', $template_params);

        } else {

            $default_admin_tpl = $this->cms_template->getTemplateFileName('controllers/admin/controllers_moderators');

            return $this->cms_template->processRender($default_admin_tpl, $template_params);

        }

    }

    public function actionModeratorsAdd(){

        if (!$this->request->isAjax()) { cmsCore::error404(); }

        $name = $this->request->get('name', '');
        if (!$name) { cmsCore::error404(); }

        $user = cmsCore::getModel('users')->filterEqual('email', $name)->getUser();

        if ($user === false){
            return $this->cms_template->renderJSON(array(
                'error'   => true,
                'message' => sprintf(LANG_CP_USER_NOT_FOUND, $name)
            ));
        }

        $moderators = $this->model_moderation->getContentTypeModerators($this->name);

        if (isset($moderators[$user['id']])){
            return $this->cms_template->renderJSON(array(
                'error'   => true,
                'message' => sprintf(LANG_MODERATOR_ALREADY, $user['nickname'])
            ));
        }

        $moderator = $this->model_moderation->addContentTypeModerator($this->name, $user['id']);

        if (!$moderator){
            return $this->cms_template->renderJSON(array(
                'error'   => true,
                'message' => LANG_ERROR
            ));
        }

        $ctypes_moderator_tpl = $this->cms_template->getTemplateFileName('controllers/admin/ctypes_moderator');

        return $this->cms_template->renderJSON(array(
            'error' => false,
            'name'  => $user['nickname'],
            'html'  => $this->cms_template->processRender($ctypes_moderator_tpl, array(
                'moderator' => $moderator,
                'not_use_trash' => !$this->useModerationTrash,
                'ctype'     => array('name' => $this->name, 'controller' => $this->name)
            ), new cmsRequest(array(), cmsRequest::CTX_INTERNAL)),
            'id'    => $user['id']
        ));

    }

    public function actionModeratorsDelete(){

        if (!$this->request->isAjax()) { cmsCore::error404(); }

        $id = $this->request->get('id', 0);
        if (!$id) { cmsCore::error404(); }

        $moderators = $this->model_moderation->getContentTypeModerators($this->name);

        if (!isset($moderators[$id])){
            return $this->cms_template->renderJSON(array(
                'error' => true
            ));
        }

        $this->model_moderation->deleteContentTypeModerator($this->name, $id);

        return $this->cms_template->renderJSON(array(
            'error' => false
        ));

    }

}
