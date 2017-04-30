<?php
/**
 * Created by PhpStorm.
 * User: liujun
 * Date: 2017/4/20
 * Time: 23:17
 */
namespace TJDS\Lib;


class BaseModel extends \Phalcon\Mvc\Model
{
    // 如 findByCidAndGid
    public function __call($name, $arguments)
    {
        if( strpos($name, "findBy") != 0) {
            throw new LibException( LibException::SYS_ERR );
        }

        $params = str_replace('findBy', '', $name);

        $opType = "And";
        if( strpos($name, "Or") != false) {
            $opType = "Or";
        }

        $paramArr = explode($opType, $params);
        $paramKeyArr = [];
        foreach ($paramArr as $value){
            if( trim($value) != ""){
                $paramKeyArr[] = lcfirst($value);
            }
        }

        $conditions = [];
        $binds = [];
        foreach ($paramKeyArr as $key) {
            $conditions[] = " $key= :$key";
            $binds[ ":$key" ] = $arguments[0][$key];
        }

        $param = [
            'conditions' => implode(" $opType ", $conditions),
            'binds' => $binds,
        ];

        if ( isset($arguments[0]['order'] ) ){
            $param['order'] = $arguments[0]['order'];
        }else {
            $param['order'] = " update_time DESC ";
        }

        if ( isset($arguments[0]['columns'] ) ){
            $param['columns'] = $arguments[0]['columns'];
        }

        return $this->finds($param);
    }

    /*
     * 查找
     */
    public function finds($data)
    {
        $pdo = $this->getDI()->getShared('db');
        $columns = "*";
        if( isset($data['columns']) ) {
            $columns = trim($data['columns']);
        }
        // sql pre
        $sqlPre = "SELECT $columns FROM ". $this->getSource();
        if( isset($data['conditions']) && trim($data['conditions']) != "" ){
            $sqlPre .= " WHERE ". trim($data['conditions']);
        }

        if( isset($data['order']) ) {
            $sqlPre .= " ORDER BY ".$data['order'];
        }else {
            $sqlPre .= " ORDER BY update_time DESC ";
        }

        $binds = isset($data['binds']) ? $data['binds'] : [];
        return $this->querys($pdo, $sqlPre, $binds);
    }

    /*
     *  查找第一个
     */
    public function findFirsts($param)
    {
        $result = $this->finds($param);
        if(count($result) > 0){
            return $result[0];
        }else {
            return [];
        }
    }


    /*
     * 插入
    */
    public function insert($data)
    {
        $pdo = $this->getDI()->getShared('db');

        $paramArr = [];
        $valuePArr = [];
        $valueArr = [];

        foreach($data as $key => $value){
            if( $value == null){
                continue;
            }
            $paramArr[] = trim($key);
            $valuePArr[] = ":$key";
            $valueArr[":$key"] = $value;
        }

        $sqlPre = "INSERT INTO ".$this->getSource();
        $sqlPre .= " ( ". implode(',', $paramArr). ") VALUES (" . implode(",", $valuePArr).")";
        $ret = $this->execs($pdo, $sqlPre, $valueArr);
        if( $ret == false){
            return false;
        }
        return $pdo->lastInsertId();
    }

    /*
     * 保存
     */
    public function edit($data)
    {
        $pdo = $this->getDI()->getShared('db');

        $sqlPre = "UPDATE ".$this->getSource();

        $paramArr = [];
        $valueArr = [];

        foreach($data as $key => $value){
            if( $key == "saveBy"){
                continue;
            }

            if( $value == null){
                continue;
            }

            $paramArr[] = trim($key). "=:". $key;
            if( is_array($value)){
                $value = implode(",", $value);
            }
            $valueArr[":$key"] = $value;
        }

        $sqlPre .= " SET ". implode(",", $paramArr);

        if( isset($data['saveBy']) ){
            $sqlPre .= " WHERE ".$data['saveBy']. "=:". $data['saveBy'];
        }
        return $this->execs($pdo, $sqlPre, $valueArr);
    }

    /*
     * 删除
     */
    public function delete($data)
    {
        if( ! isset($data['delBy']) ) {
            return true;
        }

        $delBy = trim($data['delBy']);
        if( ! isset($data[$delBy])) {
            return false;
        }

        $valueArr = "";
        if( is_array( $data[$delBy] )) {

            if( count($data[$delBy]) == 0 ){
                return true;
            }
            $valueArr [ ":$delBy" ] =  implode(' OR ', $data[$delBy]);
        }else {
            $valueArr [ ":$delBy"  ] =  $data[$delBy];
        }

        $pdo = $this->getDI()->getShared('db');
        $sqlPre = "DELETE FROM  ".$this->getSource(). " WHERE $delBy= (:$delBy)";
        return $this->execs($pdo, $sqlPre, $valueArr);
    }

    /*
     *  pdo查询
     */
    public function querys($pdo, $sqlPre, $binds)
    {
        try{
            $stmt = $pdo->prepare( $sqlPre );
            $res = $stmt ->execute( $binds);
            if($res != false){
                $stmt->setFetchMode(\Phalcon\DB::FETCH_ASSOC);
                $res =  $stmt->fetchAll();
            }

            if(  Trace::getInstance()->getValid() == 1) {
                $key = $sqlPre." ;".time();
                $debugStr = $this->getDebugStr($sqlPre, $binds);
                Trace::getInstance()->add($key, $debugStr, $res);
            }
            return $res;

        }catch ( \Exception $e ){
            if(  Trace::getInstance()->getValid() == 1) {
                $key = $sqlPre." ;".time();
                $debugStr = $this->getDebugStr($sqlPre, $binds);
                Trace::getInstance()->add($key, $debugStr, $e->getMessage() );
            }
            throw new LibException( LibException::SYS_ERR );
        }
    }

    /*
     * pdo 执行
     */
    public function execs($pdo, $sqlPre, $binds)
    {
        try{
            $stmt = $pdo->prepare( $sqlPre );
            $res = $stmt ->execute( $binds);
            if( Trace::getInstance()->getValid()== 1) {
                $key = $sqlPre." ;".time();
                $debugStr = $this->getDebugStr($sqlPre, $binds);
                Trace::getInstance()->add($key, $debugStr, $res);
            }
            return $res;
        }catch ( \Exception $e ){
            if(  Trace::getInstance()->getValid() == 1) {
                $key = $sqlPre." ;".time();
                $debugStr = $this->getDebugStr($sqlPre, $binds);
                Trace::getInstance()->add($key, $debugStr, $e->getMessage() );
            }
            throw new LibException( LibException::SYS_ERR );
        }
    }

    /*
    * 查询原始sql
    */
    public function queryRaw($sql)
    {
        try{
            $result = $this->getDI()->getShared('db')->query($sql);
            $result->setFetchMode(\Phalcon\DB::FETCH_ASSOC);
            $resultArr = $result->fetchAll();
            if( Trace::getInstance()->getValid()== 1) {
                $key = $sql." ;".time();
                Trace::getInstance()->add($key, $sql, $resultArr );
            }
            return $resultArr;
        }catch (\Exception $e ) {
            if( Trace::getInstance()->getValid()== 1) {
                $key = $sql." ;".time();
                Trace::getInstance()->add($key, $sql, $e->getMessage());
            }
            throw new LibException( LibException::SYS_ERR );
        }
    }

    /*
     * 执行原始sql
     */
    public function execRaw($sql)
    {
        try{
            $result = $this->getDI()->getShared('db')->execute($sql);
            if( Trace::getInstance()->getValid()== 1) {
                $key = $sql." ;".time();
                Trace::getInstance()->add($key, $sql, $result);
            }
            return $result;
        }catch (\Exception $e ) {
            if( Trace::getInstance()->getValid()== 1) {
                $key = $sql." ;".time();
                Trace::getInstance()->add($key, $sql, $e->getCode().":".$e->getMessage());
            }
            throw new LibException( LibException::SYS_ERR );
        }
    }

    public function getFacade($key, $share=false)
    {
        $function = ( $share == "true") ? 'getShared' : 'get';
        return $this->getDI()->$function($key);
    }


    public function setFacade($key, $value, $share=false)
    {
        $function = ( $share == "true") ? 'setShared' : 'set';
        $this->getDI()->$function($key, function() use ($value) {
            return $value;
        });
    }

    /*
    * 获得debug str
    */
    protected function getDebugStr($sqlPre, $binds){
        $debugSql = $sqlPre;
        foreach($binds as $key => $value){
            $debugSql = str_replace($key, "'$value'", $debugSql);
        }
        return $debugSql;
    }

    /*
     * 开始事务
     */
    public function begin()
    {
        return $this->getDI()->getShared('db')->query("BEGIN");
    }

    /*
     * 提交事务
     */
    public function commit()
    {
        return $this->getDI()->getShared('db')->query("COMMIT");
    }

    /*
     * 回滚事务
     */
    public function rollback()
    {
        return $this->getDI()->getShared('db')->query("ROLLBACK");
    }
}
