<?php

namespace controller;

class Index {
    function __constructor(){
        echo 'index __constructor';
    }
    function auth(){
        return 1;
    }
    function ok(){
        echo 'hello index controller<br/>';

        M('index')->index();
    }
}
