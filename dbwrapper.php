<?php

abstract class AbDBBase {
    abstract public function connect();
    abstract public function close();
    abstract public function query($sql);
}
trait ReadIniSetting {
    function read_ini(?string $section = null) {
        $filepath = 'path/to/inifile.ini';
        $result = [];
        if(is_null($section)) {
            $result = parse_ini_file($filepath);
        } else {
            $result = parse_ini_file($filepath, true);
            $result = $result[$section];
        }
        return $result;
    }
}

class WrapMySQL extends AbDBBase {
    use ReadIniSetting;
    private $dbconn = null;
    private $ini = [];
    function __construct(?string $section = null, bool $autoconnect = true) {
        $this->ini = read_ini($section);
        if($autoconnect) {
            $this->connect();
        }
    }
    function connect() {
        if(!is_null($this->dbconn)) {
            $this->dbconn = mysqli_connect($this->ini['host'], $this->ini['user'], $this->ini['pass'], $this->ini['dbname']);
            if(mysqli_connect_errno()) {
                die('MySQL NOT Connected. ' . mysqli_connect_error);
            }
        }
    }
    function close() {
        mysqli_close($this->dbconn);
    }
    function query($sql) {}
}

class WrapPostgreSQL extends AbDBBase {
    use ReadIniSetting;
    private $dbconn = null;
    private $ini = [];
    function __construct(?string $section = null, bool $autoconnect = true) {
        $this->ini = read_ini($section);
        if($autoconnect) {
            $this->connect();
        }
    }
    function connect() {
        if(!is_null($this->dbconn)) {
            $this->dbconn = pg_connect(sprintf("host=%s dbname=%s user=%s password=%s", $this->ini['host'], $this->ini['dbname'], $this->ini['user'], $this->ini['pass']))
                or die('PostgreSQL NOT Connected. ' . pg_last_error());
        }
    }
    function close() {
        pg_close($this->dbconn);
    }
    function query($sql) {}
}