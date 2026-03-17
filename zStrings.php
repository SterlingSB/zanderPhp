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

declare(strict_types=1);
error_reporting(E_ALL);

require_once dirname(__FILE__) . '/zCore.php';
require_once dirname(__FILE__) . '/zMath.php';

//--------------
function strNthSubStr(int $nth, string $str, string $separator = ','): string
{
    check(str_contains($str, $separator), "separator not found");
    $items = explode($separator, $str);
    return (string) arrayNth($nth, $items);
}
//--------------
function strNthSubStrTest(): void
{
    test(strNthSubStr(0, 'apple,pear,grape') === 'apple', 'strNthSubStr 1 test');
    test(strNthSubStr(1, 'apple,pear,grape') === 'pear', 'strNthSubStr 2 test');
    test(strNthSubStr(2, 'apple,pear,grape') === 'grape', 'strNthSubStr 3 test');
    test(strNthSubStr(-3, 'apple,pear,grape') === 'apple', 'strNthSubStr -3 test');
    test(strNthSubStr(-2, 'apple,pear,grape') === 'pear', 'strNthSubStr -2 test');
    test(strNthSubStr(-1, 'apple,pear,grape') === 'grape', 'strNthSubStr -1 test');

    try {
        strNthSubStr(-1, 'apple,pear,grape', '-');
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testExceptionThrown($e, 'separator not found test');
    }

    try {
        strNthSubStr(10, 'apple,pear,grape');
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testExceptionThrown($e, 'strNthSubStr nth above range test');
    }

    try {
        strNthSubStr(-10, 'apple,pear,grape');
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testExceptionThrown($e, 'strNthSubStr nth below range test');
    }
}
//------------------
function strLineBreakType(string $str): string
{
    if (str_contains($str, "\r\n")) {
        return "\r\n";
    } else if (str_contains($str, "\n\r")) {
        return "\n\r";
    } else if (str_contains($str, "\r")) {
        return "\r";
    } else {
        return "\n";
    }
}
//------------------
function strLineBreakTypeTest(): void
{
    test(strLineBreakType("line1\nline2") === "\n", 'unix line break');
    test(strLineBreakType("line1\r\nline2") === "\r\n", 'windows line break');
    test(strLineBreakType("line1\rline2") === "\r", 'old mac line break');
    test(strLineBreakType("no breaks") === "\n", 'fallback to unix');
}
//-------------
//-------------
function strDeletePrefix(string $prefix, string $str): string
{
    if (str_starts_with($str, $prefix)) {
        return substr($str, strlen($prefix));
    }
    return $str;
}
//------------------
function strDeletePrefixTest(): void
{
    test(strDeletePrefix('test', 'testing') == 'ing', 'basic test');
    test(strDeletePrefix('test', 'ing') == 'ing', 'prefix not there test');
    test(strDeletePrefix('test', '') == '', 'empty string test');
    test(strDeletePrefix('', 'testing') == 'testing', 'empty prefix test');
}
//-----------------------------------------
//have a single line ends with \n as the standard
function strLineNormalizeLB(string $str): string
{
    $str = str_replace("\r\n", "\n", $str);
    $str = str_replace("\n\r", "\n", $str);
    $str = str_replace("\r", "\n", $str);
    $str = strEnsureSuffix("\n", $str);
    return $str;
}
//-------------
function strLineNormalizeLBTest(): void
{
    test(strLineNormalizeLB("line1\r\nline2") === "line1\nline2\n", 'windows normalize');
    test(strLineNormalizeLB("line1\rline2") === "line1\nline2\n", 'mac normalize');
    test(strLineNormalizeLB("line1\nline2\n") === "line1\nline2\n", 'already normalized');
    test(strLineNormalizeLB("test") === "test\n", 'ensure suffix added');
}
//-------------
function strDelete(string $needle, string $haystack): string
{
    return str_replace($needle, '', $haystack);
}
//-------------
function strDeleteTest(): void
{
    test(strDelete('to', 'time to eat') === 'time  eat', 'basic test');
    test(strDelete('', 'time to eat') === 'time to eat', 'empty needle test');
    test(strDelete('to', '') === '', 'empty haystack test');
    test(strDelete('apple', 'time to eat') === 'time to eat', 'needle not found test');
    // try {
    //     test(strDelete(null, 'time to eat') === 'time  eat', 'basic test');
    //     errExceptionNotThrown();
    // }
    // catch(exception $e) {
    //     testCorrectExceptionThrown($e, cENullInvalid);
    // }
}
//-----------------------------------------
function strDeleteChars(string $strOfCharsToDelete, string $sourceStr): string
{
    return strReplaceChars($strOfCharsToDelete, '', $sourceStr);
}
//-----------------------------------------
function strDeleteCharsTest(): void
{
    test(strDeleteChars('aeiou', 'apple pie') === 'ppl p', 'vowels deleted');
    test(strDeleteChars('xyz', 'apple pie') === 'apple pie', 'nothing to delete');
    test(strDeleteChars('', 'apple pie') === 'apple pie', 'empty delete string');
}
//-----------------------------------------
function strReplace(string $strToReplace, string $newSubstr, string $sourceStr): string
{
    return str_replace($strToReplace, $newSubstr, $sourceStr);
}
//-----------------------------------------
function strReplaceTest(): void
{
    test(strReplace('apple', 'pear', 'apple pie') === 'pear pie', 'basic replace');
    test(strReplace('grape', 'pear', 'apple pie') === 'apple pie', 'no match replace');
}
//-----------------------------------------
function strReplaceChars(string $strOfCharsToReplace, string $newStr, string $sourceStr): string
{
    for ($i = 0; $i < strlen($strOfCharsToReplace); $i++) {
        $sourceStr = str_replace($strOfCharsToReplace[$i], $newStr, $sourceStr);
    }
    return $sourceStr;
}
//-------------
function strReplaceCharsTest(): void
{
    test(strReplaceChars('aeiou', '*', 'apple pie') === '*ppl* p**', 'vowels to asterisks');
    test(strReplaceChars('', '*', 'test') === 'test', 'empty search string');
}
//-------------
function strInsert(int $index, string $strToInsert, string $str): string
{
    return substr($str, 0, $index) . $strToInsert . substr($str, $index);
}
//-------------
function strInsertTest(): void
{
    test(strInsert(5, 'to', 'time  eat') === 'time to eat', 'basic test');
    test(strInsert(5, '', 'time  eat') === 'time  eat', 'empty target test');
}
//============================================================
/*function strToNorm($string) {//this ignores numbers and symbols
    $string = strtoupper($string);
    $normValue = 0;
    $remaining = 1;
    $portion = 1 - 1/26;
    $currentRange = $remaining * $portion;
    for($index = 0; $index < strlen($string); $index++) {
        $char = $string[$index];
        $charOrd = ord($char);
        if($charOrd >= ord('A') && $charOrd <= ord('Z')) {
            $mappedValue = numInRangeMapToNewRange($charOrd, ord('A'), ord('Z'), 0, $currentRange);
            $normValue = $normValue + $mappedValue;
            $remaining = $remaining - $currentRange;
            $currentRange = $remaining * $portion;
        }
    }
    
    return $normValue;
}*/
//============================================================
function strToNorm(string $string): float
{
    $ordA = ord('A');
    $ordZ = ord('Z');
    $charMinRange = $ordA;
    $charMaxRange = $ordZ;
    //$charMaxRange = $ordZ - (($ordZ - $ordA) / 26); //to make room for the second char value if there is one

    $index1 = -1;
    for ($i = 0; $i < strlen($string); $i++) {
        $char1 = strtoupper($string[$i]);
        $ordChar1 = ord($char1);
        if (($ordChar1 >= $ordA) && ($ordChar1 <= $ordZ)) {
            $index1 = $i;
            break;
        }
    }

    errIf($index1 == -1, 'string has no letters');

    $normValue = numToNorm($ordChar1, $charMinRange, $charMaxRange);
    $normValue = $normValue - ($normValue / 26); //to make room for the second char value if there is one
    checkNumInRange($normValue, 0, 1, "$normValue 1 value out of range");

    $normValue2 = 0;
    if (strlen($string) > $index1 + 1) {
        $char2 = strtoupper($string[$index1 + 1]);
        if (ord($char2) >= $ordA && ord($char2) <= $ordZ) {
            $ordChar2 = ord($char2);
            $normValue2 = numToNorm($ordChar2, $charMinRange, $charMaxRange);
            $normValue2 = $normValue2 - $normValue2 / 26;
            $normValue2 = $normValue2 / 26;
        }
    }

    $normValue2 = numClampTolerance($normValue2, 0, 1, 1e-9);
    checkNumInRange($normValue2, 0, 1, "$normValue2 2 value out of range");
    $normValue = $normValue + $normValue2;
    checkNumInRange($normValue, 0, 1, "$normValue total value out of range");

    return $normValue;
}
//------------------
function strToNormTest(): void
{
    test(floatEqual(strToNorm('a'), 0 / 26), 'BasicTest a');
    test(floatEqual(strToNorm('b'), 1 / 26), 'BasicTest b');
    test(floatEqual(strToNorm('c'), 2 / 26), 'BasicTest c');
    test(floatEqual(strToNorm('d'), 3 / 26), 'BasicTest d');
    test(floatEqual(strToNorm('z'), 25 / 26), 'BasicTest z');
    test(floatEqual(strToNorm('ab'), strToNorm('a') + (1 / 26) * (25 / 26) * (1 / 25)), 'BasicTest ab');
    test(floatEqual(strToNorm('a b'), strToNorm('a')), 'Space stops second-letter parse');
}
//------------------
function strOfStr(int $count, string $str): string
{
    return str_repeat($str, max(0, $count));
}
//------------------
function strOfStrTest(): void
{
    test(strOfStr(3, 'a') == 'aaa', 'basic test');
    test(strOfStr(0, 'a') == '', 'zero test');
    test(strOfStr(-4, 'a') == '', 'negative test');
    test(strOfStr(3, '') == '', 'empty str test');
}
//--------------------------------------------------------------------------------------------------------------
function strListIndexOfStrWithSubStr(string $subStr, int $startIndex, array $strList): int
{
    $listLength = count($strList);
    for ($i = $startIndex; $i < $listLength; $i++) {
        $str = $strList[$i];
        if (str_contains($str, $subStr)) {
            return $i;
        }
    }
    return -1;
}
//--------------------------------------------------------------------------------------------------------------
function strListIndexOfStrWithSubStrTest(): void
{
    $list = ['apple', 'pear', 'grape', 'pineapple'];
    test(strListIndexOfStrWithSubStr('apple', 0, $list) === 0, 'first apple');
    test(strListIndexOfStrWithSubStr('apple', 1, $list) === 3, 'second apple (pineapple)');
    test(strListIndexOfStrWithSubStr('pear', 0, $list) === 1, 'pear found');
    test(strListIndexOfStrWithSubStr('orange', 0, $list) === -1, 'not found');
}
//--------------------------------------------------------------------------------------------------------------
function strListIndexOfLastInPrefixGroup(string $prefix, int $startIndex, array $strList): int
{
    $foundIndex = -1;
    $listLength = count($strList);
    for ($i = $startIndex; $i < $listLength; $i++) {
        $str = $strList[$i];
        if (str_starts_with($str, $prefix)) {
            $foundIndex = $i;
        } else {
            return $foundIndex;
        }
    }

    return $foundIndex;
}
//--------------------------------------------------------------------------------------------------------------
function strListIndexOfLastInPrefixGroupTest(): void
{
    $list = ['apple1', 'apple2', 'pear1', 'pear2', 'apple3'];
    test(strListIndexOfLastInPrefixGroup('apple', 0, $list) === 1, 'last apple in first group');
    test(strListIndexOfLastInPrefixGroup('pear', 2, $list) === 3, 'last pear in group');
    test(strListIndexOfLastInPrefixGroup('apple', 3, $list) === -1, 'prefix check failure');
}
//--------------------------------------------------------------------------------------------------------------
function strListKeepLastInEachPrefixGroup(string $prefixSeperator, array $strList): array
{
    $newList = [];
    $listLength = count($strList);
    $i = 0;
    while ($i < $listLength && ($listLength > 0)) {
        $str = $strList[$i];
        $prefix = strNthSubStr(0, $str, $prefixSeperator);
        $i = strListIndexOfLastInPrefixGroup($prefix, $i, $strList);
        $newList[] = $strList[$i];
        $i++;
    }

    return $newList;
}
//--------------------------------------------------------------------------------------------------------------
function strListKeepLastInEachPrefixGroupTest(): void
{
    $list = ['a,1', 'a,2', 'b,1', 'b,2', 'b,3', 'c,1'];
    $result = strListKeepLastInEachPrefixGroup(',', $list);
    test(count($result) === 3, 'correct count');
    test($result[0] === 'a,2', 'last of a');
    test($result[1] === 'b,3', 'last of b');
    test($result[2] === 'c,1', 'last of c');
}
//--------------------------------------------------------------------------------------------------------------
function strListDeleteWithSubStr(string $subStr, array $strList): array
{
    $newList = [];
    foreach ($strList as $str) {
        if (!str_contains($str, $subStr)) {
            $newList[] = $str;
        }
    }
    return $newList;
}
//--------------------------------------------------------------------------------------------------------------
function strListDeleteWithSubStrTest(): void
{
    $list = ['apple', 'pear', 'grape'];
    $result = strListDeleteWithSubStr('apple', $list);
    test(count($result) === 2, 'correct count after deletion');
    test($result[0] === 'pear', 'pear remains');
    test($result[1] === 'grape', 'grape remains');
}
//--------------------------------------------------------------------------------------------------------------
function strListDeleteWithPrefix(string $prefix, array $strList): array
{
    $newList = [];
    foreach ($strList as $str) {
        if (!str_starts_with($str, $prefix)) {
            $newList[] = $str;
        }
    }

    return $newList;
}
//--------------------------------------------------------------------------------------------------------------
function strListDeleteWithPrefixTest(): void
{
    $list = ['apple', 'pear', 'apple pie', 'grape'];
    $result = strListDeleteWithPrefix('apple', $list);
    test(count($result) === 2, 'correct count after prefix deletion');
    test($result[0] === 'pear', 'pear remains');
    test($result[1] === 'grape', 'grape remains');
}
//--------------------------------------------------------------------------------------------------------------
function strFindEndBlockIndex(string $haystack, string $startingChar, string $endingChar): int
{
    $nestCount = 0;
    for ($i = 0; $i < strlen($haystack); $i++) {
        $currChar = $haystack[$i];
        if ($currChar === $startingChar) {
            $nestCount = $nestCount + 1;
        } else if ($currChar === $endingChar) {
            $nestCount = $nestCount - 1;
        }
        if ($nestCount === 0) {
            return $i;
        }
    }
    return -1; // Added return for cases where block is not closed
}
//--------------------------------------------------------------------------------------------------------------
function strFindEndBlockIndexTest(): void
{
    test(strFindEndBlockIndex('(apple (pear) grape) extra', '(', ')') === 19, 'nested blocks');
    test(strFindEndBlockIndex('(unclosed', '(', ')') === -1, 'unclosed block');
}
//---------------------------------------
function strCompact(string $str): string
{
    $str = str_replace('.', ' .', $str);
    $str = str_replace('!', ' !', $str);
    $str = str_replace('?', ' ?', $str);
    $words = explode(' ', $str);
    $result = '';
    foreach ($words as $word) {
        if (strlen($word) > 0) {
            $result .= $word[0];
        }
    }
    return $result;
}
//-------------------
function strCompactTest(): void
{
    test(strCompact('Basic test') === 'Bt', 'Basic test');
    test(strCompact('Look out! The rock is falling!') === 'Lo!Trif!', 'Test2');
    test(strCompact('This is   a multispace   test.') === 'Tiamt.', 'Multi space test');
}
//-------------------
function zStringsTest(): void
{
    echoLineBlockStart('zStringsTest');

    strNthSubStrTest();
    strLineBreakTypeTest();
    strDeleteTest();
    strDeletePrefixTest();
    strLineNormalizeLBTest();
    strDeleteCharsTest();
    strReplaceTest();
    strReplaceCharsTest();
    strEnsureSuffixTest();
    strHasSubStrTest();
    strHasPrefixTest();
    strInsertTest();
    strOfStrTest();
    strToNormTest();
    strListIndexOfStrWithSubStrTest();
    strListIndexOfLastInPrefixGroupTest();
    strListKeepLastInEachPrefixGroupTest();
    strListDeleteWithSubStrTest();
    strListDeleteWithPrefixTest();
    strFindEndBlockIndexTest();
    strCompactTest();

    echoLineBlockFinish('zStringsTest');
}


