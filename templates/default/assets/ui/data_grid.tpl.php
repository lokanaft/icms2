<?php
if(!empty($data['grid']['css'])){
    foreach($data['grid']['css'] as $_item){
        $this->addTplCSS($_item);
    }
}
if(!empty($data['grid']['js'])){
    foreach($data['grid']['js'] as $_item){
        $this->addTplJS($_item);
    }
}
if(!empty($data['grid']['c_css'])){
    foreach($data['grid']['c_css'] as $_item){
        $this->addControllerCSS($_item);
    }
}
if(!empty($data['grid']['c_js'])){
    foreach($data['grid']['c_js'] as $_item){
        $this->addControllerJS($_item);
    }
}

if(empty($data['grid']['no_page_title'])){
    $this->setPageTitle(implode(' — ', $titles));
}

if(empty($data['grid']['no_crumb'])){

    if(!empty($data['items_title']) && empty($crumbs)){
        $this->addBreadcrumb($data['items_title']);
    }
    if(!empty($crumbs)){
        foreach($crumbs as $crumb){
            $this->addBreadcrumb($crumb['title'], $crumb['link']);
        }
    }
}

if(empty($data['no_add'])){
    $this->addToolButton(array(
        'class' => 'add',
        'title' => LANG_CREATE.(!empty($data['labels']['create']) ? ' '.$data['labels']['create'] : ''),
        'href'  => $this->href_to($data['items_name'].'_add'.($data['parents'] && !empty($item_id) ? '/0/'.$item_id : ''))
    ));
}
if(!empty($item_id) && !empty($parent_data) && empty($data['no_edit'])){
    $this->addToolButton(array(
        'class' => 'edit',
        'title' => LANG_EDIT.(!empty($parent_data['labels']['create']) ? ' '.$parent_data['labels']['create'] : ''),
        'href'  => $this->href_to($parent_data['items_name'].'_edit/'.$item_id)
    ));
}
if(!empty($data['grid']['buttons'])){
    foreach($data['grid']['buttons'] as $button){
        $this->addToolButton($button);
    }
}
if(!empty($grid['options']['is_sortable'])){
    $this->addToolButton(array(
        'class' => 'save',
        'title' => LANG_SAVE_ORDER,
        'href'  => null,
        'onclick' => "icms.datagrid.submit('{$this->href_to($data['items_name'].'_reorder')}')"
    ));
}
if(!empty($data['grid']['help_url'])){
    $this->addToolButton(array(
        'class' => 'help',
        'title' => LANG_HELP,
        'target' => '_blank',
        'href'  => $data['grid']['help_url']
    ));
}

if(empty($data['grid']['no_title']) && !empty($data['items_title'])){
    echo '<h2>'.$data['items_title'].'</h2>';
}
if(!empty($parent)){
    $this->renderGrid($this->href_to($parent['items_name'].'_childs_grid', array($item_id, $data['items_name'])), $grid);
}else{
    $this->renderGrid($this->href_to('grid', array($data['items_name'])), $grid);
}

if(empty($data['grid']['no_sort_button']) && !empty($grid['options']['is_sortable'])){ ?>
<div class="buttons">
    <?php echo html_button(LANG_SAVE_ORDER, 'save_button', "icms.datagrid.submit('{$this->href_to($data['items_name'].'_reorder')}')"); ?>
</div>
<?php }

if(!empty($data['grid']['pure_js'])){ ?>
<script type="text/javascript">
    <?php echo $data['grid']['pure_js']; ?>
</script>
<?php }
