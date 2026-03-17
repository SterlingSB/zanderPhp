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

//code dealing with 
//get and post inputs
//caching 
//encoding and formats like JSON 
//should normally be dealt with right at the point a php script is called by convention
//
//naming conventions
//table = 2d array of data. First dimension is always and integer index, second dimension has keys with string names
//handle = take care of this issue. Check all conditions and dependencies
//get and set = tend to be fairly direct ways of storing and retrieving data
//fetch and submit = also store and retrieve data but tend to be more complex and 
//  abstract. They have more processing, validation and calculations involved 
//
//Date function standards. 
//All dates or times that are not unix have a $timeZone param or 
//the function name with have "local" in the name to specify local time
//The $timeZone param will not have a default value. It must be provided

//the z library has strict_types=1 as a policy. Various test cases have been erased from
//the test functions while relying on strict_types=1 to test for errors. 
//Many tests for nulls in particular can be erased since strict_types=1 checks for this 

declare(strict_types=1);
error_reporting(E_ALL);
//-------------
class ENullInvalid extends Exception
{

}
class EEmptyStrInvalid extends Exception
{

}
class EEmptyArrayInvalid extends Exception
{

}
class EKeyNotFound extends Exception
{

}
class EKeyAlreadyExists extends Exception
{

}
class EFileNotFound extends Exception
{

}
class EExceptionNotThrown extends Exception
{

}
//-------------
define('cException', 'Exception');
define('cENullInvalid', 'ENullInvalid');
define('cEEmptyStrInvalid', 'EEmptyStrInvalid');
define('cEEmptyArrayInvalid', 'EEmptyArrayInvalid');
define('cEKeyNotFound', 'EKeyNotFound');
define('cEKeyAlreadyExists', 'EKeyAlreadyExists');
define('cEFileNotFound', 'EFileNotFound');
define('cEExceptionNotThrown', 'EExceptionNotThrown');
define('cEOutOfRangeException', 'OutOfRangeException');
//-------------
define('cNoDefault', 'cNoDefault');
//-------------
define('cNumMegabyte', 1000 * 1000);
define('cNumMebibyte', 1024 * 1024);
define('cNumGigabyte', 1000 * 1000 * 1000);
define('cNumGibibyte', 1024 * 1024 * 1024);
define('cNumMaxFloat', PHP_FLOAT_MAX);
//-------------
define('cTimeMilliSecsPerSec', 1000);
define('cTimeSecsPerMin', 60);
define('cTimeMinsPerHour', 60);
define('cTimeHoursPerDay', 24);
define('cTimeDaysPerWeek', 7);
define('cTimeDaysPerYear', 365);
define('cTimeSecsPerHour', cTimeSecsPerMin * cTimeMinsPerHour);
define('cTimeSecsPerDay', cTimeSecsPerHour * cTimeHoursPerDay);
define('cTimeSecsPerWeek', cTimeSecsPerDay * cTimeDaysPerWeek);
define('cTimeSecsPerYear', cTimeSecsPerDay * cTimeDaysPerYear);
define('cTimeMinsPerDay', cTimeMinsPerHour * cTimeHoursPerDay);
define('cTimeDaysPerMin', 1 / cTimeMinsPerDay);
define('cTimeZoneUtc', 'UTC');
define('cTimeZoneEastern', 'America/New_York');
define('cTimeZoneCentral', 'America/Chicago');
define('cTimeZoneMountain', 'America/Denver');
define('cTimeZonePacific', 'America/Los_Angeles');
define('cTimeZoneNewYork', 'America/New_York');
define('cYearStrFormat', 'Y');
define('cDateStrFormat', 'Y-m-d');
define('cTimeStrFormat', 'H:i:s');
define('cDateTimeStrFormat', 'Y-m-d H:i:s');
//define('cTimeStartOfDayTimeStr', '00:01');

//-------------
function err(string $message, string $exceptionClassName = cException): never
{
    throw new $exceptionClassName($message);
}//no function test
//-------------
function errIf(bool $condition, string $message, string $exceptionClassName = cException): void
{//
    if ($condition === true) {
        err($message, $exceptionClassName);
    }
}//no function test
//-------------
function check(bool $condition, string $message, string $exceptionClassName = cException): void
{//
    if ($condition === false) {
        err($message, $exceptionClassName);
    }
}//no function test
//-------------
function backtraceEntry(int $levelsBack = 0): array
{//
    errIf($levelsBack < 0, 'levelsBack may not be negative');

    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $offsetIndex = $levelsBack + 1;//add 1 otherwise we count "backtraceEntry" itself    
    $maxIndex = count($backtrace) - 1;
    errIf($offsetIndex > $maxIndex + 1, 'no backtrace info');
    if ($offsetIndex === $maxIndex + 1) {
        $entry = $backtrace[$maxIndex];
        $entry['function'] = $backtrace[$maxIndex]['file'];
        return $entry;
    } else {
        return $backtrace[$offsetIndex];
    }
}
//-------------
function backtraceEntryTest(): void
{
    backtraceEntry();
    testPassed('basic test');
}
//-------------
function backtraceCallerFunctionName(): string
{
    $backtraceEntry = backtraceEntry(2);
    return (string) arrayGet($backtraceEntry, 'function');
}
//-------------
function backtraceCallerFunctionNameTest(): void
{
    testEchoOutput(backtraceCallerFunctionName());
}
//-------------
function backtraceCallerLineNumber(): int
{//
    $backtraceEntry = backtraceEntry(1);
    return arrayGet($backtraceEntry, 'line');
}
//-------------
function backtraceCallerLineNumberTest(): void
{
    testEchoOutput(backtraceCallerLineNumber());
}
//-------------
function backtraceCallerFilePath(): string
{//
    $backtraceEntry = backtraceEntry(1);
    return arrayGet($backtraceEntry, 'file');
}
//-------------
function backtraceCallerFilePathTest(): void
{
    testEchoOutput(backtraceCallerFilePath());
}
//-------------
function strHasPrefix(string $prefix, string $str): bool
{
    return str_starts_with($str, $prefix);
}
//-------------
function strHasPrefixTest(): void
{
    test(strHasPrefix('test', 'testing') === true, 'prefix there test');
    test(strHasPrefix('test', 'ing') === false, 'prefix not there test');
    test(strHasPrefix('', 'testing') === true, 'empty prefix test');//there was a reason dad can't remember. might trip something up if not.
    test(strHasPrefix('test', '') === false, 'empty string test');
}
//-------------
function strEnsurePrefix(string $prefix, string $str): string
{//
    if (strHasPrefix($prefix, $str)) {
        return $str;
    } else {
        return $prefix . $str;
    }
}
//-------------
function strEnsurePrefixTest(): void
{
    test(strEnsurePrefix('test', 'ing') === 'testing', 'basic test');
    test(strEnsurePrefix('test', 'testing') === 'testing', 'prefix there test');
    test(strEnsurePrefix('test', '') === 'test', 'empty string test');
    test(strEnsurePrefix('', 'testing') === 'testing', 'empty prefix test');//empty prefix is considered true
}
//-------------
function strDeleteLeft(int $count, string $str): string
{//
    $rightLength = strlen($str) - $count;
    if ($rightLength < 0) {
        $rightLength = 0;
    }
    return strRight($rightLength, $str);
}
//-------------
function strDeleteLeftTest(): void
{//
    test(strDeleteLeft(4, 'testing') === 'ing', 'basic test');
    test(strDeleteLeft(9, 'testing') === '', 'larger than string test');
    test(strDeleteLeft(0, 'testing') === 'testing', 'zero test');
    test(strDeleteLeft(-3, 'testing') === 'testing', 'less than zero test');
    test(strDeleteLeft(5, '') === '', 'empty string test');
}
//-------------
function strDeleteRight(int $count, string $str): string
{
    $leftLength = strlen($str) - $count;
    if ($leftLength < 0) {
        $leftLength = 0;
    }
    return strLeft($leftLength, $str);
}
//-------------
function strDeleteRightTest(): void
{//
    test(strDeleteRight(3, 'testing') === 'test', 'basic test');
    test(strDeleteRight(9, 'testing') === '', 'larger than string test');
    test(strDeleteRight(0, 'testing') === 'testing', 'zero test');
    test(strDeleteRight(-3, 'testing') === 'testing', 'less than zero test');
    test(strDeleteRight(5, '') === '', 'empty string test');
}
//-------------
function strDeleteSuffix(string $suffix, string $str): string
{
    if (strHasSuffix($suffix, $str)) {
        $suffixLength = strlen($suffix);
        $str = strDeleteRight($suffixLength, $str);
    }
    return $str;
}
//------------------
function strDeleteSuffixTest(): void
{//
    test(strDeleteSuffix('ing', 'testing') === 'test', 'basic test');
    test(strDeleteSuffix('ing', 'test') === 'test', 'suffix not there test');
    test(strDeleteSuffix('ing', '') === '', 'empty string test');
    test(strDeleteSuffix('', 'testing') === 'testing', 'empty suffix test');
}
//-------------
function test(bool $passed, string $testName): void
{
    $functionName = backtraceCallerFunctionName();
    $functionName = strDeleteSuffix('Test', $functionName);
    $testName = "$functionName $testName";
    if ($passed) {
        echoLine("passed. $testName");
    } else {
        $backtraceCallerLineNumber = backtraceCallerLineNumber();
        echoLine("FAILED! $testName at line $backtraceCallerLineNumber");
    }
}//no function test
//-------------
function testPassed(string $testName): void
{
    $functionName = backtraceCallerFunctionName();
    $functionName = strDeleteSuffix('Test', $functionName);
    $testName = "$functionName $testName";
    echoLine("passed. $testName");
}//no function test
//-------------
function errExceptionNotThrown(): void
{
    err('Exception failed to throw. ', cEExceptionNotThrown);
}//no function test
//-------------
function testExceptionThrown(Throwable $ex, string $testName = "Exception thrown text"): void
{
    $functionName = backtraceCallerFunctionName();
    $functionName = strDeleteSuffix('Test', $functionName);
    if (get_class($ex) === cEExceptionNotThrown) {
        $backtraceCallerLineNumber = backtraceCallerLineNumber();
        $testOutput = "$functionName throw '$testName' test at line $backtraceCallerLineNumber";
        echoLine("FAILED! $testOutput");
    } else {
        $testOutput = "$functionName throw '$testName' '" . $ex->getMessage() . "' test";
        echoLine("passed. $testOutput");
    }
}//no function test
//-------------
function testCorrectExceptionThrown(Throwable $ex, string $exceptionItShouldBe): void
{
    $functionName = backtraceCallerFunctionName();
    $functionName = strDeleteSuffix('Test', $functionName);
    $exceptionName = get_class($ex);
    if ($exceptionName === $exceptionItShouldBe) {
        $testName = "$functionName throw $exceptionItShouldBe:'" .
            $ex->getMessage() . "' test";
        echoLine("passed. $testName");
    } else {
        $backtraceCallerLineNumber = backtraceCallerLineNumber();
        $testName = "$functionName throw $exceptionItShouldBe test. Threw $exceptionName:'" .
            $ex->getMessage() . "' instead at line $backtraceCallerLineNumber";
        echoLine("FAILED! $testName");
    }
}//no function test
//-------------
function testEchoOutput(mixed $output): void
{
    $functionName = backtraceCallerFunctionName();
    $functionName = strDeleteSuffix('Test', $functionName);
    echoLine("output. $functionName: " . $output);
}//no function test
//--------------------------------------------------------------------------
//no tests will be written on the above functions for now if ever.
//--------------------------------------------------------------------------
function isNullOrEmptyStr(?string $value): bool
{
    return $value === null || $value === '';
}
//------------------
function isNullOrEmptyStrTest(): void
{
    // Test case: non-null, non-empty string
    test(isNullOrEmptyStr('hello') === false, 'non-empty string test');

    // Test case: empty string
    test(isNullOrEmptyStr('') === true, 'empty string test');

    // Test case: null value
    test(isNullOrEmptyStr(null) === true, 'null value test');
}
//------------------
function checkNumInRange(float $num, float $min, float $max, string $errMsg = 'number out of range'): void
{
    errIf($num < $min, $errMsg, cEOutOfRangeException);
    errIf($num > $max, $errMsg, cEOutOfRangeException);
}
//------------------
function checkNumInRangeTest(): void
{
    // Test case: number within range
    checkNumInRange(5.0, 0.0, 10.0);
    testPassed('number within range test');

    // Test case: number at lower boundary
    checkNumInRange(0.0, 0.0, 10.0);
    testPassed('number at lower boundary test');

    // Test case: number at upper boundary
    checkNumInRange(10.0, 0.0, 10.0);
    testPassed('number at upper boundary test');

    // Test case: number below range
    try {
        checkNumInRange(-1.0, 0.0, 10.0);
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testCorrectExceptionThrown($e, cEOutOfRangeException);
    }

    // Test case: number above range
    try {
        checkNumInRange(11.0, 0.0, 10.0);
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testCorrectExceptionThrown($e, cEOutOfRangeException);
    }

    // Test case: float within range
    checkNumInRange(5.5, 0.0, 10.0);
    testPassed('float within range test');
}
//-------------
function errKeyNotFound(string $key): void
{
    $message = "key '$key' not found";
    err($message, cEKeyNotFound);
}
//-------------
function errKeyNotFoundTest(): void
{
    try {
        errKeyNotFound('missingKey');
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testCorrectExceptionThrown($e, cEKeyNotFound);
    }
}
//-------------
function errIfNull(mixed $value, string $message = 'Null value invalid'): void
{
    errIf(is_null($value), $message, cENullInvalid);
}
//-------------
function errIfNullTest(): void
{
    errIfNull('string');
    testPassed('non null test');
    try {
        $value = null;
        errIfNull($value);
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testCorrectExceptionThrown($e, cENullInvalid);
    }
}
//-------------
function errIfNullOrEmptyStr(?string $value, string $message = 'Null or empty string value invalid'): void
{
    errIfNull($value, $message);
    errIf($value === '', $message, cEEmptyStrInvalid);
}
//-------------
function errIfNullOrEmptyStrTest(): void
{
    errIfNullOrEmptyStr('string');
    testPassed('not null or empty str test');
    try {
        $value = null;
        errIfNullOrEmptyStr($value);
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testCorrectExceptionThrown($e, cENullInvalid);
    }
    try {
        $value = '';
        errIfNullOrEmptyStr($value);
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testCorrectExceptionThrown($e, cEEmptyStrInvalid);
    }
}
//-------------
function checkArrayAndKeyValid(array $array, string|int $key): void
{
    errIfNull($key, 'key may not be null');
    errIf($key === '', 'key may not be an empty str', cEEmptyStrInvalid);
}
//-------------
function checkArrayAndKeyValidTest(): void
{
    $validArray['name'] = 'derf';
    checkArrayAndKeyValid($validArray, 'derf');
    testPassed('valid array and key test');
    try {
        checkArrayAndKeyValid($validArray, '');
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testCorrectExceptionThrown($e, cEEmptyStrInvalid);
    }
}
//-------------
function arrayIsEmpty(array $array): bool
{
    return count($array) === 0;
}
//-------------
function arrayIsEmptyTest(): void
{
    test(arrayIsEmpty([]) === true, 'empty test');
    test(arrayIsEmpty([1]) === false, 'not empty test');
}
//-------------
function errIfArrayIsEmpty(array $array): void
{
    errIf(arrayIsEmpty($array), 'Empty array invalid', cEEmptyArrayInvalid);
}
//-------------
function errIfArrayIsEmptyTest(): void
{
    $array = [];
    $array[] = 1;
    errIfArrayIsEmpty($array);
    testPassed('array ok test');
    try {
        errIfArrayIsEmpty([]);
        errExceptionNotThrown();
    } catch (Throwable $ex) {
        testCorrectExceptionThrown($ex, cEEmptyArrayInvalid);
    }
}
//-------------
function arrayKeyExists(array $array, string|int $key): bool
{
    checkArrayAndKeyValid($array, $key);
    return array_key_exists($key, $array);
}
//-------------
function arrayKeyExistsTest(): void
{
    $array['color'] = 'red';
    test(arrayKeyExists($array, 'color'), 'key exists test');
    test(!arrayKeyExists($array, 'missingKey'), 'key does not exist test');
}
//-------------
function checkKeyExists(array $array, string|int $key): void
{
    check(arrayKeyExists($array, $key), 'key not found', cEKeyNotFound);
}
//-------------
function checkKeyExistsTest(): void
{
    $array['color'] = 'red';
    checkKeyExists($array, 'color');
    testPassed('key found test');
    try {
        checkKeyExists($array, 'missingKey');
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testCorrectExceptionThrown($e, cEKeyNotFound);
    }
}
//-------------
function arraySetNewKey(array &$array, string|int $key, mixed $value): void
{
    errIf(arrayKeyExists($array, $key), "key $key already exists", cEKeyAlreadyExists);
    $array[$key] = $value;
}
//-------------
function arraySetNewKeyTest(): void
{
    $array = [];
    arraySetNewKey($array, 'pasta', 'bowtie');
    test(arrayKeyExists($array, 'pasta'), 'key exists test');
    test(arrayGet($array, 'pasta') === 'bowtie', 'value test');
    try {
        arraySetNewKey($array, 'pasta', 'spaghetti');
        echoReadable('should not be here');
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testCorrectExceptionThrown($e, cEKeyAlreadyExists);
    }
}
//-------------
function arrayGet(array $array, string|int $key): mixed
{
    checkKeyExists($array, $key);
    return $array[$key];
}
//-------------
function arrayGetTest(): void
{
    $array['fruit'] = 'apple';
    test(arrayGet($array, 'fruit') === 'apple', 'basic test');
    try {
        arrayGet($array, '');
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testCorrectExceptionThrown($e, cEEmptyStrInvalid);
    }

    try {
        arrayGet($array, 'pasta');
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testCorrectExceptionThrown($e, cEKeyNotFound);
    }
}
//-------------
function arrayGetOrDefault(array $array, string|int $key, mixed $default): mixed
{
    checkArrayAndKeyValid($array, $key);
    if (array_key_exists($key, $array)) {
        return $array[$key];
    } else {
        return $default;
    }
}
//-------------
function arrayGetOrDefaultTest(): void
{
    $array['fruit'] = 'apple';
    test(arrayGetOrDefault($array, 'fruit', 'grape') === 'apple', 'default not used test');
    test(arrayGetOrDefault($array, 'pasta', 'bowtie') === 'bowtie', 'default used test');
    try {
        arrayGetOrDefault($array, '', 'bowtie');
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testCorrectExceptionThrown($e, cEEmptyStrInvalid);
    }
}
//-------------
function arrayDelete(array &$array, int $indexToStartDelete): void
{
    array_splice($array, $indexToStartDelete, 1);
}
//-------------
function arrayDeleteItem(array &$array, mixed $itemToDelete): void
{
    $key = array_search($itemToDelete, $array, true);
    if ($key !== false) {
        if (is_string($key)) {
            unset($array[$key]);
        } else {
            // For numeric keys, we use array_splice to maintain sequential indices (typical for Zebra tables)
            // But we must use the physical position, not the key value.
            $keys = array_keys($array);
            $position = array_search($key, $keys, true);
            arrayDelete($array, $position);
        }
    }
}
//-------------
function getGet(string|int $key): mixed
{
    return arrayGet($_GET, $key);
}
//-------------
function postGet(string|int $key): mixed
{
    return arrayGet($_POST, $key);
}
//-------------
function requestGet(string|int $key): mixed
{
    return arrayGet($_REQUEST, $key);
}
//-------------
function requestGetTest(): void
{
    $_REQUEST['fruit'] = 'apple';
    test(requestGet('fruit') === 'apple', 'basic test');
    try {
        requestGet('pasta');
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testCorrectExceptionThrown($e, cEKeyNotFound);
    }
}
//-------------
function requestGetJsonDecode(string|int $key): mixed
{
    $result = requestGet($key);
    errIfNullOrEmptyStr($result, 'Empty json for key "' . $key . '"');
    return jsonDecodeBetter($result);
}
//-------------
function requestGetOrDefault(string|int $key, mixed $default): mixed
{
    return arrayGetOrDefault($_REQUEST, $key, $default);
}
//-------------
function requestGetOrDefaultTest(): void
{
    $_REQUEST['fruit'] = 'apple';
    test(requestGetOrDefault('fruit', 'grape') === 'apple', 'default not used test');
    test(requestGetOrDefault('pasta', 'grape') === 'grape', 'default used test');
}
//-------------
function requestGetOrDefaultJsonDecode(string|int $key, string $default): mixed
{
    $result = requestGetOrDefault($key, $default);
    errIfNullOrEmptyStr($result, 'Empty json for key "' . $key . '"');
    return jsonDecodeBetter($result);
}
//------------------
function echoLine(mixed $value): void
{
    $string = $value . '';//convert to string if not a string
    $string = str_replace("\n", "<br/>", $string);
    $string = strEnsureSuffix('<br/>', $string);
    echo $string;
}//no function test
//------------------
function echoDie(string $string = 'intentional die here'): void
{
    echoLine($string);
    die;
}//no function test
//------------------
function echoBreak(): void
{
    echoLine("");
}//no function test
//------------------
function echoLineBlockStart(string $message): void
{
    echoBreak();
    echoLine("vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv " . $message . " started");
}//no function test
//------------------
function echoLineBlockFinish(string $message): void
{
    echoLine("^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^ " . $message . " finished");
}//no function test
//------------------
function echoReadable(mixed $value): void
{
    echoLine(valueToReadable($value));
}//no function test
//------------------
function valueToReadable(mixed $value): string
{//
    if (is_array($value)) {
        $readableArray = arrayToReadable($value);
        return strDeleteSuffix("\n", $readableArray);
    }
    if (is_string($value)) {//don't use json_encode for strings because escape codes are not desired
        return '"' . $value . '"';
    }
    if (is_float($value)) {
        return sprintf('%.15F', $value);//number_format($value, 15);
    }
    return json_encode($value);
}
//------------------
function arraySumKey(array $table, string|int $key): float|int
{
    $result = 0;
    foreach ($table as $row) {
        checkKeyExists($row, $key);
        $result = $result + $row[$key];
    }
    return $result;
}
//------------------
function arraySumKeyTest(): void
{
    $table = [
        ['val' => 10, 'name' => 'a'],
        ['val' => 20, 'name' => 'b'],
        ['val' => 30, 'name' => 'c']
    ];
    test(arraySumKey($table, 'val') === 60, 'integer sum test');

    $tableFloat = [['v' => 1.5], ['v' => 2.5]];
    test(arraySumKey($tableFloat, 'v') === 4.0, 'float sum test');

    test(arraySumKey([], 'val') === 0, 'empty table test');

    try {
        arraySumKey($table, 'missing');
        errExceptionNotThrown();
    } catch (Throwable $ex) {
        testCorrectExceptionThrown($ex, cEKeyNotFound);
    }
}
//------------------
function arrayToReadable(array $array, string $prefix = ''): string
{
    if (count($array) === 0) {
        return $prefix . '[]' . "\n";
    }
    $lines = [];
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $lines[] = arrayToReadable($value, "$prefix" . "[$key]");
        } else {
            $lines[] = $prefix . "[$key]" . valueToReadable($value) . "\n";
        }
    }
    return implode('', $lines);
}
//------------------
function arrayToReadableTest(): void
{
    $emptyList = [];

    $child = [];
    $child['name'] = "Fred";
    $child['age'] = 5;

    $parent = [];
    $parent['name'] = "George";
    $parent['age'] = 30;
    $parent['child'] = $child;

    $list = [];
    $list = [47, 52, [10, 20, 30]];
    $list[] = $emptyList;
    $list[] = "honey";
    $list["fruit"] = "apple";
    $list[] = $parent;
    $output = arrayToReadable($list);
    testEchoOutput($output);
}
//------------------
function echoDebug(mixed $value): void
{
    echoLine('echoDebug at line ' .
        backtraceCallerLineNumber() . ', ' .
        backtraceCallerFunctionName() . ' ' .
        backtraceCallerFilePath());
    $value = valueToReadable($value);
    echoLine($value);
}//no function test
//------------------
function urlQueryFromArray(array $array): string
{
    if (count($array) === 0) {
        return '';
    }
    $parts = [];
    foreach ($array as $key => $param) {
        $parts[] = $key . '=' . rawurlencode((string) $param);
    }
    return '?' . implode('&', $parts);
}
//------------------
function errIfFileExists(string $filePath): void
{
    errIf(file_exists($filePath), "file exists at destination");
}
//------------------
function errIfFileExistsNoOverwrite(string $filePath, bool $overwrite): void
{
    if (!$overwrite) {
        errIfFileExists($filePath);
    }
}
//------------------
function jsonDecodeBetter(string $json): mixed
{
    errIfNullOrEmptyStr($json, 'Empty json invalid');
    $jsonResult = json_decode($json, true);
    check(json_last_error() === JSON_ERROR_NONE, 'decode of json failed: ' . json_last_error_msg());
    return $jsonResult;
}
//------------------
function jsonEncodeBetter(mixed $value): string
{
    $jsonResult = json_encode($value);
    errIf($jsonResult === false, 'json encode failed');
    return $jsonResult;
}
//------------------
function jsonEncodeBetterTest(): void
{
    test(jsonEncodeBetter(['a' => 1]) === '{"a":1}', 'basic test');
    test(jsonEncodeBetter(null) === 'null', 'null value test');
    test(jsonEncodeBetter(123) === '123', 'integer value test');
    test(jsonEncodeBetter("hello") === '"hello"', 'string value test');
}
//------------------
function jsonDecodeBetterTest(): void
{
    $res = jsonDecodeBetter('{"a":1}');
    test($res['a'] === 1, 'basic test');
    test(jsonDecodeBetter('null') === null, 'null test');
}
//------------------
function arrayIsAssoc(array $array): bool
{
    foreach (array_keys($array) as $k => $v) {
        if ($k !== $v) {
            return true;
        }
    }
    return false;
}
//------------------
function checkArrayIndexesSequential(array $array, string $message = 'array indexes must be sequential and zero-based'): void
{
    $indexes = array_keys($array);
    $sequentialIndexes = array_keys(array_values($array));
    errIf($indexes !== $sequentialIndexes, $message);
}
//------------------
function checkArrayIndexesSequentialTest(): void
{
    checkArrayIndexesSequential([]);
    testPassed('empty array test');

    checkArrayIndexesSequential([10, 20, 30]);
    testPassed('sequential indexes test');

    try {
        checkArrayIndexesSequential([1 => 'a', 2 => 'b']);
        errExceptionNotThrown();
    } catch (Throwable $ex) {
        testExceptionThrown($ex, 'sparse indexes test');
    }

    try {
        checkArrayIndexesSequential(['a' => 1, 'b' => 2]);
        errExceptionNotThrown();
    } catch (Throwable $ex) {
        testExceptionThrown($ex, 'associative array test');
    }
}
//------------------
function arrayIsAssocTest(): void
{
    test(arrayIsAssoc(['a' => 1, 'b' => 2]) === true, 'assoc test');
    test(arrayIsAssoc([1, 2, 3]) === false, 'indexed test');
    test(arrayIsAssoc(['0' => 'a', '1' => 'b']) === false, 'string indexed test');
    test(arrayIsAssoc([]) === false, 'empty test');
}
//------------------
function tableColumnGet(array $table, string|int $columnName): array
{//
    errIfArrayIsEmpty($table);
    errIfArrayIsEmpty($table[0]);
    $col = [];
    foreach ($table as $row) {
        $col[] = arrayGet($row, $columnName);
    }

    return $col;
}
//------------------
function tableColumnGetTest(): void
{
    $array = [];
    $array[0]['col1'] = 'z';
    $array[0]['col2'] = 1;
    $array[1]['col1'] = 'f';
    $array[1]['col2'] = 5;
    $array[2]['col1'] = 'a';
    $array[2]['col2'] = 9;
    $col1 = tableColumnGet($array, 'col1');
    test($col1[0] === 'z', 'basic test 1');
    test($col1[1] === 'f', 'basic test 2');
    test($col1[2] === 'a', 'basic test 3');
    $col2 = tableColumnGet($array, 'col2');
    test($col2[0] === 1, 'basic test 4');
    test($col2[1] === 5, 'basic test 5');
    test($col2[2] === 9, 'basic test 6');
}
//------------------
function tableSortByColumn(array &$array, string $colKey, bool $ascending = true): void
{
    if ($ascending) {
        $arg = SORT_ASC;
    } else {
        $arg = SORT_DESC;
    }
    $col = tableColumnGet($array, $colKey);
    array_multisort($col, $arg, $array);
}
//-----------
function tableSortByColumnTest(): void
{
    $array = [];
    $array[0]['col1'] = 'z';
    $array[0]['col2'] = 1;
    $array[1]['col1'] = 'f';
    $array[1]['col2'] = 5;
    $array[2]['col1'] = 'a';
    $array[2]['col2'] = 9;

    tableSortByColumn($array, 'col1');
    $col1 = tableColumnGet($array, 'col1');
    test($col1[0] === 'a', 'basic test 1');
    test($col1[1] === 'f', 'basic test 2');
    test($col1[2] === 'z', 'basic test 3');
    $col2 = tableColumnGet($array, 'col2');
    test($col2[0] === 9, 'basic test 4');
    test($col2[1] === 5, 'basic test 5');
    test($col2[2] === 1, 'basic test 6');

    tableSortByColumn($array, 'col2');
    $col1 = tableColumnGet($array, 'col1');
    test($col1[0] === 'z', 'basic test 7');
    test($col1[1] === 'f', 'basic test 8');
    test($col1[2] === 'a', 'basic test 9');
    $col2 = tableColumnGet($array, 'col2');
    test($col2[0] === 1, 'basic test 10');
    test($col2[1] === 5, 'basic test 11');
    test($col2[2] === 9, 'basic test 12');

    tableSortByColumn($array, 'col2', false);
    $col1 = tableColumnGet($array, 'col1');
    test($col1[0] === 'a', 'basic test 13');
    test($col1[1] === 'f', 'basic test 14');
    test($col1[2] === 'z', 'basic test 15');
    $col2 = tableColumnGet($array, 'col2');
    test($col2[0] === 9, 'basic test 16');
    test($col2[1] === 5, 'basic test 17');
    test($col2[2] === 1, 'basic test 18');
}
//-----------
// Never rely on string-checking for security. Always use PDO Prepared Statements with parameters as the primary defense.
//fix this at places where it is used
function checkSqlIsSelect(string $sql): void
{
    errIf(preg_match("/\bdelete\b/i", $sql) === 1, 'delete invalid in select statement');
    errIf(preg_match("/\bupdate\b/i", $sql) === 1, 'update invalid in select statement');
    errIf(preg_match("/\binsert\b/i", $sql) === 1, 'insert invalid in select statement');
    errIf(preg_match("/\bdrop\b/i", $sql) === 1, 'drop invalid in select statement');
    errIf(preg_match("/\bgrant\b/i", $sql) === 1, 'grant invalid in select statement');
    check(preg_match("/\bselect\b/i", $sql) === 1, 'select not found in select statement');
}
//------------------
function checkSqlIsSelectTest(): void
{
    checkSqlIsSelect('select * from cust');
    testPassed('basic test');

    try {
        checkSqlIsSelect('delete from cust');
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testExceptionThrown($e, 'found delete test');
    }

    try {
        checkSqlIsSelect('update cust');
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testExceptionThrown($e, 'select keyword not found');
    }

    try {
        checkSqlIsSelect('insert into cust');
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testExceptionThrown($e, 'insert found in select');
    }
}
//------------------
function summaryToReadable(mixed $value): string
{
    if (is_array($value)) {
        return "array";
    } else {
        return valueToReadable($value);
    }
}
//------------------
function exceptionToUserReadable(Throwable $exception): string
{
    $errMessage = $exception->getMessage();
    return $errMessage;
}
//------------------
function exceptionToReadable(Throwable $exception, string $replacementErrMessage = ''): string
{
    $errClassName = get_class($exception);
    $errCode = $exception->getCode();
    if ($replacementErrMessage === '') {
        $errMessage = $exception->getMessage();
    } else {
        $errMessage = $replacementErrMessage;
    }
    $errMessage = "error:\"$errMessage\", class:$errClassName, code:$errCode";
    return $errMessage;
}
//------------------
function backtraceToReadable(array $backtrace): string
{
    $newBackTrace = [];
    foreach ($backtrace as $call) {
        $fileValue = 'NO FILE ';
        $lineValue = 'NO LINE ';
        $functionValue = 'NO FUNCTION ';
        $argsStr = '';
        if (key_exists('file', $call)) {
            $fileValue = $call['file'];
        }
        if (key_exists('line', $call)) {
            $lineValue = $call['line'];
        }
        if (key_exists('function', $call)) {
            $functionValue = $call['function'];
        }
        if (key_exists('args', $call)) {
            $callArgs = $call['args'];
            $argParts = [];
            foreach ($callArgs as $arg) {
                $argParts[] = summaryToReadable($arg);
            }
            $argsStr = '(' . implode(', ', $argParts) . ')';
        }
        $callStr = $fileValue . ' at line ' . $lineValue . ', ' . $functionValue . $argsStr;
        $newBackTrace[] = $callStr;
    }
    return valueToReadable($newBackTrace);
}
//------------------
function backtraceToReadableTest(): void
{
    try {
        err('error for backtrace');
    } catch (Throwable $ex) {
        $readable = backtraceToReadable($ex->getTrace());
        testEchoOutput($readable);
    }
}
//------------------
function exceptionAndBacktraceToReadable(Throwable $exception): string
{
    $errMessage = exceptionToReadable($exception);
    $readableBacktrace = backtraceToReadable($exception->getTrace());
    return $errMessage . "\n" . $readableBacktrace;
}
//-------------
function strEnsureSuffix(string $suffix, string $str): string
{
    if (strHasSuffix($suffix, $str)) {
        return $str;
    } else {
        return $str . $suffix;
    }
}
//-------------
function strEnsureSuffixTest(): void
{
    test(strEnsureSuffix('ing', 'test') === 'testing', 'basic test');
    test(strEnsureSuffix('ing', 'testing') === 'testing', 'suffix there test');
    test(strEnsureSuffix('ing', '') === 'ing', 'empty string test');
    test(strEnsureSuffix('', 'testing') === 'testing', 'empty suffix test');
}
//------------------
function hashSha256(string $keyToHash): string
{
    return hash('sha256', $keyToHash);
}
//------------------
function hashSha256Test(): void
{
    $knownHash = 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3';
    test(hashSha256('123') === $knownHash, 'known hash test');
    test(strlen(hashSha256('hello')) === 64, 'output length test');
    test(hashSha256('abc') !== hashSha256('ABC'), 'case sensitive test');
}
//------------------
function echoRow(array $row): void
{
    $cells = [];
    foreach ($row as $cell) {
        $cells[] = (string) $cell;
    }
    echoLine(implode(', ', $cells));
}//no function test
//------------------
function echoArray2d(array $array): void
{
    foreach ($array as $row) {
        echoRow($row);
    }
}//no function test
//------------------
function echoHtmlTable(array $table, int $sigFigs = 8): void
{
    echo htmlTable($table, $sigFigs);
}
//------------------
function echoHtmlTableFromRow(array $row, int $sigFigs = 8): void
{
    echo htmlTableFromRow($row, $sigFigs);
}
//------------------
function htmlTh(array $table): string
{
    errIfArrayIsEmpty($table);
    $keys = array_keys($table[0]);
    $ths = [];
    foreach ($keys as $key) {
        $ths[] = '<th>' . htmlEscape($key) . '</th>';
    }
    return '<tr>' . implode('', $ths) . '</tr>';
}
//------------------
function htmlEscape(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
//------------------
function htmlTr(array $row, int $sigFigs = 8): string
{
    $tds = [];
    foreach ($row as $cell) {
        if (is_numeric($cell)) {
            $cell = round((float) $cell, $sigFigs);
        }
        $tds[] = '<td>' . htmlEscape($cell) . '</td>';
    }
    return '<tr>' . implode('', $tds) . '</tr>';
}
//------------------
function htmlTable(array $table, int $sigFigs = 8): string
{
    if (count($table) == 0) {
        return "<table></table>";
    }

    $header = htmlTh($table);
    $rows = [];
    foreach ($table as $row) {
        $rows[] = htmlTr($row, $sigFigs);
    }
    return '<table>' . $header . ' ' . implode(' ', $rows) . '</table>';
}
//------------------
function htmlTableFromRow(array $row, int $sigFigs = 8): string
{
    $table = [];
    $table[] = $row;
    return htmlTable($table, $sigFigs);
}
//-----------------------------------------
function arrayValuesToCsv(array $array): string
{
    errIfArrayIsEmpty($array);
    $values = [];
    foreach ($array as $value) {
        $values[] = json_encode($value);
    }
    return implode(',', $values);
}
//-----------------------------------------
function arrayValuesToCsvTest(): void
{
    $array = [];
    $array['color'] = 'red';
    $array['fruit'] = 'apple';
    $array['bird'] = 'robin';
    $keyNamesCsv = arrayValuesToCsv($array);
    test($keyNamesCsv === '"red","apple","robin"', 'basic test');
    try {
        arrayValuesToCsv([]);
        errExceptionNotThrown();
    } catch (Throwable $ex) {
        testCorrectExceptionThrown($ex, cEEmptyArrayInvalid);
    }
}
//-----------------------------------------
function arrayKeys(array $array): array
{
    errIfArrayIsEmpty($array);
    return array_keys($array);
}
//-----------------------------------------
function arrayKeyNamesToCsv(array $array): string
{
    $keys = arrayKeys($array);
    return arrayValuesToCsv($keys);
}
//-----------------------------------------
function arrayKeyNamesToCsvTest(): void
{
    $array = [];
    $array['color'] = 'red';
    $array['fruit'] = 'apple';
    $array['bird'] = 'robin';
    $keyNamesCsv = arrayKeyNamesToCsv($array);
    test($keyNamesCsv === '"color","fruit","bird"', 'basic test');
    try {
        arrayKeyNamesToCsv([]);
        errExceptionNotThrown();
    } catch (Throwable $ex) {
        testCorrectExceptionThrown($ex, cEEmptyArrayInvalid);
    }
}
//-----------------------------------------
function tableColumnNames(array $table): array
{
    errIfArrayIsEmpty($table);
    return arrayKeys($table[0]);
}
//-----------------------------------------
function tableColumnNamesTest(): void
{
    $table = [
        ['id' => 1, 'name' => 'Alice'],
        ['id' => 2, 'name' => 'Bob']
    ];
    $names = tableColumnNames($table);
    test($names === ['id', 'name'], 'basic test');

    try {
        tableColumnNames([]);
        errExceptionNotThrown();
    } catch (Throwable $ex) {
        testCorrectExceptionThrown($ex, cEEmptyArrayInvalid);
    }
}
//-----------------------------------------
function tableToCsv(array $table): string
{
    errIfArrayIsEmpty($table);
    $lines = [];
    $lines[] = arrayKeyNamesToCsv($table[0]);

    foreach ($table as $row) {
        $lines[] = arrayValuesToCsv($row);
    }
    return implode("\n", $lines) . "\n";
}
//----------------------------------------
function tableToCsvTest(): void
{
    $table = [
        ['id' => 1, 'name' => 'Alice'],
        ['id' => 2, 'name' => 'Bob']
    ];
    $csv = tableToCsv($table);
    $expected = "\"id\",\"name\"\n1,\"Alice\"\n2,\"Bob\"\n";
    test($csv === $expected, 'basic test');

    $table2 = [['name' => 'Hello, World']];
    test(strHasSubStr('"Hello, World"', tableToCsv($table2)), 'comma escaping test');

    try {
        tableToCsv([]);
        errExceptionNotThrown();
    } catch (Throwable $ex) {
        testCorrectExceptionThrown($ex, cEEmptyArrayInvalid);
    }
}
//----------------------------------------
function arrayHasKeyValues(array $array, array $keyValueList): bool
{
    foreach ($keyValueList as $key => $value) {
        if (arrayGet($array, $key) !== $value) {
            return false;
        }
    }

    return true;
}
//--------------
function arrayHasKeyValuesTest(): void
{
    $array = ['id' => 1, 'name' => 'Alice', 'role' => 'admin'];
    test(arrayHasKeyValues($array, ['id' => 1, 'role' => 'admin']) === true, 'match test');
    test(arrayHasKeyValues($array, ['id' => 1, 'role' => 'user']) === false, 'mismatch test');
    test(arrayHasKeyValues($array, []) === true, 'empty criteria test');

    try {
        arrayHasKeyValues($array, ['nonexistent' => 'val']);
        errExceptionNotThrown();
    } catch (Throwable $ex) {
        testCorrectExceptionThrown($ex, cEKeyNotFound);
    }
}
//--------------
function tableColumnDelete(array $table, string|int $columnName): array
{
    foreach ($table as &$record) {
        unset($record[$columnName]);
    }
    unset($record);
    return $table;
}
//--------------
function tableColumnDeleteTest(): void
{
    $table = [
        ['id' => 1, 'name' => 'Alice'],
        ['id' => 2, 'name' => 'Bob']
    ];
    $table = tableColumnDelete($table, 'name');
    test(count($table[0]) === 1, 'column count test');
    test(array_key_exists('name', $table[0]) === false, 'column deleted test');

    $table = tableColumnDelete($table, 'nonexistent');
    test(count($table[0]) === 1, 'nonexistent delete test');

    test(tableColumnDelete([], 'any') === [], 'empty table test');
}
//--------------
function tableColumnAdd(array &$table, string|int $columnName, mixed $columnValue): void
{
    foreach ($table as &$row) {
        $row[$columnName] = $columnValue;
    }
}
//--------------
function tableColumnAddTest(): void
{
    $table = [
        ['id' => 1],
        ['id' => 2]
    ];
    tableColumnAdd($table, 'active', true);
    test($table[0]['active'] === true, 'row 0 test');
    test($table[1]['active'] === true, 'row 1 test');

    tableColumnAdd($table, 'active', false);
    test($table[0]['active'] === false, 'overwrite test');
}
//----------------------------------------
function tableFilterWithCondition(array $table, callable $conditionFunction): array
{
    $rows = [];
    foreach ($table as $row) {
        if ($conditionFunction($row)) {
            $rows[] = $row;
        }
    }
    return $rows;
}
//----------------------------------------
function tableFilterWithConditionTest(): void
{
    $table = [
        ['id' => 1, 'v' => 10],
        ['id' => 2, 'v' => 20]
    ];
    $res = tableFilterWithCondition($table, function ($r) {
        return $r['v'] > 15;
    });
    test(count($res) === 1, 'count test');
    test($res[0]['id'] === 2, 'filter test');

    test(tableFilterWithCondition([], function ($r) {
        return true;
    }) === [], 'empty table test');
}
//----------------------------------------
function tableFilterWithConditionParam(
    array $table,
    callable $conditionFunction,
    mixed $param
): array {
    $rows = [];
    foreach ($table as $row) {
        if ($conditionFunction($row, $param)) {
            $rows[] = $row;
        }
    }
    return $rows;
}
//----------------------------------------
function tableFilterWithConditionParamTest(): void
{
    $table = [['v' => 10], ['v' => 20]];
    $res = tableFilterWithConditionParam($table, function ($r, $p) {
        return $r['v'] > $p;
    }, 15);
    test(count($res) === 1, 'count test');
}
//----------------------------------------
function tableFilterWithKeyValues(array $table, array $keyValueList): array
{//good
    $rows = [];
    foreach ($table as $row) {
        if (arrayHasKeyValues($row, $keyValueList)) {
            $rows[] = $row;
        }
    }
    return $rows;
}
//----------------------------------------
function tableFilterWithKeyValuesTest(): void
{
    $table = [['id' => 1, 't' => 'A'], ['id' => 2, 't' => 'B'], ['id' => 3, 't' => 'A']];
    $res = tableFilterWithKeyValues($table, ['t' => 'A']);
    test(count($res) === 2, 'count test');

    $res2 = tableFilterWithKeyValues($table, ['t' => 'C']);
    test(count($res2) === 0, 'no match test');
}
//----------------------------------------
function tableRowFindWithCondition(array $table, callable $conditionFunction, int $startIndex = 0): int
{
    $maxIndex = count($table) - 1;
    for ($i = $startIndex; $i <= $maxIndex; $i++) {
        $row = $table[$i];
        if ($conditionFunction($row)) {
            return $i;
        }
    }
    return -1;
}
//----------------------------------------
function tableRowFindWithConditionTest(): void
{
    $table = [['v' => 10], ['v' => 20], ['v' => 30]];
    test(tableRowFindWithCondition($table, function ($r) {
        return $r['v'] === 20;
    }) === 1, 'found test');
    test(tableRowFindWithCondition($table, function ($r) {
        return $r['v'] === 40;
    }) === -1, 'not found test');
    test(tableRowFindWithCondition($table, function ($r) {
        return $r['v'] === 10;
    }, 1) === -1, 'start index skip test');
}
//----------------------------------------
function tableRowFindWithKeyValues(array $table, array $keyValueList, int $startIndex = 0): int
{
    $maxIndex = count($table) - 1;
    for ($i = $startIndex; $i <= $maxIndex; $i++) {
        $row = $table[$i];
        if (arrayHasKeyValues($row, $keyValueList)) {
            return $i;
        }
    }
    return -1;
}
//----------------------------------------
function tableRowFindWithKeyValuesTest(): void
{
    $table = [['id' => 1], ['id' => 2], ['id' => 1]];
    test(tableRowFindWithKeyValues($table, ['id' => 2]) === 1, 'found test');
    test(tableRowFindWithKeyValues($table, ['id' => 1], 1) === 2, 'start index test');
    test(tableRowFindWithKeyValues($table, ['id' => 3]) === -1, 'not found test');
}
//----------------------------------------
function tableRowExistsWithCondition(array $table, callable $conditionFunction): bool
{//good
    return tableRowFindWithCondition($table, $conditionFunction) >= 0;
}
//----------------------------------------
function tableRowExistsWithConditionTest(): void
{
    $table = [['v' => 10]];
    test(tableRowExistsWithCondition($table, function ($r) {
        return $r['v'] === 10;
    }) === true, 'exists test');
}
//----------------------------------------
function tableRowExistsWithKeyValues(array $table, array $keyValueList): bool
{//good
    return tableRowFindWithKeyValues($table, $keyValueList) >= 0;
}
//----------------------------------------
function tableRowExistsWithKeyValuesTest(): void
{
    $table = [['id' => 1]];
    test(tableRowExistsWithKeyValues($table, ['id' => 1]) === true, 'exists test');
}
//----------------------------------------
function tableRowGetWithCondition(array $table, callable $conditionFunction): ?array
{//good
    $index = tableRowFindWithCondition($table, $conditionFunction);
    if ($index >= 0) {
        return $table[$index];
    } else {
        return null;
    }
}
//----------------------------------------
function tableRowGetWithConditionTest(): void
{
    $table = [['id' => 1, 'n' => 'A']];
    $row = tableRowGetWithCondition($table, function ($r) {
        return $r['id'] === 1;
    });
    test($row['n'] === 'A', 'get test');
}
//----------------------------------------
function tableRowGetWithKeyValues(array $table, array $keyValueList): ?array
{//good
    $index = tableRowFindWithKeyValues($table, $keyValueList);
    if ($index >= 0) {
        return $table[$index];
    } else {
        return null;
    }
}
//----------------------------------------
function tableRowGetWithKeyValuesTest(): void
{
    $table = [['id' => 2, 'n' => 'B']];
    $row = tableRowGetWithKeyValues($table, ['id' => 2]);
    test($row['n'] === 'B', 'get test');
}
//----------------------------------------
function tableRowDeleteWithKeyValues(array &$table, array $keyValueList): void
{//good
    $index = tableRowFindWithKeyValues($table, $keyValueList);
    if ($index >= 0) {
        arrayDelete($table, $index);
    }
}
//--------------
function tableRowDeleteWithKeyValuesTest(): void
{
    $table = [['id' => 1], ['id' => 2], ['id' => 3]];
    tableRowDeleteWithKeyValues($table, ['id' => 2]);
    test(count($table) === 2, 'delete middle row count test');
    test(tableRowExistsWithKeyValues($table, ['id' => 2]) === false, 'delete middle row existence test');
    test($table[0]['id'] === 1 && $table[1]['id'] === 3, 'delete middle row remaining test');

    tableRowDeleteWithKeyValues($table, ['id' => 99]);
    test(count($table) === 2, 'delete nonexistent row test');
}
//--------------
class Stopwatch
{
    public int $markTimeNanoSecs;
    public int $accruedNanoSecs;
    public bool $paused;
    //--------------
    function __construct()
    {
        $this->resetAndStart();
    }
    //--------------
    function resetAndStart(): void
    {
        $this->paused = true;
        $this->accruedNanoSecs = 0;
        $this->resume();
    }
    //--------------
    function accruedNanosecs(): int
    {
        if ($this->paused) {
            return $this->accruedNanoSecs;
        }
        $nanoSecsSinceMark = hrtime(true) - $this->markTimeNanoSecs;
        return $this->accruedNanoSecs + $nanoSecsSinceMark;
    }
    //--------------
    function pause(): void
    {
        if ($this->paused) {
            return;
        }

        $this->accruedNanoSecs = $this->accruedNanosecs();
        $this->paused = true;
    }
    //--------------
    function resume(): void
    {
        if ($this->paused) {
            $this->paused = false;
            $this->markTimeNanoSecs = hrtime(true);
        }
    }
    //--------------
    function accruedMillisecs(): float
    {
        return $this->accruedNanosecs() / 1_000_000;
    }
    //--------------
    function accruedSeconds(): float
    {
        return $this->accruedMillisecs() / 1000;
    }
}
//--------------
function StopwatchTest(): void
{
    $sw = new Stopwatch();
    usleep(10000); // 10ms
    $accrued = $sw->accruedMillisecs();
    test($accrued >= 10, 'initial timing test');

    $sw->pause();
    $pausedTime = $sw->accruedMillisecs();
    usleep(10000);
    test($sw->accruedMillisecs() === $pausedTime, 'pause test');

    $sw->resume();
    usleep(10000);
    test($sw->accruedMillisecs() > $pausedTime, 'resume test');

    $sw->resetAndStart();
    test($sw->accruedMillisecs() < 1, 'reset test');
}
//--------------
class Throttle
{
    public Stopwatch $stopwatch;
    public float $desiredClicksPerMin;
    public float $desiredClicksPerSec;
    public float $desiredSecsPerClick;
    public int $clickCount;
    //--------------
    function init(float $clicksPerMin): void
    {
        $this->stopwatch = new Stopwatch();
        $this->clickCount = 0;
        $this->setClicksPerMin($clicksPerMin);
    }
    //--------------
    function setClicksPerMin(float $clicksPerMin): void
    {
        errIf($clicksPerMin <= 0, 'clicksPerMin == 0');
        $this->desiredClicksPerMin = $clicksPerMin;
        $this->desiredClicksPerSec = $this->desiredClicksPerMin / 60;
        $this->desiredSecsPerClick = 60 / $this->desiredClicksPerMin;
    }
    //--------------
    function click(): void
    {
        $this->clickCount = $this->clickCount + 1;
        $elapsedSecs = $this->stopwatch->accruedSeconds();
        $targetElapsedSecs = $this->clickCount * $this->desiredSecsPerClick;

        if ($elapsedSecs < $targetElapsedSecs) {
            $secsToWait = $targetElapsedSecs - $elapsedSecs;
            $microSecsToWait = (int) ceil($secsToWait * 1000000);
            usleep($microSecsToWait);
        }

        // Reset periodically or if we fall significantly behind (more than 1 second)
        // to prevent "burst credits" and handle floating-point precision over time.
        if ($elapsedSecs > $targetElapsedSecs + 1.0 || $this->clickCount >= 1000) {
            $this->clickCount = 0;
            $this->stopwatch->resetAndStart();
        }
    }
}
//---------------------------------------
function ThrottleTest(): void
{
    $throttle = new Throttle();
    $throttle->init(600); // 10 clicks per second
    $sw = new Stopwatch();
    for ($i = 0; $i < 3; $i++) {
        $throttle->click();
    }
    $elapsed = $sw->accruedMillisecs();
    // 3 clicks should take at least ~200ms (0, 100, 200)
    test($elapsed >= 190, 'timing test');
}
//---------------------------------------
function csvLineToArray(string $csvLine): array
{
    return str_getcsv($csvLine);
}
//---------------------------------------
function csvLineToAssocArray(string $csvLine, array $keyNamesArray): array
{
    errIfNullOrEmptyStr($csvLine);
    $arrayValues = str_getcsv($csvLine);
    $assocArray = [];
    foreach ($arrayValues as $index => $value) {
        $fieldName = arrayGet($keyNamesArray, $index);
        $assocArray[$fieldName] = $value;
    }
    return $assocArray;
}
//---------------------------------------
function csvLinesToColNamesArray(string $csvStr, string $lineEnd = "\n"): array
{
    $csvLines = explode($lineEnd, $csvStr);
    $headerLine = arrayGet($csvLines, 0);
    $colNamesArray = csvLineToArray($headerLine);
    return $colNamesArray;
}
//---------------------------------------
function csvLinesToTable(string $csvStr): array
{
    $csvStr = trim($csvStr);
    $csvStr = str_replace("\r", "", $csvStr); // remove Windows line endings
    if ($csvStr === '') {
        return [];
    }
    $csvLines = explode("\n", $csvStr);
    $headerLine = arrayGet($csvLines, 0);
    $colNamesArray = csvLineToArray($headerLine);
    unset($csvLines[0]);
    $table = [];
    $count = 0;
    foreach ($csvLines as $csvLine) {
        if (trim($csvLine) === '') {
            continue;
        }
        $count = $count + 1;
        if (debuggingIsOn() && $count % 10000 === 0 && $count > 0) {//10000 reports at a good interval. 1000 is too frequent  
            echoLine($count);
        }
        $table[] = csvLineToAssocArray($csvLine, $colNamesArray);
    }
    return $table;
}
//---------------------------------------
function csvLinesToTableTest(): void
{
    $csv = "id,name\n1,Alice\n2,Bob";
    $table = csvLinesToTable($csv);
    test(count($table) === 2, 'count test');
    test($table[0]['name'] === 'Alice', 'row 0 test');
    test($table[1]['id'] === '2', 'row 1 test');

    $csvQuotes = "\"id\",\"name\"\n\"1\",\"Alice, In Chains\"";
    $table2 = csvLinesToTable($csvQuotes);
    test($table2[0]['name'] === 'Alice, In Chains', 'quotes and comma test');
}
//--------------
function arrayNth(int $nth, array $array): mixed
{
    return $array[indexRemapNegative($nth, count($array))];
}
//-------------
function arrayNthTest(): void
{
    $fruits = [];
    $fruits[0] = 'apple';
    $fruits[1] = 'pear';
    $fruits[2] = 'grape';
    test(arrayNth(0, $fruits) === 'apple', 'arrayNth basic test');
    test(arrayNth(-1, $fruits) === 'grape', 'arrayNth -1 test');
    test(arrayNth(-2, $fruits) === 'pear', 'arrayNth -2 test');
    try {
        arrayNth(10, $fruits);
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testExceptionThrown($e, 'arrayNth above range test');
    }

    try {
        arrayNth(-10, $fruits);
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testExceptionThrown($e, 'arrayNth below range test');
    }
}
//--------------
function indexRemapNegative(int $index, int $countOfItems): int
{
    $maxIndex = $countOfItems - 1;
    $result = $index;
    if ($result < 0) {
        $result = $countOfItems + $index;
    }
    errIf($result > $maxIndex, "$index maps above max of $maxIndex");
    errIf($result < 0, 'nth ' . $index . ' maps below zero');
    return $result;
}
//--------------
function indexRemapNegativeTest(): void
{
    $testArray = ['apple', 'peach', 'pear'];
    $count = count($testArray);
    test(indexRemapNegative(0, $count) === 0, 'indexRemapNegative basic test');
    test(indexRemapNegative(-1, $count) === 2, 'indexRemapNegative -1 test');
    test(indexRemapNegative(-2, $count) === 1, 'indexRemapNegative -2 test');
    try {
        indexRemapNegative(10, $count);
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testExceptionThrown($e, 'indexRemapNegative above range test');
    }
    try {
        indexRemapNegative(-10, $count);
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testExceptionThrown($e, 'indexRemapNegative below range test');
    }
}
//---------------------------------------
$gDebuggingIsOn = false;
//---------------------------------------
function debuggingIsOn(): bool
{
    global $gDebuggingIsOn;
    return $gDebuggingIsOn;
}
//---------------------------------------
function debuggingIsOff(): bool
{
    global $gDebuggingIsOn;
    return !$gDebuggingIsOn;
}
//---------------------------------------
function debuggingTurnOn(): void
{
    global $gDebuggingIsOn;
    $gDebuggingIsOn = true;
}//no function test
//---------------------------------------
function debuggingTurnOff(): void
{
    global $gDebuggingIsOn;
    $gDebuggingIsOn = false;
}//no function test
//---------------------------------------
$gProfileReport = [];
$gProfilingOff = true;
//---------------------------------------
function profilingTurnOn(): void
{
    global $gProfilingOff;
    $gProfilingOff = false;
}//no function test
//---------------------------------------
function profilingTurnOff(): void
{
    global $gProfilingOff;
    $gProfilingOff = true;
}//no function test
//---------------------------------------
function profile(?string $key = null): void
{
    global $gProfilingOff;
    if ($gProfilingOff) {
        return;
    }
    global $gProfileReport;

    $prefix = backtraceCallerFunctionName();
    if (is_null($key)) {
        $key = $prefix;
    } else {
        $key = $prefix . '-' . $key;
    }

    if (array_key_exists($key, $gProfileReport)) {
        $gProfileReport[$key][0] = $gProfileReport[$key][0] + 1;//increment call count
        $gProfileReport[$key][1]->resume();
    } else {
        $profile = [];
        $profile[0] = 1;//index zero is the call count. set to 1
        $profile[1] = new Stopwatch();
        $gProfileReport[$key] = $profile;
    }
}//no function test
//---------------------------------------
function profileEnd(?string $key = null): void
{
    global $gProfilingOff;
    if ($gProfilingOff) {
        return;
    }
    global $gProfileReport;

    $prefix = backtraceCallerFunctionName();
    if (is_null($key)) {
        $key = $prefix;
    } else {
        $key = $prefix . '-' . $key;
    }

    arrayGet($gProfileReport, $key)[1]->pause();
    //$gProfileReport[$key][1]->pause();
}//no function test
//---------------------------------------
function profileReset(): void
{
    global $gProfileReport;

    $gProfileReport = [];
}//no function test
//---------------------------------------
function profileEchoReport(): void
{
    global $gProfilingOff;
    if ($gProfilingOff) {
        return;
    }
    global $gProfileReport;

    echoLine("---Profile report");
    foreach ($gProfileReport as $key => $profile) {
        $callCount = $profile[0];
        $accruedSeconds = arrayGet($profile, 1)->accruedSeconds();
        echoLine("$key $callCount $accruedSeconds");
    }
}//no function test
//-------------
function strLeft(int $length, string $str): string
{
    if ($str === '') {
        return '';
    } else if ($length <= 0) {
        return '';
    } else {
        return substr($str, 0, $length);
    }
}
//-------------
function strHasSubStr(string $subStr, string $str): bool
{
    //todo think about the finding of an empty string later
    //if ($subStr === '') {
    //    return false;
    //}
    return str_contains($str, $subStr);
}
//-------------
function strHasSubStrTest(): void
{
    test(strHasSubStr('apple', 'apple pie') === true, 'subStr there test');
    test(strHasSubStr('apple', 'peach pear') === false, 'subStr not there test');
    test(strHasSubStr('', 'peachPear') === true, 'empty subStr test');
    test(strHasSubStr('apple', '') === false, 'empty string test');
}
//-------------
function strLeftTest(): void
{//
    test(strLeft(2, 'test') === 'te', 'basic test');
    test(strLeft(6, 'test') === 'test', 'larger than string test');
    test(strLeft(0, 'test') === '', 'zero test');
    test(strLeft(-3, 'test') === '', 'less than zero test');
    test(strLeft(5, '') === '', 'empty string test');
}
//-------------
function strHasSuffix(string $suffix, string $str): bool
{
    return str_ends_with($str, $suffix);
}
//-------------
function strHasSuffixTest(): void
{
    test(strHasSuffix('ing', 'testing') === true, 'suffix there test');
    test(strHasSuffix('ing', 'test') === false, 'suffix not there test');
    test(strHasSuffix('', 'testing') === true, 'empty suffix test');
    test(strHasSuffix('ing', '') === false, 'empty string test');
}
//-------------
function strRight(int $length, string $str): string
{//
    if ($length <= 0) {
        return '';
    } else {
        return substr($str, -$length);
    }
}
//-------------
function strRightTest(): void
{//
    test(strRight(2, 'test') === 'st', 'basic test');
    test(strRight(6, 'test') === 'test', 'larger than string test');
    test(strRight(0, 'test') === '', 'zero test');
    test(strRight(-3, 'test') === '', 'less than zero test');
    test(strRight(5, '') === '', 'empty string test');
}
//---------------------------------------
function zCoreTest(): void
{
    echoLineBlockStart('zCoreTest');

    backtraceCallerFunctionNameTest();
    backtraceCallerFilePathTest();
    backtraceCallerLineNumberTest();
    backtraceEntryTest();
    backtraceToReadableTest();
    isNullOrEmptyStrTest();
    checkNumInRangeTest();
    errKeyNotFoundTest();
    errIfNullTest();
    errIfNullOrEmptyStrTest();
    errIfArrayIsEmptyTest();
    checkArrayAndKeyValidTest();
    checkKeyExistsTest();
    arrayNthTest();
    arrayKeyExistsTest();
    arrayGetTest();
    arrayGetOrDefaultTest();
    arraySetNewKeyTest();
    arrayKeyNamesToCsvTest();
    arrayValuesToCsvTest();
    requestGetTest();
    requestGetOrDefaultTest();
    indexRemapNegativeTest();
    arrayToReadableTest();
    strLeftTest();
    strRightTest();
    strHasSubStrTest();
    strDeleteLeftTest();
    strDeleteRightTest();
    strDeleteSuffixTest();
    strHasPrefixTest();
    strEnsurePrefixTest();
    strEnsureSuffixTest();
    strHasSuffixTest();
    tableColumnGetTest();
    tableSortByColumnTest();
    checkSqlIsSelectTest();
    hashSha256Test();
    arraySumKeyTest();
    tableColumnNamesTest();
    tableToCsvTest();
    arrayHasKeyValuesTest();
    tableColumnDeleteTest();
    tableColumnAddTest();
    tableFilterWithConditionTest();
    tableFilterWithConditionParamTest();
    tableFilterWithKeyValuesTest();
    tableRowFindWithConditionTest();
    tableRowFindWithKeyValuesTest();
    tableRowExistsWithConditionTest();
    tableRowExistsWithKeyValuesTest();
    tableRowGetWithConditionTest();
    tableRowGetWithKeyValuesTest();
    tableRowDeleteWithKeyValuesTest();

    arrayIsEmptyTest();
    jsonEncodeBetterTest();
    jsonDecodeBetterTest();
    arrayIsAssocTest();
    checkArrayIndexesSequentialTest();
    csvLinesToTableTest();
    StopwatchTest();
    ThrottleTest();

    echoLineBlockFinish('zCoreTest');
}
