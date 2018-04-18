<?php
namespace helper;

class MysqlModel extends ChainSql{
    public $master = array();
    public $slaves = array();

    private $link ;
    function setConfig($master, $slaves = array() ){
        $this->master = $master;
        $this->slaves = $slaves;

        if( !empty($master['TABLE_PREFIX']) ){
            $this->prefix($master['TABLE_PREFIX']);
        }
        $table = strtolower (array_pop( explode('\\',get_class($this) )) ) ;
        $this->table( $table );
    }
    function lastId(){
        return mysqli_insert_id($this->link);
    }
    function getLink( $config ){

        if( empty($this->link) ){
            $this->link = @mysqli_connect($config['HOST'], $config['USERNAME'], $config['PASSWORD'], $config['DBNAME'], $config['PORT']);
            if (!$this->link) {
                die('Connect Error: ' . mysqli_connect_error());
            }
            mysqli_query($this->link,  'SET NAMES UTF8');
        }
        return $this->link;
    }

    function getConfig( $sql ){
        $master = $this->master;
        $slaves = $this->slaves;

        if( substr($sql,0,6) === 'SELECT' ){
            return $master;
        }else{
            $count = count( $slaves );
            if( $count  === 0 ){
                return $master;
            }
            return $slaves[ time() % $count ];
        }
    }

    function query( $sqlArray ){
        $sqlArr = array_filter($sqlArray);
        $sqlStr = implode( ' ' , $sqlArr);

        if( !empty($this->operate['debugger']) ){
            die( $sqlStr);
        }
        $config   = $this->getConfig( $sqlStr );
        $link     = $this->getLink( $config );
        $resource = mysqli_query($link, $sqlStr, MYSQLI_STORE_RESULT);

        if( empty($resource)){
            throw new \Exception( mysqli_error($this->handle), 1);
        }

        $result = array();
        while ($row = mysqli_fetch_assoc($resource)) {
            $result[] = $row;
        }
        mysqli_free_result($resource);

        return $result;

    }
    function __destruct(){
        if ( !empty($this->link) ) {
            mysqli_close($this->link);
        }
    }
}
