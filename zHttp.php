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
require_once dirname(__FILE__) . '/zFiles.php';
//------------------
define('cHttpDefaultTimeout', 30);

//------------------
function httpServerIp(): string
{
    $val = (string) arrayGet($_SERVER, 'SERVER_ADDR');
    errIfNullOrEmptyStr($val, 'SERVER_ADDR empty');
    return $val;
}
//------------------
function httpServerHost(): string
{
    $val = (string) arrayGet($_SERVER, 'HTTP_HOST');
    errIfNullOrEmptyStr($val, 'HTTP_HOST empty');
    return $val;
}
//------------------
function httpRemoteIp(): string
{
    $remoteAddr = (string) arrayGet($_SERVER, 'REMOTE_ADDR');
    errIfNullOrEmptyStr($remoteAddr, 'REMOTE_ADDR empty');
    if ($remoteAddr === '::1') {
        $remoteAddr = 'localhost';
    }
    return $remoteAddr;
}
//------------------
function httpGetByFileGetContents(string $url): string
{
    errIfNullOrEmptyStr($url, 'null or empty url invalid');
    $contents = file_get_contents($url);
    errIf($contents === false, "failed to read url: $url");
    return $contents;
}
//------------------
function httpGetByFileContentsTest(): void
{
    $url = 'https://www.google.com';
    $result = httpGetByFileGetContents($url);
    test(stripos($result, 'Google') !== false, 'basic test');
}
//------------------
function httpGetByCurl(string $url, array $headers = [], array $options = []): string
{
    errIfNullOrEmptyStr($url);
    $handle = curl_init();
    errIf($handle === false, 'http get failed');
    try {
        arraySetNewKey($options, CURLOPT_URL, $url);
        arraySetNewKey($options, CURLOPT_HTTPHEADER, $headers);
        arraySetNewKey($options, CURLOPT_RETURNTRANSFER, true);
        arraySetNewKey($options, CURLOPT_FOLLOWLOCATION, true);
        arraySetNewKey($options, CURLOPT_TIMEOUT, cHttpDefaultTimeout);
        curl_setopt_array($handle, $options);
        $urlResult = curl_exec($handle);
        if ($urlResult === false) {
            $curlErr = curl_error($handle);
            err('{"remoteHttpError":"' . $curlErr . '"}');
        }
        $curlInfo = curl_getinfo($handle);
        $errCode = $curlInfo['http_code'];
        // TODO: Consider sending the error code in a header
        if ($errCode != 200) {
            $val = is_string($urlResult) ? $urlResult : 'null';
            filelog("remoteHttpErrorCode: $errCode, remoteValue: $val");
            err('remoteHttpErrorCode');
        }
    } finally {
        curl_close($handle);
    }
    return (string) $urlResult;
}
//------------------
function httpGetByCurlTest(): void
{
    $url = 'https://www.google.com';
    $result = httpGetByCurl($url);
    test(stripos($result, 'Google') !== false, 'basic test');
}
//------------------
function httpPostByCurl(string $url, string $dataStr, array $headers = [], array $options = []): string
{
    errIfNullOrEmptyStr($url);
    $handle = curl_init();
    errIf($handle === false, 'http post failed');
    try {
        arraySetNewKey($options, CURLOPT_URL, $url);
        arraySetNewKey($options, CURLOPT_POST, true);
        arraySetNewKey($options, CURLOPT_POSTFIELDS, $dataStr);
        arraySetNewKey($options, CURLOPT_HTTPHEADER, $headers);
        arraySetNewKey($options, CURLOPT_RETURNTRANSFER, true);
        arraySetNewKey($options, CURLOPT_FOLLOWLOCATION, true);
        arraySetNewKey($options, CURLOPT_TIMEOUT, cHttpDefaultTimeout);
        curl_setopt_array($handle, $options);

        $urlResult = curl_exec($handle);
        if ($urlResult === false) {
            $curlErr = curl_error($handle);
            err('{"remoteHttpError":"' . $curlErr . '"}');
        }
        $curlInfo = curl_getinfo($handle);
        $errCode = $curlInfo['http_code'];
        if ($errCode != 200) {
            $val = is_string($urlResult) ? $urlResult : 'null';
            err('{"remoteHttpErrorCode":' . $errCode . ',"remoteValue":' . $val . '}');
        }
    } finally {
        curl_close($handle);
    }
    return (string) $urlResult;
}
//------------------
function httpPostJsonByCurl(string $url, string $jsonStr, array $headers = [], array $options = []): string
{
    $headers[] = 'Content-Type: application/json';
    return httpPostByCurl($url, $jsonStr, $headers, $options);
}
//------------------
function httpDeleteByCurl(string $url, array $headers = [], array $options = []): string
{
    errIfNullOrEmptyStr($url);
    $handle = curl_init();
    errIf($handle === false, 'http delete failed');
    try {
        arraySetNewKey($options, CURLOPT_URL, $url);
        arraySetNewKey($options, CURLOPT_CUSTOMREQUEST, "DELETE");
        arraySetNewKey($options, CURLOPT_HTTPHEADER, $headers);
        arraySetNewKey($options, CURLOPT_RETURNTRANSFER, true);
        arraySetNewKey($options, CURLOPT_FOLLOWLOCATION, true);
        arraySetNewKey($options, CURLOPT_TIMEOUT, cHttpDefaultTimeout);
        curl_setopt_array($handle, $options);

        $urlResult = curl_exec($handle);
        if ($urlResult === false) {
            $curlErr = curl_error($handle);
            err('{"remoteHttpError":"' . $curlErr . '"}');
        }
        $curlInfo = curl_getinfo($handle);
        $errCode = $curlInfo['http_code'];
        if ($errCode != 200 && $errCode != 201 && $errCode != 202 && $errCode != 204) {
            $val = is_string($urlResult) ? $urlResult : 'null';
            err('{"remoteHttpErrorCode":' . $errCode . ',"remoteValue":' . $val . '}');
        }
    } finally {
        curl_close($handle);
    }
    return (string) $urlResult;
}
//-------------------
function httpSaveRemoteIpAsBackendIp(): void
{
    $remoteAddr = httpRemoteIp();
    $path = fileExtractDir(__FILE__) . 'backendIp.txt';
    fileWrite($remoteAddr, $path);
}
//-------------------
function backendIp(): string
{
    $path = fileExtractDir(__FILE__) . 'backendIp.txt';
    return fileRead($path);
}
//---------------------------------------
//---------------------------------------
function hitForIp(string $hitDir): int
{
    errIfNullOrEmptyStr($hitDir);
    $hitDir = dirEnsureSlash($hitDir);
    dirEnsureExists($hitDir);
    $remoteIp = httpRemoteIp();
    $hitPath = $hitDir . $remoteIp . '.hit';
    return hitFile($hitPath);
}
//------------------
function httpErr(Throwable $exception): void
{
    http_response_code(400);

    $errMessage = exceptionToReadable($exception);
    echoLine($errMessage);

    $errMessage = exceptionAndBacktraceToReadable($exception);
    fileLog($errMessage);
}
//------------------
function exceptionHandlerHttpErr(): void
{
    set_exception_handler('httpErr');
}
//------------------
function zHttpTest(): void
{
    echoLineBlockStart('zHttpTest');
    httpGetByFileContentsTest();
    httpGetByCurlTest();
    echoLineBlockFinish('zHttpTest');
}

exceptionHandlerHttpErr();
