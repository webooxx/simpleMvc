<?php
namespace helper;

/**
 * 链式操作SQL
 */
class ChainSql{

    public $operate = array(
        'table'  => '',
        'prefix' => '',
        'field'  => '',
        'where'  => '',
        'group'  => '',
        'order'  => '',
        'limit'  => '',
        'having' => '',
        'data'   => '',
    );

    function query( $sql ){}

    final function getRealable(){
        $list   = $this->operate['table'];
        $prefix = $this->operate['prefix'];
        $sqlStr = array();
        foreach( $list as $table ){
            $sqlStr[] = str_replace( '$$__tablePrefix__$$' , $prefix , $table);
        }
        return implode(' , ',$sqlStr);
    }

    final function __call( $name, $args ){

        $arg = isset($args[0]) ? $args[0] : array();

        //  设定 ，执行 ，修饰
        switch ( $name ) {
            //  --->    设定 - 表
            case 'table':
                if (count(explode(',', $arg)) > 1) {
                    $_tableA = explode(',', $arg);
                } else {
                    $_tableA[] = $arg;
                }
                foreach ($_tableA as $item) {
                    $tableMap = preg_split('/(\s+as\s+|\s+)/i', trim($item));
                    $_tableB[] = '`$$__tablePrefix__$$' .  trim($tableMap[0]) . '`' . (count($tableMap) > 1 ? ' AS ' . trim($tableMap[1]) : '');
                }
                $this->operate['table'] = $_tableB;
                break;
            //  --->    设定 - 表 - 前缀
            case 'prefix':
                $this->operate['prefix'] = $arg;
                break;

            //  --->    设定 - 字段
            case 'select':
            case 'field':
                $this->operate['field'] = $arg;
                break;

            //  --->    设定 - 条件
            case 'where':
                if ($arg === 1 || $arg === true) {
                    $this->operate['where'] = '1=1';
                } else if (is_string($arg)) {
                    $this->operate['where'] = $arg;
                } else if (is_array($arg)) {
                    foreach ($arg as $k => $v) {
                        $kvs[] = '`' . trim(addslashes($k)) . '` = ' . (is_numeric($v) ? $v+0 : '\'' . addslashes($v) . '\'');
                    }
                    $this->operate['where'] = implode(' and ', (array)$kvs);
                }
                break;
            //  --->    设定 - 数据
            case 'data':
                if (is_string($arg)) {
                    $arg = explode(',', trim($arg));
                    foreach ($arg as $k => $v) {
                        $one = explode('=', trim($v));
                        $data[trim($one[0])] = trim($one[1]);
                    }

                } else if (array_keys($arg) !== range(0, count($arg) - 1)) {
                    foreach ($arg as $k => $v) {
                        $data[addslashes($k)] = is_string($v) ? addslashes($v) : $v;
                    }
                }
                $arg = $data;
                $this->operate['data'] = $arg;
                break;

            //  --->    执行 - 新增
            case 'add':
                if ($arg) {
                    $this->data($arg);
                }
                $sql[] = 'INSERT INTO';
                $sql[] = $this->getRealable();

                #    有键名的数据
                if (array_keys($this->operate['data']) !== range(0, count($this->operate['data']) - 1)) {
                    $sql[] = '( `' . implode('`,`', array_keys($this->operate["data"])) . '` )';
                }

                foreach ($this->operate['data'] as $k => $v) {
                    $_data[] = is_string($v) ? '\'' . $v . '\'' : $v;
                }
                $sql[] = 'VALUES (' . implode(',', $_data) . ') ';
                return $this->query( $sql );
                break;

            //  --->    执行 - 删除
            case 'del':
            case 'delete':
                if ($arg) {
                    $this->where($arg);
                }
                if (empty($this->operate['where'])) {
                    throw new \Exception("Not allowed to delete all the data!", 1);
                    return null;
                }
                $sql[] = 'DELETE FROM';
                $sql[] = $this->getRealable();
                $sql[] = 'WHERE ' . $this->operate['where'];
                $sql[] = empty($this->operate['limit']) ? ' LIMIT 1 ' : 'LIMIT ' . $this->operate['limit'];
                return $this->query( $sql );
                break;
            case 'deleteAll':
                if ($arg) {
                    $this->where($arg);
                }
                if (empty($this->operate['where'])) {
                    throw new \Exception("Not allowed to delete all the data!", 1);
                    return null;
                }
                $sql[] = 'DELETE FROM';
                $sql[] = $this->getRealable();
                $sql[] = 'WHERE ' . $this->operate['where'];
                return $this->query( $sql );
                break;

            //  --->    执行 - 修改
            case 'save':
                if ($arg) {
                    $this->data($arg);
                }
                if (empty($this->operate['where'])) {
                    throw new \Exception("Not allowed to update all the data!", 1);
                    return null;
                }
                foreach ($this->operate['data'] as $k => $v) {
                    if (substr($v, 0, 1) === substr($v, -1) && substr($v, -1) === '`') {
                        $kvs[] = '`' . $k . '` = ' . substr($v, 1, -1);
                    } else {
                        $kvs[] = '`' . $k . '` = ' . (is_string($v) ? '\'' . $v . '\'' : $v);
                    }
                }
                $sql[] = 'UPDATE';
                $sql[] = $this->getRealable();
                $sql[] = 'SET';
                $sql[] = implode(',', $kvs);
                $sql[] = 'WHERE ' . $this->operate['where'];
                // $sql[] = empty($this->operate['limit']) ? ' ' : 'LIMIT ' . $this->operate['limit'];
                return $this->query( $sql );
                break;

            //  --->    执行 - 查找
            case 'find':
                if ($arg) {
                    $this->where($arg);
                }
                $this->limit(1);
                $result = $this->findAll($arg ? $arg : array());
                return (count($result) == 1) ? $result[0] : $result;
                break;
            case 'findAll':
                if ($arg) {
                    $this->where($arg);
                }
                $sql[] = 'SELECT';
                $sql[] = empty($this->operate['field']) ? '*' : $this->operate['field'];
                $sql[] = 'FROM';
                $sql[] = $this->getRealable();

                $sql[] = $this->operate['where'] ? 'WHERE ' . $this->operate['where'] : '';
                $sql[] = $this->operate['group'] ? 'GROUP BY ' . $this->operate['group'] : '';
                $sql[] = $this->operate['order'] ? 'ORDER BY ' . $this->operate['order'] : '';
                $sql[] = $this->operate['limit'] ? 'LIMIT ' . $this->operate['limit'] : '';
                $sql[] = $this->operate['having'] ? 'HAVING ' . $this->operate['having'] : '';
                return $this->query( $sql );
                break;

            //  --->    执行 - 查找 - 统计
            case 'count' :
                if ($arg) {
                    $this->where($arg);
                }
                $sql[] = 'SELECT';
                $sql[] = ' count(*) as c ';
                $sql[] = 'FROM';
                $sql[] = $this->getRealable();
                $sql[] = $this->operate['where'] ? 'WHERE ' . $this->operate['where'] : '';
                $result = $this->query( $sql );
                return $result[0]['c'];
                break;

            //  --->    修饰 - 条目限定
            case 'limit':
                if (is_array($arg)) {
                    $this->operate['limit'] = implode(',', $arg);
                }
                if( is_int($arg) ){
                    $this->operate['limit'] = '0,'.$arg;
                }
                if (is_string($arg) && !empty($arg)) {
                    $limit = explode(',', $arg);
                    if (count($limit) == 1) {
                        array_unshift($limit, 0);
                    }
                    $this->operate['limit'] = implode(',', $limit);
                }
                break;
            default:
                $this->operate[$name] = $arg;
                break;
        }
        return $this;
    }
}
