<?php
namespace Entry{
    function init(){
        $router = explode('/',  empty( $_REQUEST['r'] ) ? 'index/index' : $_REQUEST['r'] );
        $module = 'controller'.'\\'.$router[0];
        $method = empty($router[1]) ?  'index' : $router[1];
        $instance = new $module();
        if( method_exists( $instance, 'auth' )  && !$instance->auth( $method ) ){
            throw new \Exception("Authorization fail in $module !", 1);
        }
        $instance->$method();
    }
}
namespace {
    function M( $class , $namespace = 'model' ){
        $name = implode('\\',['',$namespace,$class]);
        return new $name();
    }
    spl_autoload_register(function ( $class ){
        include_once  str_replace('\\','/',$class)  . '.php';
    });
    Entry\init();
}
