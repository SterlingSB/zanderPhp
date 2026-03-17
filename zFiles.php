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
error_reporting(E_ALL | E_STRICT);

require_once dirname(__FILE__) . '/zCore.php';
require_once dirname(__FILE__) . '/zStrings.php';
require_once dirname(__FILE__) . '/zDates.php';

//------------------
function fileLog(string $str, string $fileLogPath = '.' . DIRECTORY_SEPARATOR . 'log.txt'): void
{
    $time = dateTimeStrLocal();
    $timeStampedStr = "$time $str\n";
    fileAppend($timeStampedStr, $fileLogPath);
}
//------------------
function fileLogTest(): void
{
    testFilesDelete();
    $testLog = testDirName() . 'log.txt';
    fileLog('log 1', $testLog);
    fileLog('log 2', $testLog);
    $data = fileRead($testLog);
    test($data !== "", 'fileLog test');
    testFilesDelete();
}
//------------------
function fileLogReadable(mixed $value, string $fileLogPath = '.' . DIRECTORY_SEPARATOR . 'log.txt'): void
{
    fileLog(valueToReadable($value), $fileLogPath);
}
//--------
function fileLogBlockStart(string $message): void
{
    fileLog("vvvvvvvv " . $message . " started");
}
//--------
function fileLogBlockFinish(string $message): void
{
    fileLog("^^^^^^^^ " . $message . " finished");
}
//------------------
function fileLogDebug(mixed $value, string $logFilePath = 'log.txt'): void
{
    $callerInfo = 'fileLogDebugCalled at line ' .
        backtraceCallerLineNumber() . ' ' .
        backtraceCallerFunctionName() . ' ' .
        backtraceCallerFilePath();
    $readableValue = valueToReadable($value);
    $msg = $callerInfo . "\n" . $readableValue;
    fileLog($msg, $logFilePath);
}
//------------------
function fileWrite(string $data, string $filePath): void
{
    dirEnsureExists(fileExtractDir($filePath), true);
    $result = file_put_contents($filePath, $data);
    errIf($result === false, "Write to file $filePath failed");
}
//------------------
function fileWriteTest(): void
{
    testFilesDelete();

    $testIoPath = testDirName() . 'testIo.txt';
    fileWrite('some data', $testIoPath);
    $data = fileRead($testIoPath);
    test($data == 'some data', 'file write test');
    testFilesDelete();
}
//------------------
function fileAppend(string $data, string $filePath): void
{
    dirEnsureExists(fileExtractDir($filePath), true);
    $result = file_put_contents($filePath, $data, FILE_APPEND);
    errIf($result === false, "Append to file $filePath failed");
}
//------------------
function fileAppendTest(): void
{
    testFilesDelete();

    $testIoPath = testDirName() . 'testIo.txt';
    fileWrite('some data ', $testIoPath);
    fileAppend('appended more', $testIoPath);
    $data = fileRead($testIoPath);
    test($data == 'some data appended more', 'Append test');
    fileDelete($testIoPath);
}
//------------------
function fileRead(string $filePath): string
{
    errIfNullOrEmptyStr($filePath, "file path may not be empty");
    check(file_exists($filePath), "file $filePath not found");
    $result = file_get_contents($filePath);
    errIf($result === false, "Read of file $filePath failed");
    return $result;
}
//--------------------------------------------------------------------------------------------------------------
function fileReadJsonDecodeRetry(string $filePath, int $retryCount = 10): ?array
{
    $tryCount = 0;
    do {
        if (!file_exists($filePath)) {
            return null;
        }
        try {
            $json = fileRead($filePath);
        } catch (Exception $e) {
            return null;
        }
        $data = json_decode($json, true);
        if ($data === null) {
            usleep(10000); // Wait 10ms for potential concurrent write to finish
        }
        $tryCount++;
    } while ($data === null && $tryCount < $retryCount);

    return $data;
}
//------------------
function fileEnsureExists(string $filePath, string $contents = ''): void
{
    if (!file_exists($filePath)) {
        fileWrite($contents, $filePath);
    }
}
//------------------
function fileGetInfo(string $filePath): array
{
    checkFileExists($filePath);
    $info['filePath'] = $filePath;
    $info['fileSize'] = (int) filesize($filePath);
    $info['fileModifiedTime'] = (int) filemtime($filePath);
    return $info;
}
//------------------
function fileGetInfoTest(): void
{
    testFilesSetUp();

    $testData = testDirName() . 'testData.txt';
    $fileInfo = fileGetInfo($testData);
    fileDelete($testData);
    test(arrayGet($fileInfo, 'fileSize') == 3, 'size test');
    arrayGet($fileInfo, 'fileModifiedTime');
    testPassed('dateTime test');
}
//------------------
function fileFind(
    string $pathMask,
    bool $searchSubDirs = false,
    bool $includeDirs = false,
    bool $includeFiles = true
): array {
    errIfNullOrEmptyStr($pathMask, 'null mask not allowed');

    $fileList = glob($pathMask, GLOB_MARK | GLOB_ERR);
    errIf($fileList === false, 'Err reading filelist');

    $processedFileList = [];
    foreach ($fileList as $dirOrFilePath) {
        if ((is_file($dirOrFilePath) && $includeFiles) || (is_dir($dirOrFilePath) && $includeDirs)) {
            $processedFileList[] = $dirOrFilePath;
        }
    }

    if ($searchSubDirs) {
        $dir = fileExtractDir($pathMask);
        $subDirList = glob($dir . '*', GLOB_ONLYDIR | GLOB_MARK | GLOB_ERR);
        if ($subDirList !== false) {
            $fileNameWithExt = fileExtractFileNameWithExt($pathMask);
            foreach ($subDirList as $subDir) {
                $subMask = $subDir . $fileNameWithExt;
                $subList = fileFind($subMask, $searchSubDirs, $includeDirs, $includeFiles);
                $processedFileList = array_merge($processedFileList, $subList);
            }
        }
    }

    return $processedFileList;
}
//------------------
function fileFindTest(): void
{
    testFilesSetUp();
    $fileList = fileFind(testDirName() . '*.ini');
    test(count($fileList) === 1, 'found test');
    test($fileList[0] == testDirName() . 'test.ini', 'basic test');
}
//------------------
function fileFindInfo(
    string $pathMask,
    bool $searchSubDirs = false,
    bool $includeDirs = false,
    bool $includeFiles = true
): array {
    $fileList = fileFind($pathMask, $searchSubDirs, $includeDirs, $includeFiles);

    return fileListToFileInfoList($fileList);
}
//------------------
function fileFindInfoTest(): void
{
    testFilesSetUp();
    $fileInfoList = fileFindInfo(testDirName() . '*.ini');
    test(count($fileInfoList) === 1, 'found test');
    test(arrayGet($fileInfoList[0], 'filePath') == testDirName() . 'test.ini', 'basic test');
    test(arrayGet($fileInfoList[0], 'fileSize') > 0, 'basic test');
}
//------------------
function fileListToFileInfoList(array $fileList, bool $skipOnError = false): array
{
    errIfNull($fileList, 'null file list not allowed');
    $fileInfoList = [];
    foreach ($fileList as $filePath) {
        try {
            $fileInfoList[] = fileGetInfo($filePath);
        } catch (Exception $e) {
            if ($skipOnError) {
                continue;
            }

            throw $e;
        }
    }

    return $fileInfoList;
}
//------------------
function fileListToFileInfoListTest(): void
{
    testFilesSetUp();
    $fileList = fileFind(testDirName() . '*.ini');
    $fileInfoList = fileListToFileInfoList($fileList);
    test(count($fileInfoList) === 1, 'found test');
    test(arrayGet($fileInfoList[0], 'filePath') == testDirName() . 'test.ini', 'basic test');
    test(arrayGet($fileInfoList[0], 'fileSize') > 0, 'basic test');
}
//---------------------------------------
function fileListGetOldBytes(array $fileList, int $oldByteCount): array
{
    errIfNull($fileList, 'file list may not be null');
    errIfNull($oldByteCount, 'old byte count may not be null');
    $oldBytes = [];
    if (count($fileList) == 0) {
        return $oldBytes;
    }
    $firstFile = $fileList[0];
    checkKeyExists($firstFile, 'fileSize');
    checkKeyExists($firstFile, 'fileModifiedTime');

    tableSortByColumn($fileList, 'fileModifiedTime');
    $oldBytes = array();
    $index = 0;
    while ($oldByteCount > 0 and $index < count($fileList)) {
        $file = $fileList[$index];
        $oldBytes[] = $file;
        $oldByteCount -= $file['fileSize'];
        $index++;
    }
    return $oldBytes;
}
//------------------
function fileListGetOldBytesTest(): void
{
    testFilesSetUp();
    $fileList = fileFindInfo(testDirName() . '*.*');
    $fileList = fileListGetOldBytes($fileList, 1);
    test(count($fileList) == 1, 'count files found test');
    $fileInfo = $fileList[0];
    test(arrayGet($fileInfo, 'fileSize') > 0, 'size of file test');
}
//------------------
function fileRename(string $srcPath, string $newNameAndExt, bool $overwrite = false): void
{
    $srcDir = fileExtractDir($srcPath);
    $newPath = $srcDir . $newNameAndExt;
    errIfFileExistsNoOverwrite($newPath, $overwrite);
    $success = rename($srcPath, $newPath);
    check($success, 'rename failed');
}
//------------------
function fileRenameTest(): void
{
    testFilesSetUp();
    $src = testDirName() . 'testData.txt';
    fileRename($src, 'renamed.txt');
    test(!file_exists($src), 'old file gone');
    test(file_exists(testDirName() . 'renamed.txt'), 'new file exists');
}
//------------------
function fileMove(string $srcPath, string $destDir, bool $overwrite = false): void
{
    $srcFileName = fileExtractFileNameWithExt($srcPath);
    $destPath = $destDir . $srcFileName;
    errIfFileExistsNoOverwrite($destPath, $overwrite);
    $success = rename($srcPath, $destPath);
    check($success, 'move failed');
}
//------------------
function fileMoveTest(): void
{
    testFilesSetUp();
    $src = testDirName() . 'testData.txt';
    $destSubDir = testDirName() . 'sub' . DIRECTORY_SEPARATOR;
    dirEnsureExists($destSubDir);
    fileMove($src, $destSubDir);
    test(!file_exists($src), 'old file gone');
    test(file_exists($destSubDir . 'testData.txt'), 'moved file exists');
}
//------------------
function fileDelete(string $filePath): void
{
    checkFileExists($filePath);
    $success = unlink($filePath);
    errIf($success === false, "delete of $filePath failed");
}
//------------------
function fileDeleteTest(): void
{
    testFilesSetUp();

    $fileToDeletePath = testDirName() . 'testData.txt';
    fileDelete($fileToDeletePath);
    test(file_exists($fileToDeletePath) == false, 'delete file test');
    try {
        fileDelete('notFound.txt');
    } catch (Exception $e) {
        testCorrectExceptionThrown($e, cEFileNotFound);
    }
}
//------------------
function fileListDelete(array $fileInfoList): void
{
    errIfNull($fileInfoList);
    foreach ($fileInfoList as $fileInfo) {
        if (is_array($fileInfo)) {
            $filePath = $fileInfo['filePath'];
        } else {
            $filePath = $fileInfo;
        }
        fileDelete($filePath);
    }
}
//------------------
function fileListDeleteTest(): void
{
    testFilesDelete();
    $testDir = testDirName();
    fileWrite('data', $testDir . 'file1.txt');
    fileWrite('data', $testDir . 'file2.txt');
    fileWrite('data', $testDir . 'file3.txt');
    $fileList = fileFindInfo($testDir . 'file*.*');
    check(count($fileList) == 3, 'files not created');
    fileListDelete($fileList);
    $fileList = fileFindInfo($testDir . '*.*');
    test(count($fileList) == 0, 'files deleted test');
}
//------------------
function fileListSize(array $fileList): int
{
    $size = 0;
    foreach ($fileList as $file) {
        $size += $file['fileSize'];
    }
    return $size;
}
//------------------
function fileListSizeTest(): void
{
    testFilesDelete();
    $testDir = testDirName();
    fileWrite('data', $testDir . 'file1.txt');
    fileWrite('data', $testDir . 'file2.txt');
    fileWrite('data', $testDir . 'file3.txt');
    $fileList = fileFindInfo($testDir . '*.*');
    test(fileListSize($fileList) == 12, 'basicTest');
    fileListDelete($fileList);
}
//-------------
function dirCreate(string $dir, bool $recursive = false): void
{
    $dir = dirEnsureSlash($dir);

    $result = mkdir($dir, 0777, $recursive);
    if ($result == false) {
        err('failed to make directory: ' . error_get_last()['message']);
    }
}
//------------------
function dirCreateTest(): void
{
    testFilesDelete();
    $dir = testDirName() . 'newDir';
    dirCreate($dir);
    test(is_dir($dir), 'dir created');
}
//-------------
function dirEnsureExists(string $dir, bool $recursive = false): void
{
    $dir = dirEnsureSlash($dir);
    if (!file_exists($dir)) {
        dirCreate($dir, $recursive);
    }
}
//------------------
function dirEnsureExistsTest(): void
{
    testFilesDelete();
    $dir = testDirName() . 'ensureDir';
    dirEnsureExists($dir);
    test(is_dir($dir), 'dir ensured');
    dirEnsureExists($dir); // Should not fail if already exists
    test(is_dir($dir), 'dir still there');
}
//-------------
function dirEnsureSlash(string $dir): string
{
    if (str_ends_with($dir, '/') || str_ends_with($dir, '\\')) {
        return $dir;
    } else {
        return $dir . DIRECTORY_SEPARATOR;
    }
}
//------------------
function dirEnsureSlashTest(): void
{
    test(str_ends_with(dirEnsureSlash('test'), DIRECTORY_SEPARATOR), 'appends slash');
    test(dirEnsureSlash('test/') === 'test/', 'keeps slash');
    test(dirEnsureSlash('test\\') === 'test\\', 'keeps backslash');
}
//-----------------------------------------
function filePtrsForEachLine($inputHandle, $outputHandle, callable $lineFunction): void
{
    $line = fgets($inputHandle, 10000);
    while ($line !== false) {
        $line = $lineFunction($line);
        fwrite($outputHandle, $line);
        $line = fgets($inputHandle, 10000);
    }
}
//-----------------------------------------
function fileForEachLine(string $filePath, callable $lineFunction): void
{
    $tempPath = fileExtractPathNoExt($filePath) . '.temp';
    $inputHandle = @fopen($filePath, "r");
    $outputHandle = @fopen($tempPath, "w");
    errIf($inputHandle == false, "cannot open file $filePath");

    filePtrsForEachLine($inputHandle, $outputHandle, $lineFunction);
    check(feof($inputHandle), "read in $filePath failed");
    fclose($inputHandle);
    fclose($outputHandle);
    fileDelete($filePath);
    $fileName = fileExtractFileNameWithExt($filePath);
    fileRename($tempPath, $fileName);
}
//------------------
function fileForEachLineTest(): void
{
    testFilesSetUp();
    $path = testDirName() . 'testData.txt';
    fileWrite("a\nb\nc", $path);
    fileForEachLine($path, function ($line) {
        return strtoupper((string) $line);
    });
    test(fileRead($path) === "A\nB\nC", 'transformed lines');
}
//-----------------------------------------
function fileNormalizeLB(string $filePath): void
{
    fileForEachLine($filePath, 'strLineNormalizeLB');
}
//------------------
function fileNormalizeLBTest(): void
{
    testFilesSetUp();
    $path = testDirName() . 'testLb.txt';
    fileWrite("line1\r\nline2\rline3", $path);
    fileNormalizeLB($path);
    test(fileRead($path) === "line1\nline2\nline3\n", 'normalized line breaks');
}
//-----------------------------------------
function fileOpenReadPtr(string $filePath)
{
    $ptr = fopen($filePath, "r");
    errIf($ptr === false, "$filePath did not open to read.");
    return $ptr;
}
//------------------
function fileOpenReadPtrTest(): void
{
    testFilesSetUp();
    $path = testDirName() . 'testData.txt';
    $ptr = fileOpenReadPtr($path);
    test(is_resource($ptr), 'got resource');
    fclose($ptr);
}
//-----------------------------------------
function fileOpenWritePtr(string $filePath)
{
    $ptr = fopen($filePath, "w");
    errIf($ptr === false, "$filePath did not open to write");
    return $ptr;
}
//------------------
function fileOpenWritePtrTest(): void
{
    testFilesDelete();
    $path = testDirName() . 'writePtr.txt';
    $ptr = fileOpenWritePtr($path);
    test(is_resource($ptr), 'got resource');
    fwrite($ptr, 'data');
    fclose($ptr);
    test(fileRead($path) === 'data', 'wrote data');
}
//-----------------------------------------
function fileCsvNormalizeForDb(string $filePath): void
{
    $tempPath = fileExtractPathNoExt($filePath) . '.temp';
    $inputHandle = fileOpenReadPtr($filePath);
    $outputHandle = fileOpenWritePtr($tempPath);

    $header = fgets($inputHandle, 10000);
    $header = csvHeaderNormalizeForDb((string) $header);
    fwrite($outputHandle, $header);

    filePtrsForEachLine($inputHandle, $outputHandle, 'strLineNormalizeLB');
    check(feof($inputHandle), "read in $filePath failed");
    fclose($inputHandle);
    fclose($outputHandle);
    fileDelete($filePath);
    $fileName = fileExtractFileNameWithExt($filePath);
    fileRename($tempPath, $fileName);
}
//-----------------------------------------
function filePtrsWriteLines($inputHandle, $outputHandle, int $lineCount = 100000): bool
{
    $count = 0;
    $line = fgets($inputHandle, 10000);
    while ($line !== false && $count < $lineCount) {
        fwrite($outputHandle, $line);
        $line = fgets($inputHandle, 10000);
        $count = $count + 1;
    }
    return feof($inputHandle);
}
//-----------------------------------------
function fileCsvNormalizeForDbBreakInPieces(string $srcPath): void
{
    $srcHandle = fileOpenReadPtr($srcPath);
    $header = fgets($srcHandle, 10000);
    $partRoot = fileExtractPathNoExt($srcPath);
    $fileNum = 0;

    while (true) {
        $line = fgets($srcHandle, 10000);
        if ($line === false) {
            break;
        }

        $fileNumPad = str_pad($fileNum . '', 5, '0', STR_PAD_LEFT);
        $partPath = $partRoot . '_P' . $fileNumPad . '.csv';
        $partHandle = fileOpenWritePtr($partPath);

        fwrite($partHandle, (string) $header);
        fwrite($partHandle, $line);

        $srcEof = filePtrsWriteLines($srcHandle, $partHandle, 99999);
        fclose($partHandle);

        if ($srcEof) {
            break;
        }
        $fileNum = $fileNum + 1;
    }
    fclose($srcHandle);
}
//------------------
function testDirName(): string
{
    $dir = fileExtractDir(__FILE__);
    $testDir = $dir . 'testFiles' . DIRECTORY_SEPARATOR;
    return $testDir;
}
//------------------
function testDirEnsureExists(): void
{
    $testDir = testDirName();
    dirEnsureExists($testDir);
}
//------------------
function testFilesDelete(): void
{
    testDirEnsureExists();
    $testDir = testDirName();
    // Search subdirs, include files, exclude dirs (we'll delete them later if needed, but fileListDelete deletes files)
    $fileList = fileFind($testDir . '*', true, false, true);
    fileListDelete($fileList);

    // Also delete any subdirs we created in tests
    $dirList = fileFind($testDir . '*', false, true, false);
    foreach ($dirList as $dir) {
        if (is_dir($dir) && $dir !== $testDir) {
            @rmdir($dir);
        }
    }
}
//------------------
function testFilesSetUp(): void
{
    testFilesDelete();
    $testIniPath = testDirName() . 'test.ini';
    $iniData =
        "fruit=apple\n" .
        "[desserts]\n" .
        "iceCream=vanilla";
    fileWrite($iniData, $testIniPath);

    $testDataPath = testDirName() . 'testData.txt';
    fileWrite('123', $testDataPath);
}
//------------------
function cacheSet(string $key, string $data, int $maxCacheSize, string $cacheDir): void
{
    errIfNullOrEmptyStr($key, 'key may not be null');
    check(is_string($data), 'data to cache must be a string');
    errIfNull($maxCacheSize, 'cache size may not be null');
    errIfNullOrEmptyStr($cacheDir, 'cacheDir may not be null');
    dirEnsureExists($cacheDir);

    $dataSize = strlen($data);
    if ($dataSize > $maxCacheSize) {
        return;
    }
    $cacheFileList = fileFindInfo($cacheDir . '*.*');
    $currentSize = fileListSize($cacheFileList);
    $projectedNeeded = $currentSize + $dataSize;
    if ($projectedNeeded > $maxCacheSize) {
        $oldByteFiles = fileListGetOldBytes($cacheFileList, $projectedNeeded - $maxCacheSize);
        fileListDelete($oldByteFiles);
    }
    $filePath = $cacheDir . cacheFileName($key);
    fileWrite($data, $filePath);
    hitForFile($filePath);
}
//------------------
function cacheSetTest(): void
{
    testFilesDelete();

    $testDir = testDirName();
    for ($i = 0; $i < 10; $i++) {
        cacheSet("$i", str_repeat("$i", 10), 110, $testDir);
    }
    $cacheFiles = fileFindInfo($testDir . '*.*');

    $count = count($cacheFiles);
    $size = fileListSize($cacheFiles);
    test($count == 20, 'basic count test');//cache and hit files
    test($size == 110, 'basic size test');//cache and hit file size

    cacheSet("a", str_repeat('a', 20), 110, testDirName());
    $cacheFiles = fileFindInfo($testDir . '*.*');
    $count = count($cacheFiles);
    $size = fileListSize($cacheFiles);
    test($count == 19, 'replace file count test');
    test($size == 110, 'replace file size test');

    cacheSet("tooBig", str_repeat('a', 200), 110, testDirName());
    $cacheFiles = fileFindInfo($testDir . '*.*');
    $count = count($cacheFiles);
    $size = fileListSize($cacheFiles);
    test($count == 19, 'data too big count test');
    test($size == 110, 'data too big size test');

    testFilesDelete();
}
//------------------
//---------------------------------------
function hitFile(string $filePath): int
{
    if (file_exists($filePath)) {
        $count = (int) fileRead($filePath);
        $count = $count + 1;
    } else {
        $count = 1;
    }
    fileWrite($count . '', $filePath);
    return $count;
}
//---------------------------------------
function hitForFile(string $filePath): int
{
    $hitPath = fileExtractPathNoExt($filePath) . '.hit';
    return hitFile($hitPath);
}
//---------------------------------------
function cacheGet(string $key, string $cacheDir): string|false
{
    errIfNullOrEmptyStr($key, 'key may not be null');
    errIfNullOrEmptyStr($cacheDir, 'cacheDir may not be null');

    $filePath = $cacheDir . cacheFileName($key);
    if (file_exists($filePath)) {
        hitForFile($filePath);
        touch($filePath);
        return fileRead($filePath);
    } else {
        return false;
    }
}
//---------------------------------------
function cacheGetTest(): void
{
    testFilesDelete();
    $testDir = testDirName();

    cacheSet('1', 'test data', 100, $testDir);
    $result = cacheGet('1', $testDir);
    test($result == 'test data', 'get from cache test');
    $result = cacheGet('cache key not found', $testDir);
    test($result === false, 'data not in cache test');
    testFilesDelete();
}
//-----------------------------------------
//line should be convertable to db column names. Get rid of 
//strange symbols and spaces. if there are spaces between the comma and the 
//quotes there may be problems. This code does not handle this case
function csvHeaderNormalizeForDb(string $headerStr): string
{
    $normalChar = '_';
    $headerStr = trim($headerStr);
    $headerStr = str_replace('"', '', $headerStr);
    $headerStr = strReplaceChars('~!@#$%^&*(),-+={}|[]:;<>.? ', $normalChar, $headerStr);
    $headerStr = strReplaceChars("\\\/'", $normalChar, $headerStr);
    $headerStr = strtolower($headerStr);
    $headerStr = strLineNormalizeLB($headerStr);
    return $headerStr;
}
//------------------
function csvHeaderNormalizeForDbTest(): void
{
    test(csvHeaderNormalizeForDb('"First Name", Last-Name') === "first_name__last_name\n", 'normalized header');
}
//------------------
function cacheFileName(string $keyToHash): string
{
    return hash('sha256', $keyToHash) . '.data';
}
//------------------
function cacheNewFileNameTest(): void
{
    test(cacheFileName('123') != null, 'basic test');
}
//------------------
function iniGet(string $iniFilePath, string $setting, ?string $section = null, mixed $defaultSetting = cNoDefault): mixed
{
    checkFileExists($iniFilePath);
    errIfNullOrEmptyStr($setting, 'null setting not allowed');
    if ($section == null) {
        $sections = parse_ini_file($iniFilePath);
        errIf($sections === false, "Error parsing ini file $iniFilePath");
        if (array_key_exists($setting, $sections)) {
            return $sections[$setting];
        } else if ($defaultSetting !== cNoDefault) {
            return $defaultSetting;
        }
        err("Ini setting '$setting' not found in file '$iniFilePath'");
    } else {
        $sections = parse_ini_file($iniFilePath, true);
        errIf($sections === false, "Error parsing ini file $iniFilePath");
        if (array_key_exists($section, $sections)) {
            $settings = $sections[$section];
            if (array_key_exists($setting, $settings)) {
                return $settings[$setting];
            } else if ($defaultSetting !== cNoDefault) {
                return $defaultSetting;
            }
            err("Ini setting '$setting' not found for section '$section' in file '$iniFilePath'");
        } else if ($defaultSetting !== cNoDefault) {
            return $defaultSetting;
        }
        err("Ini section '$section' not found while looking for setting '$setting' in file '$iniFilePath'");
    }
}
//------------------
function iniGetTest(): void
{
    testFilesSetUp();

    $testIniPath = testDirName() . 'test.ini';

    test(iniGet($testIniPath, 'fruit') == 'apple', 'basic test');
    test(iniGet($testIniPath, 'iceCream', 'desserts') == 'vanilla', 'section test');
    test(iniGet($testIniPath, 'sport', null, 'football') == 'football', 'default test');
    test(iniGet($testIniPath, 'sport', 'desserts', 'football') == 'football', 'default with section test');

    try {
        iniGet($testIniPath, 'sport');
        errExceptionNotThrown();
    } catch (Exception $e) {
        testExceptionThrown($e, 'key not found');
    }

    try {
        iniGet($testIniPath, 'sport', 'desserts');
        errExceptionNotThrown();
    } catch (Exception $e) {
        testExceptionThrown($e, 'key not found in section');
    }

    try {
        iniGet($testIniPath, '');
        errExceptionNotThrown();
    } catch (Exception $e) {
        testExceptionThrown($e, 'file name null');
    }

    try {
        iniGet('notFound.ini', 'fruit');
        errExceptionNotThrown();
    } catch (Exception $e) {
        testCorrectExceptionThrown($e, cEFileNotFound);
    }
}
//--------------------------------------------------------------------------
function fileExtractDir(string $filePath): string
{
    return dirEnsureSlash(pathinfo($filePath, PATHINFO_DIRNAME));
}
//------------------
function fileExtractExt(string $filePath): string
{
    return pathinfo($filePath, PATHINFO_EXTENSION);
}
//------------------
function fileExtractFileNameNoExt(string $filePath): string
{
    return pathinfo($filePath, PATHINFO_FILENAME);
}
//------------------
function fileExtractFileNameWithExt(string $filePath): string
{
    return pathinfo($filePath, PATHINFO_BASENAME);
}
//------------------
function fileExtractPathNoExt(string $filePath): string
{
    return fileExtractDir($filePath) . fileExtractFileNameNoExt($filePath);
}
//------------------
function checkFileExists(string $filePath, ?string $message = null): void
{
    check(file_exists($filePath), $message ?? "file not found", cEFileNotFound);
}
//------------------
function checkFileExistsTest(): void
{
    testFilesSetUp();

    $testDataPath = testDirName() . 'testData.txt';
    checkFileExists($testDataPath);
    testPassed('basicTest');
    try {
        checkFileExists('notFound.txt');
        errExceptionNotThrown();
    } catch (Exception $e) {
        testCorrectExceptionThrown($e, cEFileNotFound);
    }
}
//------------------
function echoAndLog(string $string, string $logFilePath = 'log.txt'): void
{
    echoLine($string);
    fileLog($string, $logFilePath);
}
//---------------------------------------
function zFilesTest(): void
{
    echoLineBlockStart('zFilesTest');

    checkFileExistsTest();
    fileDeleteTest();
    fileWriteTest();
    fileAppendTest();
    fileRenameTest();
    fileMoveTest();
    fileLogTest();
    fileGetInfoTest();
    fileFindTest();
    fileListToFileInfoListTest();
    fileFindInfoTest();
    fileListGetOldBytesTest();
    fileListDeleteTest();
    fileListSizeTest();
    dirCreateTest();
    dirEnsureExistsTest();
    dirEnsureSlashTest();
    fileForEachLineTest();
    fileNormalizeLBTest();
    fileOpenReadPtrTest();
    fileOpenWritePtrTest();
    cacheSetTest();
    cacheGetTest();
    cacheNewFileNameTest();
    iniGetTest();
    csvHeaderNormalizeForDbTest();

    echoLineBlockFinish('zFilesTest');
}

