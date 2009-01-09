<?php

class ListFilter{
    function __construct($local, $foreign, $choices, $title){
    	$this->local = $local;
    	$this->foreign = $foreign;
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

    public function setUp(){
        $this->model = new $this->modelName;
    }

    public function getPerms($request){
        return array('view', 'add', 'change', 'delete');
    }

    protected function setInlines($model, &$data){
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
                $url = IPF_HTTP_URL_urlForView('IPF_Admin_Views_ListItems', array($lapp, $lmodel));
                return new IPF_HTTP_Response_Redirect($url);
            }
        }
        else{
            $form = $this->_getAddForm($this->model,null,array('user_fields'=>$this->fields()));
            $this->_setupAddForm($form);
            $data = array();
            $this->setInlines($this->model, &$data);
        }

        $context = array(
        	'mode'=>'add',
            'page_title'=>'Add '.$this->modelName,
            'classname'=>$this->modelName,
            'form'=>$form,
            'inlineInstances'=>$this->inlineInstances,
            'lapp'=>$lapp,
            'perms'=>$this->getPerms($request),
            'lmodel'=>$lmodel,
            'admin_title' => IPF::get('admin_title'),
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
            $this->setInlines($o, &$data);
        }

        $context = array(
        	'mode'=>'change',
            'page_title'=>'Edit '.$this->modelName,
            'classname'=>$this->modelName,
            'object'=>$o,
            'form'=>$form,
            'inlineInstances'=>$this->inlineInstances,
            'lapp'=>$lapp,
            'perms'=>$this->getPerms($request),
            'lmodel'=>$lmodel,
	       	'admin_title' => IPF::get('admin_title'),
        );
        return IPF_Shortcuts::RenderToResponse($this->_getChangeTemplate(), $context, $request);
    }

    public function DeleteItem($request, $lapp, $lmodel, $o){
        if ($request->method == 'POST'){
            AdminLog::logAction($request, $o, AdminLog::DELETION);
            $o->delete();
            $url = IPF_HTTP_URL_urlForView('IPF_Admin_Views_ListItems', array($lapp, $lmodel));
            return new IPF_HTTP_Response_Redirect($url);
        }
        $context = array(
            'page_title'=>'Delete '.$this->modelName,
            'classname'=>$this->modelName,
            'object'=>$o,
            'lapp'=>$lapp,
            'lmodel'=>$lmodel,
            'affected'=>array(),
	       	'admin_title' => IPF::get('admin_title'),
        );
        return IPF_Shortcuts::RenderToResponse('admin/delete.html', $context, $request);
    }

    protected function _ListFilterQuery($request){
    	foreach($this->filters as $f){
    		$param_name = 'filter_'.$f->local;
    		if (isset($request->GET[$param_name])){
    		    $id = $request->GET[$param_name];
    		    if ($f->IsChoice($id)){
    				$this->q->where($f->local.'='.$id);
    		    }
    		}
    	}
    }

    protected function _GetFilters($request){
    	$this->filters = array();
    	$rels = $this->model->getTable()->getRelations();
        foreach($this->_listFilters() as $f){
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
        }
    }

    public function ListItems($request){
        $this->ListItemsQuery();
        $this->_GetFilters($request);
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
        	'orderable'=>method_exists($this, 'list_order'),
            'page_title'=>$this->modelName.' List',
            'header'=>$this->header,
            'objects'=>$objects,
            'pager'=>$pager,
            'classname'=>$this->modelName,
            'perms'=>$this->getPerms($request),
            'filters'=>$this->filters,
	       	'admin_title' => IPF::get('admin_title'),
        );
        return IPF_Shortcuts::RenderToResponse('admin/items.html', $context, $request);
    }
}
