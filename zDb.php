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

require_once dirname(__FILE__) . '/zCore.php';
require_once dirname(__FILE__) . '/zStrings.php';
require_once dirname(__FILE__) . '/zDates.php';

$gDbConnection = null;
//-----------
define('cDbTypeBoolean', "boolean");
define('cDbTypeFloat', "float");
define('cDbTypeDateTime', "dateTime");
define('cDbTypeCharacter100', "charactor100");
define('cDbTypeCharacter300', "charactor300");
define('cDbTypeCharacter1000', "charactor1000");
define('cDbTypeCharacter3000', "charactor3000");
define('cDbTypeCharacter10000', "charactor10000");
define('cDbTypeCharacter30000', "charactor30000");
define('cDbTypeCharacter50000', "charactor50000");
define('cDbTypeCodeBoolean', -3);
define('cDbTypeCodeFloat', -2);
define('cDbTypeCodeDateTime', -1);
define('cDbTypeCodeCharacter100', 100);
define('cDbTypeCodeCharacter300', 300);
define('cDbTypeCodeCharacter1000', 1000);
define('cDbTypeCodeCharacter3000', 3000);
define('cDbTypeCodeCharacter10000', 10000);
define('cDbTypeCodeCharacter30000', 30000);
define('cDbTypeCodeCharacter50000', 50000);
//-----------
function dbTypeOfValue(mixed $value): ?string
{
    if (is_null($value)) {
        return null;
    } else if (is_bool($value)) {
        return cDbTypeBoolean;
    } else if (is_numeric($value)) {
        return cDbTypeFloat;
    } else if (is_string($value)) {
        if (isDateTimeStr($value)) {
            return cDbTypeDateTime;
        }
        $strLen = strlen($value);
        if ($strLen <= 100) {
            return cDbTypeCharacter100;
        } else if ($strLen <= 300) {
            return cDbTypeCharacter300;
        } else if ($strLen <= 1000) {
            return cDbTypeCharacter1000;
        } else if ($strLen <= 3000) {
            return cDbTypeCharacter3000;
        } else if ($strLen <= 10000) {
            return cDbTypeCharacter10000;
        } else if ($strLen <= 30000) {
            return cDbTypeCharacter30000;
        } else {
            return cDbTypeCharacter50000;
        }
    } else {
        $type = gettype($value);
        err("dbType of $type unsupported");
    }
}
//-----------
function dbTypeOfValueTest(): void
{
    test(dbTypeOfValue(null) === null, "null test");
    test(dbTypeOfValue(true) === cDbTypeBoolean, "true test");
    test(dbTypeOfValue(false) === cDbTypeBoolean, "false test");
    test(dbTypeOfValue(123) === cDbTypeFloat, "123 test");
    test(dbTypeOfValue(0) === cDbTypeFloat, "0 test");
    test(dbTypeOfValue(-123) === cDbTypeFloat, "-123 test");
    test(dbTypeOfValue("123") === cDbTypeFloat, '"123" test');
    test(dbTypeOfValue("0") === cDbTypeFloat, '"0" test');
    test(dbTypeOfValue("-123") === cDbTypeFloat, '"-123" test');
    test(dbTypeOfValue("2020-03-05") === cDbTypeDateTime, "03-5-2020 test");
    test(dbTypeOfValue("abc") === cDbTypeCharacter100, "abc test");
}
//-----------
function dbTypeToDbTypeCode(?string $dbType): ?int
{
    if (is_null($dbType)) {
        return null;
    } else if ($dbType === cDbTypeBoolean) {
        return cDbTypeCodeBoolean;
    } else if ($dbType === cDbTypeFloat) {
        return cDbTypeCodeFloat;
    } else if ($dbType === cDbTypeDateTime) {
        return cDbTypeCodeDateTime;
    } else if ($dbType === cDbTypeCharacter100) {
        return cDbTypeCodeCharacter100;
    } else if ($dbType === cDbTypeCharacter300) {
        return cDbTypeCodeCharacter300;
    } else if ($dbType === cDbTypeCharacter1000) {
        return cDbTypeCodeCharacter1000;
    } else if ($dbType === cDbTypeCharacter3000) {
        return cDbTypeCodeCharacter3000;
    } else if ($dbType === cDbTypeCharacter10000) {
        return cDbTypeCodeCharacter10000;
    } else if ($dbType === cDbTypeCharacter30000) {
        return cDbTypeCodeCharacter30000;
    } else if ($dbType === cDbTypeCharacter50000) {
        return cDbTypeCodeCharacter50000;
    } else {
        $readable = valueToReadable($dbType);
        err("$readable is of an unknown dbType");
    }
}
//-----------
function dbTypeCodeToDbType(?int $dbTypeCode): ?string
{
    if (is_null($dbTypeCode)) {
        return null;
    } else if ($dbTypeCode === cDbTypeCodeBoolean) {
        return cDbTypeBoolean;
    } else if ($dbTypeCode === cDbTypeCodeFloat) {
        return cDbTypeFloat;
    } else if ($dbTypeCode === cDbTypeCodeDateTime) {
        return cDbTypeDateTime;
    } else if ($dbTypeCode === cDbTypeCodeCharacter100) {
        return cDbTypeCharacter100;
    } else if ($dbTypeCode === cDbTypeCodeCharacter300) {
        return cDbTypeCharacter300;
    } else if ($dbTypeCode === cDbTypeCodeCharacter1000) {
        return cDbTypeCharacter1000;
    } else if ($dbTypeCode === cDbTypeCodeCharacter3000) {
        return cDbTypeCharacter3000;
    } else if ($dbTypeCode === cDbTypeCodeCharacter10000) {
        return cDbTypeCharacter10000;
    } else if ($dbTypeCode === cDbTypeCodeCharacter30000) {
        return cDbTypeCharacter30000;
    } else if ($dbTypeCode === cDbTypeCodeCharacter50000) {
        return cDbTypeCharacter50000;
    } else {
        $readable = valueToReadable($dbTypeCode);
        err("$readable is of an unknown dbTypeCode");
    }
}
//-----------
function dbTypeBestFit(?string $type1, ?string $type2): ?string
{
    if (is_null($type1)) {
        return $type2;
    } else if (is_null($type2)) {
        return $type1;
    }
    $type1Code = dbTypeToDbTypeCode($type1);
    $type2Code = dbTypeToDbTypeCode($type2);
    $resultCode = max((int) $type1Code, (int) $type2Code);
    return dbTypeCodeToDbType($resultCode);
}
//-----------
function dbTypeBestFitTest(): void
{
    test(dbTypeBestFit(null, null) === null, "null null test");
    test(dbTypeBestFit(cDbTypeBoolean, null) === cDbTypeBoolean, "boolean null test");
    test(dbTypeBestFit(null, cDbTypeBoolean) === cDbTypeBoolean, "null boolean test");
    test(dbTypeBestFit(cDbTypeBoolean, cDbTypeFloat) === cDbTypeFloat, "boolean float test");
    test(dbTypeBestFit(cDbTypeFloat, cDbTypeDateTime) === cDbTypeDateTime, "float dateTime test");
    test(dbTypeBestFit(cDbTypeDateTime, cDbTypeCharacter100) === cDbTypeCharacter100, "dateTime char100 test");
    test(dbTypeBestFit(cDbTypeCharacter100, cDbTypeCharacter300) === cDbTypeCharacter300, "char100 char300 test");
    test(dbTypeBestFit(cDbTypeCharacter300, cDbTypeCharacter1000) === cDbTypeCharacter1000, "char300 char1000 test");
    test(dbTypeBestFit(cDbTypeCharacter1000, cDbTypeCharacter3000) === cDbTypeCharacter3000, "char1000 char3000 test");
    test(dbTypeBestFit(cDbTypeCharacter3000, cDbTypeCharacter10000) === cDbTypeCharacter10000, "char3000 char10000 test");
    test(dbTypeBestFit(cDbTypeCharacter10000, cDbTypeCharacter30000) === cDbTypeCharacter30000, "char10000 char30000 test");
    test(dbTypeBestFit(cDbTypeCharacter30000, cDbTypeCharacter50000) === cDbTypeCharacter50000, "char30000 char50000 test");
}
//-----------
function tableColumnDbType(array $table, string $columnName): string
{
    $bestFitSoFar = null;
    $i = 0;
    foreach ($table as $row) {
        $dbType = dbTypeOfValue($row[$columnName]);
        $bestFitSoFar = dbTypeBestFit($dbType, $bestFitSoFar);
        $i++;
        if ($i > 10) {
            break;
        }
    }
    if ($bestFitSoFar == null) {
        $bestFitSoFar = cDbTypeCharacter100; //default to cDbTypeCharacter100 if null
    }
    return $bestFitSoFar;
}
//-----------------------------------------
function tableColumnInfo(array $table): array
{
    errIfNull($table, 'null array invalid');
    errIfArrayIsEmpty($table);
    $types = [];
    $columnNames = tableColumnNames($table);
    foreach ($columnNames as $columnName) {
        $types[$columnName] = tableColumnDbType($table, $columnName);
    }
    return $types;
}
//-----------
//'mysql:host=localhost;port=3306', root, cloudthewire
//'pgsql:host=localhost;dbname=house', 'postgres', 'cloudthewire38!'
function dbInit(string $connectionString, string $user, string $password): void
{
    global $gDbConnection;
    $gDbConnection = new PDO($connectionString, $user, $password);
    $db = dbGetDbConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    //dbExecCommand("SET CHARACTER SET 'utf8'");
}
//-----------
function dbGetDbConnection(): PDO
{
    global $gDbConnection;
    errIfNull($gDbConnection, 'Connection not created. Call dbInit');
    return $gDbConnection;
}
//-----------
function dbExecCommand(string $command, bool $returnExceptionsAsString = false): int|string
{
    $db = dbGetDbConnection();
    try {
        $result = $db->exec($command);
        errIf($result === false, "Failed to run the db command");
    } catch (Exception $ex) {
        $newMessage = $ex->getMessage() . "\ncode=" . $ex->getCode() . "\nFailed to run the db command\n";
        if ($returnExceptionsAsString) {
            return $newMessage;
        } else {
            $newEx = new Exception($newMessage, (int) $ex->getCode());
            throw $newEx;
        }
    }

    return $result;
}
//-----------
function dbSelectStatement(string $sql, array $params = []): PDOStatement
{
    checkSqlIsSelect($sql);
    $db = dbGetDbConnection();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}
//-----------
function dbSelect(string $sql, array $params = []): array
{
    $stmt = dbSelectStatement($sql, $params);
    $selectData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    errIf($selectData === false, 'Query failed');
    return $selectData;
}
//-----------
function dbSelectColumn(string $sql, array $params = []): array
{
    $stmt = dbSelectStatement($sql, $params);
    $selectData = $stmt->fetchAll(PDO::FETCH_NUM);
    errIf($selectData === false, 'Query failed');
    $col = [];
    foreach ($selectData as $row) {
        $col[] = $row[0];
    }
    return $col;
}
//-----------
function dbSelectRow(string $sql, array $params = []): ?array
{
    $stmt = dbSelectStatement($sql, $params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return null;
    }
    return $row;
}
//-----------
function dbSelectValue(string $sql, array $params = []): mixed
{
    $stmt = dbSelectStatement($sql, $params);
    $row = $stmt->fetch(PDO::FETCH_NUM);
    if ($row === false) {
        return null;
    } else {
        $value = $row[0];
        return $value;
    }
}
//------------------
//this function is used in inserts and updates. numbers are converted to strings I am not sure why
//------------------
function dbValueToSqlImportValue(mixed $value): string
{
    if (is_bool($value) || is_null($value)) {
        return jsonEncodeBetter($value);
    } else if (is_numeric($value)) {
        return "'" . $value . "'";
    } else if (is_string($value)) {
        $sqlStr = str_replace("'", "''", $value);
        return "'" . $sqlStr . "'";
    } else {
        err('type ' . gettype($value) . ' not supported');
    }
}
//------------------
function dbValueToSqlImportValueTest(): void
{
    test(dbValueToSqlImportValue(null) === 'null', 'null test');
    test(dbValueToSqlImportValue(true) === 'true', 'true test');
    test(dbValueToSqlImportValue(123) === "'123'", 'int test');
    test(dbValueToSqlImportValue(12.3) === "'12.3'", 'float test');
    test(dbValueToSqlImportValue("O'Reilly") === "'O''Reilly'", 'string escape test');
}
//------------------
//not sure if this is better than dbValueToSqlImportValue
//------------------
function dbValueToSqlValue(mixed $value): string
{
    if (is_string($value)) {
        $sqlStr = str_replace("'", "''", $value);
        return "'" . $sqlStr . "'";
    } else if (is_null($value) || is_bool($value) || is_numeric($value)) {
        return jsonEncodeBetter($value);
    } else {
        err('type ' . gettype($value) . ' not supported');
    }
}
//------------------
function dbValueToSqlValueTest(): void
{
    test(dbValueToSqlValue(null) === 'null', 'null test');
    test(dbValueToSqlValue(true) === 'true', 'true test');
    test(dbValueToSqlValue(123) === '123', 'int test');
    test(dbValueToSqlValue("O'Reilly") === "'O''Reilly'", 'string escape test');
}
//------------------
function dbRowToSqlInsert(array $row, string $tableName): string
{
    $fieldNames = '';
    foreach ($row as $key => $value) {
        $fieldNames = $fieldNames . $key . ',';
    }
    $fieldNames = strDeleteSuffix(',', $fieldNames);
    $fieldNames = "($fieldNames)";

    $insertValues = '';
    foreach ($row as $value) {
        $insertValue = dbValueToSqlImportValue($value);
        $insertValues = $insertValues . $insertValue . ",";
    }
    $insertValues = strDeleteSuffix(',', $insertValues);
    $insertValues = "($insertValues)";

    $insertStatement = "insert into $tableName $fieldNames values $insertValues";
    return $insertStatement;
}
//------------------
function dbInsertRow(array $row, string $tableName): int|string
{
    $insertStatement = dbRowToSqlInsert($row, $tableName);
    return dbExecCommand($insertStatement);
}
//------------------
function dbInsertTable(array $table, string $tableName): void
{
    foreach ($table as $row) {
        dbInsertRow($row, $tableName);
    }
}
//------------------
function dbInsertCsvLines(string $csvLines, string $tableName): void
{
    $table = csvLinesToTable($csvLines);
    dbInsertTable($table, $tableName);
}
//------------------
function dbRowColumnsToFilter(array $row, array $columnNames): string
{
    $filter = '';
    foreach ($columnNames as $colName) {
        $sqlValue = dbValueToSqlValue($row[$colName]);
        $filter = $filter . "$colName = $sqlValue and ";
    }
    $filter = strDeleteSuffix('and ', $filter);
    $filter = "($filter)";
    return $filter;
}
//------------------
function dbRowPkeyToFilter(array $row, ?array $pkeyColumnNames = null): string
{
    if ($pkeyColumnNames == null) {
        $pkeyColumnNames = arrayKeys($row);
    }
    return dbRowColumnsToFilter($row, $pkeyColumnNames);
}
//------------------
function dbRowToSqlUpdate(array $row, string $tableName, ?array $pkeyColumnNames = null): string
{
    $pkeyFilter = dbRowPkeyToFilter($row, $pkeyColumnNames);

    $newValues = '';
    $actualPkeys = $pkeyColumnNames ?? arrayKeys($row);

    foreach ($row as $key => $value) {
        if (in_array($key, $actualPkeys, true)) {
            continue;
        }
        $sqlValue = dbValueToSqlImportValue($value);
        $newValues = $newValues . "$key = $sqlValue,\n";
    }

    if ($newValues === '') {
        err("No columns left to update for table '$tableName'. Ensure row has data outside of pkey columns.");
    }

    $newValues = strDeleteSuffix(",\n", $newValues);

    $updateDml = "update $tableName set $newValues\nwhere\n$pkeyFilter";
    return $updateDml;
}
//------------------
function dbRowToSqlUpdateTest(): void
{
    $row = ['id' => 1, 'name' => "O'Reilly", 'age' => 30];
    $sql = dbRowToSqlUpdate($row, 'users', ['id']);
    test(str_contains($sql, "set name = 'O''Reilly'"), 'set clause name test');
    test(str_contains($sql, "age = '30'"), 'set clause age test');
    test(!str_contains($sql, "set id = 1"), 'pkey excluded from set clause test');
    test(str_contains($sql, "where\n(id = 1 )"), 'where clause pkey test');
}
//------------------
function dbUpdateRow(array $row, string $tableName, ?array $pkeyColumnNames = null): int|string
{
    $updateStatement = dbRowToSqlUpdate($row, $tableName, $pkeyColumnNames);
    return dbExecCommand($updateStatement);
}
//------------------
function dbUpdateTable(array $table, string $tableName, ?array $pkeyColumnNames = null): void
{
    foreach ($table as $row) {
        dbUpdateRow($row, $tableName, $pkeyColumnNames);
    }
}
//-----------------------------------------
function dbPkeyExists(array $row, string $tableName, ?array $pkey = null): bool
{
    $pkeyFilter = dbRowPkeyToFilter($row, $pkey);
    $selectPkey = "select * from $tableName where $pkeyFilter";
    $rowResult = dbSelectRow($selectPkey);
    if ($rowResult === null) {
        return false;
    }
    return true;
}
//-----------------------------------------
function dbSubmitRow(array $row, string $tableName, ?array $pkeyColumnNames = null): array
{
    $result = [];
    if (dbPkeyExists($row, $tableName, $pkeyColumnNames)) {
        $count = dbUpdateRow($row, $tableName, $pkeyColumnNames);
        $result['updateCount'] = $count;
    } else {
        $count = dbInsertRow($row, $tableName);
        $result['insertCount'] = $count;
    }

    return $result;
}
//-----------------------------------------
function dbSubmitTable(array $table, string $tableName, ?array $pkeyColumnNames = null): array
{
    $updateCount = 0;
    $insertCount = 0;
    foreach ($table as $row) {
        $rowResult = dbSubmitRow($row, $tableName, $pkeyColumnNames);
        if (arrayKeyExists($rowResult, 'updateCount')) {
            $updateCount = (int) $updateCount + (int) $rowResult['updateCount'];
        } else {
            $insertCount = (int) $insertCount + (int) $rowResult['insertCount'];
        }
    }

    $result = [];
    $result['updateCount'] = $updateCount;
    $result['insertCount'] = $insertCount;

    return $result;
}
//-----------------------------------------
function dbDropTable(string $tableName): void
{
    $dropTableCommand = "drop table $tableName";
    dbExecCommand($dropTableCommand);
}
//-----------------------------------------
function dbDeleteAllRowsInTable(string $tableName): void
{
    $deleteAllRowsCommand = "delete from $tableName";
    dbExecCommand($deleteAllRowsCommand);
}
//------------------
function exceptionToDbReadable(Throwable $exception): string
{
    $errMessage = $exception->getMessage();
    if ($exception instanceof PDOException) {
        $parts = explode(':', $errMessage);
        $errMessage = $parts[1] ?? $errMessage;
    }
    return exceptionToReadable($exception, $errMessage);
}
//------------------
function httpErrCustom(Throwable $exception): void
{
    http_response_code(400);

    $errMessage = 'ERROR! ' . exceptionToDbReadable($exception);
    echoLine($errMessage);

    $errMessage = exceptionAndBacktraceToReadable($exception);
    fileLog($errMessage);
}
//------------------
function zDbTest(): void
{
    echoLineBlockStart("zDbTest");
    dbTypeOfValueTest();
    dbTypeBestFitTest();
    dbValueToSqlValueTest();
    dbValueToSqlImportValueTest();
    dbRowToSqlUpdateTest();
    echoLineBlockFinish("zDbTest");
}

set_exception_handler('httpErrCustom');
/*
http://php.net/manual/en/book.pdo.php
PDO — The PDO class
PDO::beginTransaction — Initiates a transaction
PDO::commit — Commits a transaction
PDO::__construct — Creates a PDO instance representing a connection to a database
PDO::errorCode — Fetch the SQLSTATE associated with the last operation on the database handle
PDO::errorInfo — Fetch extended error information associated with the last operation on the database handle
PDO::exec — Execute an SQL statement and return the number of affected rows
PDO::getAttribute — Retrieve a database connection attribute
PDO::getAvailableDrivers — Return an array of available PDO drivers
PDO::inTransaction — Checks if inside a transaction
PDO::lastInsertId — Returns the ID of the last inserted row or sequence value
PDO::prepare — Prepares a statement for execution and returns a statement object
PDO::query — Executes an SQL statement, returning a result set as a PDOStatement object
PDO::quote — Quotes a string for use in a query
PDO::rollBack — Rolls back a transaction
PDO::setAttribute — Set an attribute
PDOStatement — The PDOStatement class
PDOStatement::bindColumn — Bind a column to a PHP variable
PDOStatement::bindParam — Binds a parameter to the specified variable name
PDOStatement::bindValue — Binds a value to a parameter
PDOStatement::closeCursor — Closes the cursor, enabling the statement to be executed again
PDOStatement::columnCount — Returns the number of columns in the result set
PDOStatement::debugDumpParams — Dump an SQL prepared command
PDOStatement::errorCode — Fetch the SQLSTATE associated with the last operation on the statement handle
PDOStatement::errorInfo — Fetch extended error information associated with the last operation on the statement handle
PDOStatement::execute — Executes a prepared statement
PDOStatement::fetch — Fetches the next row from a result set
PDOStatement::fetchAll — Returns an array containing all of the result set rows
PDOStatement::fetchColumn — Returns a single column from the next row of a result set
PDOStatement::fetchObject — Fetches the next row and returns it as an object
PDOStatement::getAttribute — Retrieve a statement attribute
PDOStatement::getColumnMeta — Returns metadata for a column in a result set
PDOStatement::nextRowset — Advances to the next rowset in a multi-rowset statement handle
PDOStatement::rowCount — Returns the number of rows affected by the last SQL statement
PDOStatement::setAttribute — Set a statement attribute
PDOStatement::setFetchMode — Set the default fetch mode for this statement
PDOException — The PDOException class

http://php.net/manual/en/ref.pdo-pgsql.php
PDO_PGSQL DSN — Connecting to PostgreSQL databases
PDO::pgsqlCopyFromArray — Copy data from PHP array into table
PDO::pgsqlCopyFromFile — Copy data from file into table
PDO::pgsqlCopyToArray — Copy data from database table into PHP array
PDO::pgsqlCopyToFile — Copy data from table into file
PDO::pgsqlGetNotify — Get asynchronous notification
PDO::pgsqlGetPid — Get the server PID
PDO::pgsqlLOBCreate — Creates a new large object
PDO::pgsqlLOBOpen — Opens an existing large object stream
PDO::pgsqlLOBUnlink — Deletes the large object 
*/
