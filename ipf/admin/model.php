<?php

abstract class BaseListFilter{
    function __construct($title, $choices){
        $this->choices = $choices;
        $this->title = $title;
    }

    function IsChoice($id){
        foreach($this->choices as &$ch){
            if ($ch['id']==$id)
                return true;
        }
        return false;
    }

    function selected(){
        foreach($this->choices as &$ch){
            if ( ($ch['id']!='') && ($ch['selected']===true) )
                return true;
        }
        return false;
    }

    abstract function FilterQuery($request,$q);
}

class ListFilter extends BaseListFilter{
    function __construct($local, $foreign, $choices, $title){
        parent::__construct($title, $choices);
        $this->local = $local;
        $this->foreign = $foreign;
    }

    function FilterQuery($request,$q){
        $param_name = 'filter_'.$this->local;
        if (isset($request->GET[$param_name])){
            $id = $request->GET[$param_name];
            if ($this->IsChoice($id)){
                $q->where($this->local.'='.$id);
            }
        }
    }
}

class ListTreeFilter extends BaseListFilter{
   function __construct($name, $title, $model, $fields){
        $this->name = $name;
        $choices = array();
        $choices[] = array(
            'id'=>null,
            'param'=>'',
            'name'=>'All',
            'selected'=>false,
        );
        $levels = array();

        $mrels = $model->getTable()->getRelations();
        $this->fields = array();
        foreach($fields as $fname){
            if (array_key_exists($fname, $mrels)){
                $n = count($this->fields);
                if ($n==0)
                    $parent_key = null;
                else
                    $parent_key = $this->fields[$n-1]['local'];
                $this->fields[] = array(
                    'name'=>$fname,
                    'local'=>$mrels[$fname]->getLocal(),
                    'parent_key'=>$parent_key,
                    'class'=>$mrels[$fname]->getClass(),
                    'objects'=>$this->_getObjects($mrels[$fname]->getClass()),
                );
            }
        }
        $this->_collectTreeRecursive(&$choices);
        parent::__construct($title, $choices);
    }

    protected function _getObjects($modelName)
    {
        return IPF_ORM_Query::create()
            ->from($modelName)
            ->orderby('ord')
            ->execute();
    }

    protected function _collectTreeRecursive(&$choices,$level=0,$parent_id=null,$valname=''){
        foreach($this->fields[$level]['objects'] as $o){
            if ($level>0){
                $foreign = $this->fields[$level]['parent_key'];
                if ($parent_id!=$o->$foreign)
                    continue;
            }
            $this->_addObject($o, &$choices, $level, $valname);
            if ($level<(count($this->fields)-1)){
                $this->_collectTreeRecursive(&$choices,$level+1,$o->id,$valname.$o->id.'.');
            }
        }
    }

    protected function _addObject($o, &$choices, $level, $valname)
    {
        $name = str_repeat("-", $level).$o->name;
        $id = $valname.$o->id;

        $choices[] = array(
            'id'=>$id,
            'param'=>'filter_'.$this->name.'='.$id,
            'name'=>$name,
            'selected'=>false,
        );
    }
    function SetSelect($request){
        $sel_id = @$request->GET['filter_'.$this->name];
        foreach($this->choices as &$ch){
            $ch['selected']= ($sel_id==$ch['id']);
        }
    }

    function FilterQuery($request,$q){
        $param_name = 'filter_'.$this->name;
        if (isset($request->GET[$param_name])){
            $id = $request->GET[$param_name];
            if ($this->IsChoice($id)){
                $l = explode(".",$id);
                $wh = array();
                for($i=0; $i<count($this->fields); $i++){
                    if ($i>=(count($l)))
                        $wh[] = $this->fields[$i]['local'].' IS NULL';
                    else
                        $wh[] = $this->fields[$i]['local'].'='.$l[$i];
                }
                $dql = '';
                foreach($wh as $w){
                    if ($dql!='')
                        $dql .= ' AND ';
                    $dql .= $w;
                }
                $q->where($dql);
            }
        }
    }
}

class IPF_Admin_Model{
    static $models = array();

    public static function register($classModel, $classAdmin){
        IPF_Admin_Model::$models[$classModel] = new $classAdmin($classModel);
    }

    public static function isModelRegister($classModel){
        if (array_key_exists($classModel, IPF_Admin_Model::$models))
            return true;
        return false;
    }

    public static function getModelAdmin($classModel){
        if (array_key_exists($classModel, IPF_Admin_Model::$models)){
            $ma = IPF_Admin_Model::$models[$classModel];
            $ma->setUp();
            return $ma;
        }
        return null;
    }

    var $modelName = null;
    var $model = null;
    var $inlineInstances = array();
    var $perPage = 50;

    public function __construct($modelName){
        $this->modelName = $modelName;
    }

    public function verbose_name(){
        return IPF_Utils::humanTitle($this->modelName);
    }

    public function setUp(){
        $this->model = new $this->modelName;
    }

    public function getPerms($request){
        return array('view', 'add', 'change', 'delete');
    }

    protected function setInlines($model, $data=null){
        $il = $this->inlines();
        if (is_array($il)){
            foreach($il as $inlineName=>$inlineClassName){
                $this->inlineInstances[] = new $inlineClassName($model,$data);
            }
        }
    }

    protected function saveInlines($obj){
        foreach($this->inlineInstances as $inlineInstance){
            $inlineInstance->save($obj);
        }
    }

    protected function _listFilters(){
        return array();
    }

    protected function _setupEditForm($form){
        $this->_setupForm($form);
    }

    protected function _setupAddForm($form){
        $this->_setupForm($form);
    }

    protected function _setupForm($form){
        
    }

    public function fields(){return null;}

    public function inlines(){return null;}

    public function isValidInlines(){
        foreach($this->inlineInstances as &$il){
            if ($il->isValid()===false){
                return false;
            }
        }
        return true;
    }

    public function ListItemsHeader(){
        $this->header = array();
        if (method_exists($this,'list_display'))
            $this->names = $this->list_display();
        else
            $this->names = $this->model->getTable()->getColumnNames();

        foreach ($this->names as $name){
            $this->header[$name] = new IPF_Template_ContextVars(array(
                'title'=>IPF_Utils::humanTitle($name),
                'name'=>$name,
                'sortable'=>null,
            ));
        }
        return $this->header;
    }

    public function ListItemsQuery(){
        if (method_exists($this->model,'ordering'))
            $ord = $this->model->ordering();
        else
            $ord = '1 desc';
        $this->q = IPF_ORM_Query::create()->from($this->modelName)->orderby($ord);
    }

    public function ListRow($o){
        $row = array();

        foreach($this->header as &$h){
            $listMethod = 'column_'.$h['name'];
            if (method_exists($this,$listMethod))
                $str = $this->$listMethod($o);
            else{
                $t = $o->getTable()->getTypeOf($h['name']);
                $str = $o->$h['name'];
                if ($t=='boolean'){
                    if ($str)
                        $str = '<img src="'.IPF::get('admin_media_url').'img/icon-yes.gif" alt="True" />';
                    else
                        $str = '<img src="'.IPF::get('admin_media_url').'img/icon-no.gif" alt="False" />';
                }
            }
            $row[$h['name']] = $str;
        }
        $this->linksRow(&$row, $o);
        return $row;
    }

    protected function linksRow($row, $o){
        if (method_exists($this,'list_display_links')){
            $links_display = $this->list_display_links();
        }else{
            $links_display = null;
            $i = 1;
        }
        foreach($row as $name=>&$v){
            if ($links_display){
                if (array_search($name, $links_display)!==false)
                    $v = '<a href="'.$this->UrlForResult($o).'">'.$v.'</a>';
            }else{
                if ($i==1)
                    $v = '<a href="'.$this->UrlForResult($o).'">'.$v.'</a>';
                $i++;
            }
        }
    }

    protected function UrlForResult($o){
        return  $o->__get($this->model->getTable()->getIdentifier()).'/';
    }

    protected function _getForm($model_obj, $data, $extra){
        return IPF_Shortcuts::GetFormForModel($model_obj,$data,$extra);
    }

    protected function _getEditForm($model_obj, $data, $extra){
        return $this->_getForm($model_obj, $data, $extra);
    }

    protected function _getAddForm($model_obj, $data, $extra){
        return $this->_getForm($model_obj, $data, $extra);
    }

    protected function _getAddTemplate(){
        return 'admin/change.html';
    }

    protected function _getChangeTemplate(){
        return 'admin/change.html';
    }

    protected function _beforeEdit($o){
        $this->_beforeChange($o);
    }

    protected function _beforeAdd($o){
        $this->_beforeChange($o);
    }

    protected function _beforeChange($o){
    }

    protected function _afterEdit($o){
        $this->_afterChange($o);
    }

    protected function _afterAdd($o){
        $this->_afterChange($o);
    }

    protected function _afterChange($o){
    }

    // Views Function
    public function AddItem($request, $lapp, $lmodel){
        if ($request->method == 'POST'){
            $this->_beforeAdd(new $this->model());
            $data = $request->POST+$request->FILES;
            $form = $this->_getAddForm($this->model, &$data, array('user_fields'=>$this->fields()));
            $this->_setupAddForm($form);
            $this->setInlines($this->model, &$data);
            if ($form->isValid()) {
                $item = $form->save();
                $this->saveInlines($item);
                AdminLog::logAction($request, $item, AdminLog::ADDITION);
                $this->_afterAdd($item);
                $url = @$request->POST['ipf_referrer'];
                if ($url=='')
                    $url = IPF_HTTP_URL_urlForView('IPF_Admin_Views_ListItems', array($lapp, $lmodel));
                return new IPF_HTTP_Response_Redirect($url);
            }
        }
        else{
            $form = $this->_getAddForm($this->model,null,array('user_fields'=>$this->fields()));
            $this->_setupAddForm($form);
            $data = array();
            $this->setInlines($this->model);
        }

        $context = array(
            'mode'=>'add',
            'page_title'=>'Add '.$this->verbose_name(),
            'classname'=>$this->verbose_name(),
            'form'=>$form,
            'inlineInstances'=>$this->inlineInstances,
            'lapp'=>$lapp,
            'perms'=>$this->getPerms($request),
            'lmodel'=>$lmodel,
            'admin_title' => IPF::get('admin_title'),
            'indexpage_url'=>IPF::get('indexpage_url','/'),
        );
        return IPF_Shortcuts::RenderToResponse($this->_getAddTemplate(), $context, $request);
    }

    public function EditItem($request, $lapp, $lmodel, $o){
        if ($request->method == 'POST'){
            $this->_beforeEdit($o);
            $data = $request->POST+$request->FILES;
            $form = $this->_getEditForm($o,&$data,array('user_fields'=>$this->fields()));
            $this->_setupEditForm($form);
            $this->setInlines($o, &$data);
            if ( ($form->isValid()) && ($this->isValidInlines()) ) {
                $item = $form->save();
                $this->saveInlines($item);
                AdminLog::logAction($request, $item, AdminLog::CHANGE);
                $this->_afterEdit($item);
                $url = @$request->POST['ipf_referrer'];
                if ($url=='')
                    $url = IPF_HTTP_URL_urlForView('IPF_Admin_Views_ListItems', array($lapp, $lmodel));
                return new IPF_HTTP_Response_Redirect($url);
            }
        }
        else{
            $data = $o->getData();
            foreach($o->getTable()->getRelations() as $rname=>$rel){
                $pk = $rel->getTable()->getIdentifier();
                if (array_search($rname,$this->fields())){
                    if ($rel->getType()==IPF_ORM_Relation::MANY_AGGREGATE){
                        $data[$rname] = array();
                        foreach($rel->fetchRelatedFor($o) as $ri)
                            $data[$rname][] = $ri->$pk;
                    }
                }
            }
            $form = $this->_getEditForm($o,&$data,array('user_fields'=>$this->fields()));
            $this->_setupEditForm($form);
            $this->setInlines($o);
        }

        $context = array(
            'mode'=>'change',
            'page_title'=>'Edit '.$this->verbose_name(),
            'classname'=>$this->verbose_name(),
            'object'=>$o,
            'form'=>$form,
            'inlineInstances'=>$this->inlineInstances,
            'lapp'=>$lapp,
            'perms'=>$this->getPerms($request),
            'lmodel'=>$lmodel,
            'admin_title' => IPF::get('admin_title'),
            'indexpage_url'=>IPF::get('indexpage_url','/'),
        );
        return IPF_Shortcuts::RenderToResponse($this->_getChangeTemplate(), $context, $request);
    }

    public function DeleteItem($request, $lapp, $lmodel, $o){
        if ($request->method == 'POST'){
            AdminLog::logAction($request, $o, AdminLog::DELETION);
            $o->delete();
            $url = @$request->POST['ipf_referrer'];
            if ($url=='')
                $url = IPF_HTTP_URL_urlForView('IPF_Admin_Views_ListItems', array($lapp, $lmodel));
            return new IPF_HTTP_Response_Redirect($url);
        }
        $context = array(
            'page_title'=>'Delete '.$this->modelName,
            'classname'=>$this->verbose_name(),
            'object'=>$o,
            'lapp'=>$lapp,
            'lmodel'=>$lmodel,
            'affected'=>array(),
            'ipf_referrer'=>@$request->GET['ipf_referrer'],
            'admin_title' => IPF::get('admin_title'),
            'indexpage_url'=>IPF::get('indexpage_url','/'),
        );
        return IPF_Shortcuts::RenderToResponse('admin/delete.html', $context, $request);
    }

    protected function _ListFilterQuery($request){
        foreach($this->filters as $f){
            $f->FilterQuery($request,$this->q);
        }
    }

    protected function _isSearch(){
        if (method_exists($this,'_searchFields'))
            return true;
        return false;
    }

    protected function _ListSearchQuery($request){
        $this->search_value = null;
        if (!$this->_isSearch())
            return;
        $fields = $this->_searchFields();
        $this->search_value = @$request->GET['q'];
        if ($this->search_value!=''){
            $wh = '';
            $whv = array();
            foreach ($fields as $f){
                if ($wh!='') $wh.=' or ';
                $wh.= $f.' like ?';
                $whv[] = '%'.$this->search_value.'%';
            }
            $this->q->where($wh,$whv);
            return true;
        }
        return false;
    }

    protected function _GetFilters($request){
        $this->filters = array();
        $rels = $this->model->getTable()->getRelations();
        foreach($this->_listFilters() as $f){
            if (is_string($f)){
                $local = $rels[$f]['local'];
                $foreign = $rels[$f]['foreign'];
                $sel_id = @$request->GET['filter_'.$local];
                $choices = array();
                $choices[] = array(
                    'id'=>null,
                    'param'=>'',
                    'name'=>'All',
                    'selected'=>($sel_id==''),
                );
                foreach (IPF_ORM::getTable($rels[$f]['class'])->findAll() as $val){
                    $selected = false;
                    $id = $val[$foreign];
                    if ($sel_id==$id)
                        $selected = true;
                    $choices[] = array(
                        'id'=>$id,
                        'param'=>'filter_'.$local.'='.$id,
                        'name'=>(string)$val,
                        'selected'=>$selected,
                    );
                }
                $this->filters[$f] = new ListFilter($local, $foreign, $choices, 'By '.IPF_Utils::humanTitle($f));
            } else {
                if ( (get_class($f)=='ListTreeFilter') || is_subclass_of($f, 'ListTreeFilter')){
                    $f->SetSelect($request);
                    $this->filters[$f->name] = $f;
                }
            }
        }
    }

    protected function _orderable(){
        return method_exists($this, 'list_order');
    }

    public function ListItems($request, $lapp, $lmodel){
        $this->ListItemsQuery();
        $this->_GetFilters($request);
        if (!$this->_ListSearchQuery($request))
            $this->_ListFilterQuery($request);
        $this->ListItemsHeader();

        $currentPage = (int)@$request->GET['page'];

        $url = '';
        foreach ($request->GET as $k=>$v){
            if ($k=='page')
                continue;
            if ($url=='')
                $url = '?';
            else
                $url .= '&';
            $url .= $k.'='.$v;
        }
        if ($url=='')
            $pager_url = '?page={%page_number}';
        else
            $pager_url = $url.'&page={%page_number}';

        $pager = new IPF_ORM_Pager_LayoutArrows(
            new IPF_ORM_Pager($this->q, $currentPage, $this->perPage),
            new IPF_ORM_Pager_Range_Sliding(array('chunk' => 10)),
            $pager_url
        );
        $pager->setTemplate('<a href="{%url}">{%page}</a> ');
        $pager->setSelectedTemplate('<span class="this-page">{%page}</span> ');
        $objects = $pager->getPager()->execute();

        $context = array(
            'orderable'=>$this->_orderable(),
            'page_title'=>$this->page_title(),
            'header'=>$this->header,
            'objects'=>$objects,
            'pager'=>$pager,
            'classname'=>$this->verbose_name(),
            'perms'=>$this->getPerms($request),
            'filters'=>$this->filters,
            'admin_title' => IPF::get('admin_title'),
            'is_search' => $this->_isSearch(),
            'search_value' => $this->search_value,
            'lapp'=>$lapp,
            'lmodel'=>$lmodel,
            'indexpage_url'=>IPF::get('indexpage_url','/'),
        );
        return IPF_Shortcuts::RenderToResponse('admin/items.html', $context, $request);
    }
    
    function page_title(){
        return $this->verbose_name().' List';
    }
}
