<?php
/*
Copyright 2019-2026 Sterling Butts

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*/

require_once dirname(__FILE__).'/zCore.php';
require_once dirname(__FILE__).'/zDb.php';
//-----------------------------------------
function dbTypeToPostgresType($dbType) {
    if(strHasPrefix('charactor', $dbType)) {
        return 'character varying';
    }
    else if($dbType === cDbTypeFloat) {
        return 'double precision';
    }
    else if($dbType === cDbTypeBoolean) {
        return 'boolean';
    }
    //else if($dbType === cDbTypeDate) {
        //return 'date';
    //}
    else if($dbType === cDbTypeDateTime) {
        return 'timestamp';
    }
    else {
        err("type $dbType misspelt or unsupported");
    }
}      
//--------------
function dbTypesToPostgresTypes($dbTypes) {      
    $pgTypes = [];
    foreach($dbTypes as $key=>$dbType) {
        errIfNull($dbType, "Type null for key $key");
        //echoLine($key);
        $pgTypes[$key] = dbTypeToPostgresType($dbType);
    }

    return $pgTypes;
}
//--------------
function dbCreateTablePostgres(array $columnInfo, string $tableName) {
    //echoDebug($columnInfo);
    $postgresTypes = dbTypesToPostgresTypes($columnInfo);
    //echoDebug($postgresTypes);
    $ddlColInfo = '';
    foreach($postgresTypes as $columnName=>$pgType) {
        $ddlColInfo = $ddlColInfo.'"'.trim($columnName).'" '.$pgType.',';
    }

    $columns = strDeleteSuffix(',', $ddlColInfo);
    $ddl = "create table $tableName($columns)";
    //echoLine($ddl);
    dbExecCommand($ddl);
}
//--------------
function dbCreateTableFromTablePostgres(array $table, string $tableName) {
    $columnInfo = tableColumnInfo($table);
    dbCreateTablePostgres($columnInfo, $tableName);
}
//-----------------------------------------
//C:\\Program Files\\PostgreSQL\\12\\bin\\psql.exe" --command " "\\copy public.time_series_covid19_confirmed_global_narrow_import (province_state, country_region, lat, \"long\", date, value) FROM 'C:/origin/QUIZZI~1/covid19/TIME_S~1.CSV' CSV QUOTE '\"' ESCAPE '''';""
function importCsvByPostgres($filePath, $tableName) {
    $fileText = fileRead($filePath);
    $fileText = trim($fileText);
    fileWrite($fileText, $filePath);
    $db = dbGetDbConnection();
    //$command = "COPY $tableName FROM '$filePath' DELIMITER ',' CSV HEADER ";//encoding 'windows-1251'";
    $command = "COPY $tableName FROM '$filePath' DELIMITER ',' CSV HEADER encoding 'windows-1251'";
//echoDebug($command);
    //exec('C:\\Program Files\\PostgreSQL\\12\\bin\\psql.exe --command  "\\copy public.time_series_covid19_confirmed_global_narrow_import (province_state, country_region, lat, \"long\", date, value) FROM 'C:/origin/QUIZZI~1/covid19/TIME_S~1.CSV' CSV QUOTE '\"' ESCAPE '''');
    $result = $db->exec($command);
    if($result === false) {
        $errInfo = $db->errorInfo();
        $errMessage = exceptionToDbReadable($errInfo);
        err("$command FAILED! $errMessage"); 
    } 
    return $result;
}
