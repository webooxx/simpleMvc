<?php
namespace config;

class Db {
    static function getMaster(){
        return array(
            'HOST'          => '127.0.0.1',
            'PORT'          => '3306',
            'DBNAME'        => 'iwenjuan_dev',
            'USERNAME'      => 'root',
            'PASSWORD'      => '',
            'TABLE_PREFIX'  => 'iw_',
        );
    }
}
