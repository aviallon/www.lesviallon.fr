<?php
    function connect(){
        /*$conStr = sprintf("pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s",
            "localhost",
            5432,
            "blog",
            "blog",
            "Indb75a");*/
        
        try {
            $pdo = new \PDO("pgsql:host=localhost;port=5432;dbname=blog", "blog", "*****", array(
                \PDO::ATTR_PERSISTENT => true
            ));
            /*$pdo = new \PDO($conStr);*/
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e){
            die($e->getMessage());
        }
        
        return $pdo;
    }
    
    function array_db($pg_array){
        if($pg_array != ''){
            $array = explode(',', trim($pg_array, "{}"));
            //for($i = 0; $i<$array.)
            return $array;
        }
        return [];
    }
    
    function queryFilter($res){
        return array_filter($res, function($k){
            return (gettype($k) != 'integer');
        }, ARRAY_FILTER_USE_KEY);
    }
    
    // Warning : may throw if table empty
    function valid_identifier($identifier, $default, $tablename){
        $pdo = connect();
        
        $request = "SELECT * FROM $tablename LIMIT 1";
        $stmt = $pdo->prepare($request);
        $stmt->execute();
        $res = $stmt->fetchAll();
        $authorized_identifiers = array_keys(queryFilter($res[0]));
        
        $return = $default;
        if(in_array($identifier, $authorized_identifiers)){
            $return = $identifier;
        }
        
        return $return;
    }
    
    function to_pg_array($set) {

        if (is_null($set) || !is_array($set)) {
            return 'NULL';
        }

        // can be called with a scalar or array
        settype($set, 'array');

        $result = array();
        foreach ($set as $t) {
                // Element is array : recursion
            if (is_array($t)) {
                $result[] = to_pg_array($t);
            }
            else {
                // PHP NULL
                if (is_null($t)) {
                    $result[] = 'NULL';
                }
                // PHP TRUE::boolean
                elseif (is_bool($t) && $t == TRUE) {
                    $result[] = 'TRUE';
                }
                // PHP FALSE::boolean
                elseif (is_bool($t) && $t == FALSE) {
                    $result[] = 'FALSE';
                }
                // Other scalar value
                else {
                    // Escape
                    $t = pg_escape_string($t);

                    // quote only non-numeric values
                    if (!is_numeric($t)) {
                        $t = '\'' . $t . '\'';
                    }
                    $result[] = $t;
                }
            }
        }
        return '{' . implode(",", $result) . '}'; // format
    }
?>
