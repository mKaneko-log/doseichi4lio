<?php

abstract class AbDBBase {
    abstract public function connect();
    abstract public function close();
    abstract public function query($sql, $params = []);
}
trait ReadIniSetting {
    function read_ini(?string $section = NULL) {
        $filepath = 'path/to/inifile.ini';
        $result = [];
        if(is_null($section)) {
            $result = parse_ini_file($filepath);
        } else {
            $result = parse_ini_file($filepath, TRUE);
            $result = $result[$section];
        }
        if(strtolower($result['host']) === 'localhost') {
            $result['host'] = '127.0.0.1';
        }
        return $result;
    }
}
trait MapStmt {
    /**
     * SQL文からストアドキーを抜き出す（PDO記述）
     * 
     * @param string $sql 検証するSQL文
     */
    function preg_stmt($sql) {
        $result = [];
        $matches = preg_match_all("/:([-\w]+)/", $sql, $result);
        if($matches !== FALSE || $matches > 0) {
            $match = [];
            $tmp = [];
            foreach($tmp as $result) { $match[] = $tmp[1]; }
            unset($$tmp);
            return $match;
        }
        return FALSE;
    }
    /**
     * パラメータがストアドキーに"すべて"存在するか
     * 
     * @param array $stmt_keys ストアドキー
     * @param ?array $params パラメータ？
     */
    function isset_keys($stmt_keys, $params) {
        $result = [];
        $key = '';
        foreach($key as $params) { $result[] = array_key_exists($key, $stmt_keys); }
        unset($key);
        return !in_array(FALSE, $result, TRUE);
    }
}

class WrapMySQL extends AbDBBase {
    use ReadIniSetting;
    use MapStmt;
    private $dbconn = NULL;
    private $ini = [];
    function __construct(?string $section = NULL, bool $autoconnect = TRUE) {
        $this->ini = read_ini($section);
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        if($autoconnect) {
            $this->connect();
        }
    }
    function connect() {
        if(!is_null($this->dbconn)) {
            $this->dbconn = new mysqli($this->ini['host'], $this->ini['user'], $this->ini['password'], $this->ini['dbname']);
            $this->dbconn->set_charset('utf8mb4');
        }
    }
    function close() {
        mysqli_close($this->dbconn);
    }
    function query($sql, $params = NULL) {
        $has_params = !(is_null($params) || (is_array($params) && count($params) == 0));
        try {
            $stmt = preg_stmt($sql);
            if($stmt === FALSE && !$has_params) {
                // query
            } else {
                if(!isset_keys($stmt, $params)) { throw new Exception(); }
                // stmt
            }
        } catch(Exception $err) {
            //
        }
    }
}

class WrapPostgreSQL extends AbDBBase {
    use ReadIniSetting;
    use MapStmt;
    private $dbconn = NULL;
    private $ini = [];
    function __construct(?string $section = NULL, bool $autoconnect = TRUE) {
        $this->ini = read_ini($section);
        if($autoconnect) {
            $this->connect();
        }
    }
    function connect() {
        if(!is_null($this->dbconn)) {
            $this->dbconn = pg_connect(sprintf("host=%s dbname=%s user=%s password=%s", $this->ini['host'], $this->ini['dbname'], $this->ini['user'], $this->ini['password']))
                or die('PostgreSQL NOT Connected. ' . pg_last_error());
        }
    }
    function close() {
        pg_close($this->dbconn);
    }
    function query($sql, $params = NULL) {
        $has_params = !(is_null($params) || (is_array($params) && count($params) == 0));
        try {
            $stmt = preg_stmt($sql);
            if($stmt === FALSE && !$has_params) {
                // query
            } else {
                // stmt
            }
        } catch(Exception $err) {
            //
        }
    }
}