<?php
if(!empty($data['form']['css'])){
    foreach($data['form']['css'] as $_item){
        $this->addTplCSS($_item);
    }
}
if(!empty($data['form']['js'])){
    foreach($data['form']['js'] as $_item){
        $this->addTplJS($_item);
    }
}
if(!empty($data['form']['c_css'])){
    foreach($data['form']['c_css'] as $_item){
        $this->addControllerCSS($_item);
    }
}
if(!empty($data['form']['c_js'])){
    foreach($data['form']['c_js'] as $_item){
        $this->addControllerJS($_item);
    }
}

if(empty($data['form']['no_page_title'])){
    $title = (($do === 'edit') ? '' : (LANG_CREATE.($data['labels']['create'] ? ' '.$data['labels']['create'].' ' : '').' — ')).implode(' — ', $titles);
    $this->setPageTitle($title);
}

if(empty($data['form']['no_crumb'])){
    if(!empty($data['items_title']) && empty($crumbs)){
        $this->addBreadcrumb($data['items_title'], $this->href_to($data['items_name']));
    }
    if(!empty($crumbs)){
        foreach($crumbs as $crumb){
            $this->addBreadcrumb($crumb['title'], $crumb['link']);
        }
    }
}

if(empty($data['form']['no_title']) && !empty($data['items_title'])){
    if($do === 'edit'){
        echo '<h2>'.(LANG_EDIT.($data['labels']['create'] ? ' '.$data['labels']['create'] : '').
                (!empty($item[$data['title_field']]) ? ' &laquo;'.$item[$data['title_field']].'&raquo;' : '')).'</h2>';
    }else{
        echo '<h2>'.((!$item_id ? LANG_CREATE : LANG_CLONE).($data['labels']['create'] ? ' '.$data['labels']['create'] : '').
                (!empty($item[$data['title_field']]) ? ' &laquo;'.$item[$data['title_field']].'&raquo;' : '')).'</h2>';
    }
}

$this->addToolButton(array(
    'class' => 'save',
    'title' => LANG_SAVE,
    'href'  => 'javascript:icms.forms.submit()'
));
$this->addToolButton(array(
    'class' => 'cancel',
    'title' => LANG_CANCEL,
    'href'  => $this->href_to($back_url?:'')
));

$this->renderForm($form, $item, array(
    'action' => '',
    'method' => 'post',
), $errors);

if(!empty($data['form']['pure_js'])){ ?>
<script type="text/javascript">
    <?php echo $data['form']['pure_js']; ?>
</script>
<?php }
