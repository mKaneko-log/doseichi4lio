<?php

abstract class AbDBBase {
    abstract public function connect();
    abstract public function close();
    abstract public function query($sql, $params = NULL);
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
            unset($tmp);
            return $match;
        }
        return FALSE;
    }
    /**
     * プリペアドキーがパラメータに"すべて"存在するか
     * 
     * @param array $stmt_keys プリペアドキー
     * @param ?array $params パラメータ？
     */
    function isset_keys($stmt_keys, $params) {
        $result = [];
        /** @var iterable|string $key */
        foreach($key as $stmt_keys) { $result[] = array_key_exists($key, $params); }
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
        $this->ini = $this->read_ini($section);
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

    private function replaceSqlParam(string &$sql, array $stmt, array $params) {
        // [0: 'sid', 1: 'hoge', 2: 10, 3: 3.14] でshift()して「...$val」…みたいな？
        $result = [];
        $result[] = '';
        $sql = preg_replace("/:[-\w]+/", '?', $sql);
        /** @var iterable|string $key */
        foreach($key as $stmt) {
            $temp = $params[$key];
            if(is_int($temp)) {
                // 整数
                $result[0] .= 'i';
            } elseif(is_float($temp)) {
                // 小数点あり
                $result[0] .= 'd';
            } else {
                // 文字列
                $result[0] .= 's';
            }
            $result[] = $temp;
        }
        unset($key);
        return $result;
    }
    function query($sql, $params = NULL) {
        $has_params = !(is_null($params) || (is_array($params) && count($params) == 0));
        $result = [];
        try {
            $stmt = $this->preg_stmt($sql);
            if($stmt === FALSE && !$has_params) {
                // query
                try {
                    $q_result = $this->dbconn->query($sql);
                    if(is_bool(($q_result))) {
                        $result = $q_result? []: NULL;
                    } else {
                        while($row = $q_result->fetch_assoc()) { $result[] = $row; }
                        unset($row);
                    }
                } catch(mysqli_sql_exception $q_err) {
                    var_dump($q_err->getCode());
                    var_dump($q_err->getMessage());
                    throw $q_err;
                } finally {
                    $q_result->free();
                }
            } else {
                if(!$this->isset_keys($stmt, $params)) { throw new ArgumentCountError("パラメータ数がプリペアドステートメントと一致しません。"); }
                // stmt
                try {
                    $prepared = $this->replaceSqlParam($sql, $stmt, $params);
                    $v_types = array_shift($prepared);
                    $q_stmt = new mysqli_stmt($this->dbconn, $sql);
                    if(count($prepared) > 1) {
                        // 複数
                        $q_stmt->bind_param($v_types, ...$prepared);
                    } else {
                        // 単一
                        $q_stmt->bind_param($v_types, $prepared);
                    }
                    $q_stmt->execute();
                    $q_result = $q_stmt->get_result();
                    if(!is_bool($q_result)) {
                        // SELECT
                        while($row = $q_result->fetch_assoc()) { $result[] = $row; }
                        unset($row);
                    } elseif($q_stmt->affected_rows > -1 && $this->dbconn->errno == 0) {
                        // その他 (なにもしない)
                        $result = [];
                    } else {
                        // エラー
                        throw new mysqli_sql_exception("SQL実行（プリペアド）で何らかのエラー", 999);
                    }
                } catch(mysqli_sql_exception $s_err) {
                    var_dump($s_err->getCode());
                    var_dump($s_err->getMessage());
                    throw $s_err;
                } finally {
                    if(isset($q_result)) { $q_result->free(); }
                    $q_stmt->close();
                }
            }

            return $result;

        } catch(Exception $err) {
            var_dump("= - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - =");
            var_dump($err->getCode());
            var_dump($err->getMessage());
            throw $err;
        }
    }
}


class WrapPostgreSQL extends AbDBBase {
    use ReadIniSetting;
    use MapStmt;
    private $dbconn = NULL;
    private $ini = [];
    function __construct(?string $section = NULL, bool $autoconnect = TRUE) {
        $this->ini = $this->read_ini($section);
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
        $result = [];
        try {
            $stmt = $this->preg_stmt($sql);
            if($stmt === FALSE && !$has_params) {
                // query
                try {
                    $q_result = pg_query($this->dbconn, $sql);
                    if(!$q_result) { throw new Exception("SQL実行に失敗: ". pg_last_error()); }
                    $q_status = pg_result_status(($q_result));
                    if($q_status == PGSQL_BAD_RESPONSE || $q_status == PGSQL_NONFATAL_ERROR || $q_status == PGSQL_FATAL_ERROR) {
                        throw new Exception("SQL実行中にエラー発生: ". pg_last_error());
                    }
                    if($q_status == PGSQL_TUPLES_OK) {
                        /** @var array $row */
                        while($row = pg_fetch_assoc($q_result)) { $result[] = $row; }
                    }
                } catch(Exception $q_err) {
                    var_dump($q_err->getCode());
                    var_dump($q_err->getMessage());
                    throw $q_err;
                } finally {
                    if(isset($q_result)) { pg_free_result($q_result); }
                }
            } else {
                if(!$this->isset_keys($stmt, $params)) { throw new ArgumentCountError("パラメータ数がプリペアドステートメントと一致しません。"); }
                // stmt
                $s_params = [];
                $pattern = '/:([-\w]+)/';
                $count = 0;
                $s_func = function(&$sql, $pattern, $params, &$output, &$i) {
                    $flg = preg_match($pattern, $sql, $matches);
                    if($flg == 0) { return FALSE; }
                    $i++;
                    $output[] = $params[$matches[1]];
                    $sql = preg_replace($pattern, '*_*' . $i, $sql, 1);
                    return TRUE;
                };
                while($s_func($sql, $pattern, $params, $s_params, $count)) {
                    $sql = str_replace('*_*', '$', $sql);
                }
            }

            return $result;

        } catch(Exception $err) {
            var_dump("= - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - = - =");
            var_dump($err->getCode());
            var_dump($err->getMessage());
            throw $err;
        }
    }
}