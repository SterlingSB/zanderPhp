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
function importCsvFileByMySql($filePath, $tableName, 
                                $rowCountToIgnore = 1) {
    $filePath = strReplaceChars('\\', '/', $filePath);
    $lineEnd = "'\\n'";
    $command = "LOAD DATA INFILE '$filePath' "
                ."INTO TABLE $tableName "
                ."FIELDS "
                    ."TERMINATED BY ',' "
                    ."OPTIONALLY ENCLOSED BY '\"' "
                ."LINES "
                    ."TERMINATED BY $lineEnd "
                ."IGNORE $rowCountToIgnore ROWS"
                ;
//echoLine($command);
    dbExecCommand($command);
}
//-----------------------------------------
function dbTypeToMySqlType($dbType) {
    if($dbType === cDbTypeCharacter100) {
        return 'VARCHAR(100)';
    }
    if($dbType === cDbTypeCharacter1000) {
        return 'VARCHAR(1000)';
    }
    if($dbType === cDbTypeCharacter10000) {
        return 'VARCHAR(10000)';
    }
    if($dbType === cDbTypeCharacter50000) {
        return 'VARCHAR(50000)';
    }
    else if($dbType === cDbTypeFloat) {
        return 'DOUBLE';
    }
    //else if($dbType === cDbTypeDate) {
      //  return 'DATE';
    //}
    else if($dbType === cDbTypeDateTime) {
        return 'DATETIME';
    }
    else {
        err("type $dbType misspelt or unsupported");
    }
}      
//--------------
function dbTypesToMySqlTypes($dbTypes) {      
    $mySqlTypes = [];
    foreach($dbTypes as $dbType) {
        $mySqlTypes[] = dbTypeToMySqlType($dbType);
    }
    return $mySqlTypes;
}
