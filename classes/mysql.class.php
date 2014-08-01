<?php
function db_string($String, $DisableWildcards = false) {
    global $DB;
    $String = $DB->escape_str($String);
    if ($DisableWildcards) {
        $String = str_replace(array('%','_'), array('\%','\_'), $String);
    }
    return $String;
}

class mysqldb {
    public static $Queries = 0;
    public static $Time = 0;
    public $LinkID = false;

    function connect() {
        if (!$this->LinkID) {
            $this->LinkID = mysqli_connect(MYSQL_SERVER, MYSQL_USER, MYSQL_PASS, MYSQL_DB, 3306);
            if (!$this->LinkID) {
                $Errno = mysqli_connect_errno();
                $Error = mysqli_connect_error();
                exit("Connection failed - $Errno - $Error");
            }
        }
    }
    function escape_str($Str) {
        $this->connect(0);
        return mysqli_real_escape_string($this->LinkID, $Str);
    }

    function query($Query) {
        $QueryStartTime = microtime(true);
        $this->connect();

        $Query = mysqli_query($this->LinkID, $Query);

        $QueryEndTime = microtime(true);
        self::$Time += ($QueryEndTime - $QueryStartTime) * 1000;

        if (!$Query) {
            $Error = mysqli_error($this->LinkID);
            exit("Query failed - $Error");
        }

        mysqli_close($this->LinkID);
        $this->LinkID = false;
        self::$Queries++;
        return $Query;

    }
    function store($Data) {

        $DataID = db_string($Data['id_str']);
        $Encoded = db_string(json_encode($Data));

        $Res = $this->query("SELECT tweet_id FROM tweets WHERE tweet_id = '". $DataID ."';");

        if (empty(mysqli_fetch_array($Res))) {
            $this->query("INSERT INTO tweets (tweet_id, json_data)
                     VALUES (" . $DataID . ", '$Encoded')");
            return 1;
        }
        else {
            return 0;
        }
    }
}