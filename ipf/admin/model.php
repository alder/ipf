<?php 

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

    public function __construct($modelName){
        $this->modelName = $modelName;
    }
    
    public function setUp(){
        $this->model = new $this->modelName;
    }
    
    protected function setInlines(&$instance=null){
        $il = $this->inlines();
        if (is_array($il)){
            foreach($il as $inlineName=>$inlineClassName){
                $this->inlineInstances[] = new $inlineClassName(&$this->model,&$instance);
            }
        }
    }
    
    protected function _setupEditForm(&$form){
        $this->_setupForm(&$form);
        $this->setInlines($this->instance);
    }

    protected function _setupAddForm(&$form){
        $this->_setupForm(&$form);
        $this->setInlines();
    }

    protected function _setupForm(&$form){
    }
    
    public function fields(){return null;}

    public function inlines(){return null;}
    
    public function isValidInlines(){
        if ($this->inlineInstances==null)
            return true;
        foreach($this->inlineInstances as &$il){
            if ($il->isValid()===false)
                return false;
        }
        return true;
    }

    public function ListItemsHeader(){
        $this->header = array();
        if (method_exists($this,'list_display'))
            $this->names = $this->list_display();
        else
            $this->names = $this->qe->getTable()->getColumnNames();
            
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
        $this->q = IPF_ORM_Query::create()->from($this->modelName);
    }
    
    public function ListRow($o){
        $row = array();
        
        foreach($this->header as &$h){
            $listMethod = 'column_'.$h['name'];
            if (method_exists($this,$listMethod))
                $str = $this->$listMethod(&$o);
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
        $this->LinksRow(&$row, &$o);
        return $row;
    }
    
    protected function LinksRow(&$row, &$o){
        if (method_exists($this,'list_display_links')){
            $links_display = $this->list_display_links();
        }else{
            $links_display = null;
            $i = 1;
        }
        foreach($row as $name=>&$v){
            if ($links_display){
                if (array_search($name, $links_display)!==false)
                    $v = '<a href="'.$this->UrlForResult(&$o).'">'.$v.'</a>';
            }else{
                if ($i==1)
                    $v = '<a href="'.$this->UrlForResult(&$o).'">'.$v.'</a>';
                $i++;
            }
        }
    }
    
    protected function UrlForResult(&$o){
        return  $o->__get($this->qe->getTable()->getIdentifier()).'/';
    }
    
    // Views Function
    public function AddItem($request, $lapp, $lmodel){
        if ($request->method == 'POST'){
            $form = IPF_Shortcuts::GetFormForModel($this->model,$request->POST+$request->FILES,array('user_fields'=>$this->fields()));
            $this->_setupAddForm(&$form);
            if ($form->isValid()) {
                $item = $form->save();
                AdminLog::logAction($request, $item, AdminLog::ADDITION);
                $url = IPF_HTTP_URL_urlForView('IPF_Admin_Views_ListItems', array($lapp, $lmodel));
                return new IPF_HTTP_Response_Redirect($url);
            }
        }
        else{
            $form = IPF_Shortcuts::GetFormForModel($this->model,null,array('user_fields'=>$this->fields()));
            $this->_setupAddForm(&$form);
        }
        $context = array(
            'page_title'=>'Add '.$this->modelName, 
            'classname'=>$this->modelName,
            'form'=>$form,
            'lapp'=>$lapp,
            'lmodel'=>$lmodel,
        );
        return IPF_Shortcuts::RenderToResponse('admin/add.html', $context, $request);
    }

    public function EditItem($request, $lapp, $lmodel, $o){
        if ($request->method == 'POST'){
            $form = IPF_Shortcuts::GetFormForModel($o,$request->POST+$request->FILES,array('user_fields'=>$this->fields()));
            $this->_setupEditForm(&$form);
            if ( ($form->isValid()) && ($this->isValidInlines()) ) {
                $item = $form->save();
                AdminLog::logAction($request, $item, AdminLog::CHANGE);
                $url = IPF_HTTP_URL_urlForView('IPF_Admin_Views_ListItems', array($lapp, $lmodel));
                return new IPF_HTTP_Response_Redirect($url);
            }
        }
        else{
            $form = IPF_Shortcuts::GetFormForModel($o,$o->getData(),array('user_fields'=>$this->fields()));
            $this->_setupEditForm(&$form);
        }
        
        $context = array(
            'page_title'=>'Edit '.$this->modelName, 
            'classname'=>$this->modelName,
            'object'=>$o,
            'form'=>$form,
            'inlineInstances'=>$this->inlineInstances,
            'lapp'=>$lapp,
            'lmodel'=>$lmodel,
        );
        return IPF_Shortcuts::RenderToResponse('admin/change.html', $context, $request);
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
        );
        return IPF_Shortcuts::RenderToResponse('admin/delete.html', $context, $request);
    }

    public function ListItems($request){
        $this->ListItemsQuery();
        $this->qe = $this->q->execute();
        $this->ListItemsHeader();
        //print_r($this->qe->getTable()->getIdentifier());
        $context = array(
            'page_title'=>$this->modelName.' List', 
            'header'=>$this->header,
            'classname'=>$this->modelName,
            'objects'=>$this->qe,
            'classname'=>$this->modelName,
        );
        return IPF_Shortcuts::RenderToResponse('admin/items.html', $context, $request);
    }
}
