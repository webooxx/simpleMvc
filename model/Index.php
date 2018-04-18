<?php
namespace model;

class Index  extends \helper\MysqlModel{

    function __construct(){
        $this->setConfig(  \config\Db::getMaster() );
    }

    function index(){
        echo 'i am index model<br/>';
        var_dump($this);
    }
}
