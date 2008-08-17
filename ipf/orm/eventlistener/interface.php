<?php

interface IPF_ORM_EventListener_Interface
{
    public function preTransactionCommit(IPF_ORM_Event $event);
    public function postTransactionCommit(IPF_ORM_Event $event);
    public function preTransactionRollback(IPF_ORM_Event $event);
    public function postTransactionRollback(IPF_ORM_Event $event);
    public function preTransactionBegin(IPF_ORM_Event $event);
    public function postTransactionBegin(IPF_ORM_Event $event);
    public function postConnect(IPF_ORM_Event $event);
    public function preConnect(IPF_ORM_Event $event);
    public function preQuery(IPF_ORM_Event $event);
    public function postQuery(IPF_ORM_Event $event);
    public function prePrepare(IPF_ORM_Event $event);
    public function postPrepare(IPF_ORM_Event $event);
    public function preExec(IPF_ORM_Event $event);
    public function postExec(IPF_ORM_Event $event);
    public function preError(IPF_ORM_Event $event);
    public function postError(IPF_ORM_Event $event);
    public function preFetch(IPF_ORM_Event $event);
    public function postFetch(IPF_ORM_Event $event);
    public function preFetchAll(IPF_ORM_Event $event);
    public function postFetchAll(IPF_ORM_Event $event);
    public function preStmtExecute(IPF_ORM_Event $event);
    public function postStmtExecute(IPF_ORM_Event $event);
}
