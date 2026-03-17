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

$gTimeZoneIsSet = false;
//------------------
//I need to see the zone it hold it during a temp switch
//------------------
function dateTimeGetZone(): string
{
    global $gTimeZoneIsSet;
    check($gTimeZoneIsSet === true, 'Time zone not set. Call dateTimeSetZone');
    return date_default_timezone_get();
}
//------------------
function dateTimeGetZoneTest(): void
{
    test(dateTimeGetZone() != null, 'basic test');
}
//------------------
function dateTimeSetZone(string $timeZoneStr): void
{
    global $gTimeZoneIsSet;
    $result = date_default_timezone_set($timeZoneStr);
    errIf($result === false, 'timeZoneStr invalid');
    $gTimeZoneIsSet = true;
}
//------------------
function dateTimeSetZoneTest(): void
{
    $currTimeZoneStr = dateTimeGetZone();
    dateTimeSetZone($currTimeZoneStr);
    testPassed('basic test');
}
//------------------
function dateTimeStrForZone(string $timeZone): string
{
    $oldTimeZone = dateTimeGetZone();
    dateTimeSetZone($timeZone);
    try {
        return date(cDateTimeStrFormat);
    } finally {
        dateTimeSetZone($oldTimeZone);
    }
}
//------------------
function dateTimeStrLocal(): string
{
    return date(cDateTimeStrFormat);
}
//------------------
function dateTimeStrLocalTest(): void
{
    testEchoOutput('Local date time ' . dateTimeStrLocal());
    test(dateTimeStrLocal() != null, 'basic test');
}
//-----------------------------------------
function dateTimeUnixAlterHourMinSec(int $unix, int $hour, int $minute, int $second, string $timeZone): int
{
    $oldTimezone = dateTimeGetZone();
    dateTimeSetZone($timeZone);
    try {
        $parced = dateTimeUnixToParsed($unix, $timeZone);
        return mktime($hour, $minute, $second, $parced['mon'], $parced['mday'], $parced['year']);
    } finally {
        dateTimeSetZone($oldTimezone);
    }
}
//------------------
function dateTimeUnixAlterHourMinSecTest(): void
{
    test(dateTimeUnixAlterHourMinSec(0, 0, 0, 0, cTimeZoneUtc) == 0, 'basic test');
    test(dateTimeUnixAlterHourMinSec(cTimeSecsPerDay, 1, 1, 1, cTimeZoneUtc) ==
        cTimeSecsPerDay + cTimeSecsPerHour + cTimeSecsPerMin + 1, 'day test');
}
//------------------
function dateTimeUnixToFormatted(int $timeUnix, string $timeZone, string $format, string $unit = 'seconds'): string
{
    $unit = strtolower($unit);
    check(
        in_array($unit, ['seconds', 'millis', 'micros', 'nanos'], true),
        "time unit $unit invalid"
    );

    if ($unit === 'nanos') {
        $timeUnix = $timeUnix / 1_000_000_000;
    } else if ($unit === 'micros') {
        $timeUnix = $timeUnix / 1_000_000;
    } else if ($unit === 'millis') {
        $timeUnix = $timeUnix / 1_000;
    }

    // Keep conversion at whole-second resolution without negative drift.
    if (is_float($timeUnix)) {
        if ($timeUnix >= 0) {
            $timeUnix = floor($timeUnix);
        } else {
            $timeUnix = ceil($timeUnix);
        }
    }
    $timeUnix = (int) $timeUnix;

    $holdZone = dateTimeGetZone();
    dateTimeSetZone($timeZone);
    try {
        return date($format, $timeUnix);
    } finally {
        dateTimeSetZone($holdZone);
    }
}
//------------------
function dateTimeUnixToFormattedTest(): void
{
    test(dateTimeUnixToFormatted(0, cTimeZoneMountain, cDateTimeStrFormat) != null, 'basic test');
    test(dateTimeUnixToFormatted(0, cTimeZoneEastern, cDateStrFormat) == '1969-12-31', 'test eastern date');
    test(dateTimeUnixToFormatted(0, cTimeZoneMountain, cDateStrFormat) == '1969-12-31', 'test mountain date');
    test(dateTimeUnixToFormatted(0, cTimeZoneEastern, cDateTimeStrFormat) == '1969-12-31 19:00:00', 'test eastern date time');
    test(dateTimeUnixToFormatted(0, cTimeZoneMountain, cDateTimeStrFormat) == '1969-12-31 17:00:00', 'test mountain date time');

    test(dateTimeUnixToFormatted(1609459200, cTimeZoneUtc, cDateStrFormat) == '2021-01-01', 'seconds test');
    test(dateTimeUnixToFormatted(1609459200 * 1000, cTimeZoneUtc, cDateStrFormat, 'millis') == '2021-01-01', 'milliseconds test');
    test(dateTimeUnixToFormatted(1609459200 * 1000000, cTimeZoneUtc, cDateStrFormat, 'micros') == '2021-01-01', 'microseconds test');
    test(dateTimeUnixToFormatted(1609459200 * 1000000000, cTimeZoneUtc, cDateStrFormat, 'nanos') == '2021-01-01', 'nanoseconds test');
    test(dateTimeUnixToFormatted(-1609459200123, cTimeZoneUtc, cDateStrFormat, 'millis') == '1919-01-01', 'negative milliseconds test');
    test(dateTimeUnixToFormatted(-1609459200123000, cTimeZoneUtc, cDateStrFormat, 'micros') == '1919-01-01', 'negative microseconds test');
    test(dateTimeUnixToFormatted(-1609459200123000000, cTimeZoneUtc, cDateStrFormat, 'nanos') == '1919-01-01', 'negative nanoseconds test');
    test(dateTimeUnixToFormatted(1609459200, cTimeZoneUtc, cDateStrFormat, 'seconds') == '2021-01-01', 'explicit seconds unit test');
    test(dateTimeUnixToFormatted(1609459200000, cTimeZoneUtc, cDateStrFormat, 'MILLIS') == '2021-01-01', 'millis case-insensitive test');
    test(dateTimeUnixToFormatted(1609459200000000, cTimeZoneUtc, cDateStrFormat, 'MICROS') == '2021-01-01', 'micros case-insensitive test');
    test(dateTimeUnixToFormatted(1609459200000000000, cTimeZoneUtc, cDateStrFormat, 'NANOS') == '2021-01-01', 'nanos case-insensitive test');
    test(dateTimeUnixToFormatted(1609459200, cTimeZoneUtc, cDateStrFormat, 'SECONDS') == '2021-01-01', 'seconds case-insensitive test');

    try {
        dateTimeUnixToFormatted(1609459200, cTimeZoneUtc, cDateStrFormat, 'mins');
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testExceptionThrown($e, 'invalid unit throws');
    }
}
//------------------
function dateTimeUnixToFormattedAuto(int|float $timeUnix, string $timeZone, string $format): string
{
    //must be checked biggest numbers to smallest
    $absTime = abs($timeUnix);
    if ($absTime > 10_000_000_000_000_000) { //  16 zeros year 2286
        $unit = 'nanos';                            //  1/1_000_000_000 billionth of a second
    } else if ($absTime > 10_000_000_000_000) {     //  13 zeros year 2286 
        $unit = 'micros';                           //  1/1_000_000 millionth of a second
    } else if ($absTime > 10_000_000_000) {         //  10 zeros year 2286 
        $unit = 'millis';                           //  1/1000 thousandth of a second
    } else {
        $unit = 'seconds';                          //  whole seconds
    }

    return dateTimeUnixToFormatted($timeUnix, $timeZone, $format, $unit);
}
//------------------
function dateTimeUnixToFormattedAutoTest(): void
{
    test(dateTimeUnixToFormattedAuto(0, cTimeZoneMountain, cDateTimeStrFormat) != null, 'basic test');
    test(dateTimeUnixToFormattedAuto(0, cTimeZoneEastern, cDateStrFormat) == '1969-12-31', 'test eastern date');
    test(dateTimeUnixToFormattedAuto(0, cTimeZoneMountain, cDateStrFormat) == '1969-12-31', 'test mountain date');
    test(dateTimeUnixToFormattedAuto(0, cTimeZoneEastern, cDateTimeStrFormat) == '1969-12-31 19:00:00', 'test eastern date time');
    test(dateTimeUnixToFormattedAuto(0, cTimeZoneMountain, cDateTimeStrFormat) == '1969-12-31 17:00:00', 'test mountain date time');

    test(dateTimeUnixToFormattedAuto(1609459200, cTimeZoneUtc, cDateStrFormat) == '2021-01-01', 'seconds test');
    test(dateTimeUnixToFormattedAuto(1609459200 * 1000, cTimeZoneUtc, cDateStrFormat) == '2021-01-01', 'milliseconds test');
    test(dateTimeUnixToFormattedAuto(1609459200 * 1000000, cTimeZoneUtc, cDateStrFormat) == '2021-01-01', 'microseconds test');
    test(dateTimeUnixToFormattedAuto(1609459200 * 1000000000, cTimeZoneUtc, cDateStrFormat) == '2021-01-01', 'nanoseconds test');
    test(dateTimeUnixToFormattedAuto(-1609459200123, cTimeZoneUtc, cDateStrFormat) == '1919-01-01', 'negative milliseconds test');
    test(dateTimeUnixToFormattedAuto(-1609459200123000, cTimeZoneUtc, cDateStrFormat) == '1919-01-01', 'negative microseconds test');
    test(dateTimeUnixToFormattedAuto(-1609459200123000000, cTimeZoneUtc, cDateStrFormat) == '1919-01-01', 'negative nanoseconds test');
}
//------------------
function dateTimeUnix(): int
{
    return time();
}
//------------------
function dateTimeUnixTest(): void
{
    testEchoOutput(time());
}
//------------------
function dateTimeUnixToDateStr(int|float $timeUnix, string $timeZone): string
{
    return dateTimeUnixToFormatted($timeUnix, $timeZone, cDateStrFormat);
}
//------------------
function dateTimeUnixToDateStrTest(): void
{
    test(
        dateTimeUnixToDateStr(0, cTimeZoneMountain) != null,
        'basic test'
    );
    test(dateTimeUnixToDateStr(0, cTimeZoneUtc) ==
        '1970-01-01', 'eastern test date time');
    test(dateTimeUnixToDateStr(0, cTimeZoneEastern) ==
        '1969-12-31', 'eastern test date time');
    test(dateTimeUnixToDateStr(0, cTimeZoneMountain) ==
        '1969-12-31', 'mountain test date time');
}
//------------------
function dateTimeUnixToTimeStr(int|float $timeUnix, string $timeZone): string
{
    return dateTimeUnixToFormatted($timeUnix, $timeZone, cTimeStrFormat);
}
//------------------
function dateTimeUnixToTimeStrTest(): void
{
    test(
        dateTimeUnixToTimeStr(0, cTimeZoneUtc) != null,
        'basic test'
    );
    test(dateTimeUnixToTimeStr(0, cTimeZoneUtc) ==
        '00:00:00', 'UTC test');
    test(dateTimeUnixToTimeStr(0, cTimeZoneEastern) ==
        '19:00:00', 'eastern test date time');
    test(dateTimeUnixToTimeStr(0, cTimeZoneMountain) ==
        '17:00:00', 'mountain test date time');
}
//------------------
function dateTimeUnixToDateTimeStr(int|float $timeUnix, string $timeZone): string
{
    return dateTimeUnixToFormatted($timeUnix, $timeZone, cDateTimeStrFormat);
}
//------------------
function dateTimeUnixToDateTimeStrTest(): void
{
    test(
        dateTimeUnixToDateTimeStr(0, cTimeZoneMountain) != null,
        'basic test'
    );
    test(dateTimeUnixToDateTimeStr(0, cTimeZoneUtc) ==
        '1970-01-01 00:00:00', 'UTC test date time');
    test(dateTimeUnixToDateTimeStr(0, cTimeZoneEastern) ==
        '1969-12-31 19:00:00', 'eastern test date time');
    test(dateTimeUnixToDateTimeStr(0, cTimeZoneMountain) ==
        '1969-12-31 17:00:00', 'mountain test date time');
}
//------------------
function dateTimeUnixToIsoUtc(int $unixTime): string
{
    $oldTimezone = dateTimeGetZone();
    dateTimeSetZone(cTimeZoneUtc);
    try {
        return date('Y-m-d\TH:i:s\Z', $unixTime);
    } finally {
        dateTimeSetZone($oldTimezone);
    }
}
//------------------
function dateTimeUnixToIsoUtcTest(): void
{
    $oldTimezone = dateTimeGetZone();
    test(dateTimeUnixToIsoUtc(0) == '1970-01-01T00:00:00Z', 'basic test');
    test(
        dateTimeGetZone() == $oldTimezone,
        "timezone " . dateTimeGetZone() . " not restored correctly to $oldTimezone"
    );
}
//------------------
function dateTimeUnixToParsed(int $timeUnix, string $timeZone): array
{
    $oldTimeZone = dateTimeGetZone();
    dateTimeSetZone($timeZone);
    try {
        $result = getdate($timeUnix);
    } finally {
        dateTimeSetZone($oldTimeZone);
    }

    return $result;
}
//------------------
function dateTimeUnixToParsedTest(): void
{
    $oldTimezone = dateTimeGetZone();

    $results = dateTimeUnixToParsed(0, cTimeZoneMountain);
    test($results['year'] == 1969, 'year test');
    test($results['mon'] == 12, 'mon test');
    test($results['month'] == 'December', 'month test');
    test($results['mday'] == 31, 'mday test');
    test($results['wday'] == 3, 'wday test');
    test($results['weekday'] == 'Wednesday', 'weekday test');
    test($results['yday'] == 364, 'yday test');
    test($results['hours'] == 17, 'hours test');
    test($results['minutes'] == 0, 'minutes test');
    test($results['seconds'] == 0, 'seconds test');

    $results = dateTimeUnixToParsed(70, cTimeZoneMountain);
    test($results['year'] == 1969, 'year test2');
    test($results['mon'] == 12, 'mon test2');
    test($results['month'] == 'December', 'month test2');
    test($results['mday'] == 31, 'mday test2');
    test($results['wday'] == 3, 'wday test2');
    test($results['weekday'] == 'Wednesday', 'weekday test2');
    test($results['yday'] == 364, 'yday test2');
    test($results['hours'] == 17, 'hours test2');
    test($results['minutes'] == 1, 'minutes test2');
    test($results['seconds'] == 10, 'seconds test2');
    test(
        dateTimeGetZone() == $oldTimezone,
        "timezone " . dateTimeGetZone() . " not restored correctly to $oldTimezone"
    );

    test(
        dateTimeGetZone() == $oldTimezone,
        "timezone " . dateTimeGetZone() . " not restored correctly to $oldTimezone"
    );
}
//------------------
function dateTimeUnixExtractYear(int $timeUnix, string $timeZone): int
{
    return dateTimeUnixToParsed($timeUnix, $timeZone)['year'];
}
//------------------
function dateTimeUnixExtractMonth(int $timeUnix, string $timeZone): int
{
    return dateTimeUnixToParsed($timeUnix, $timeZone)['mon'];
}
//------------------
function dateTimeUnixExtractMonthName(int $timeUnix, string $timeZone): string
{
    return dateTimeUnixToParsed($timeUnix, $timeZone)['month'];
}
//------------------
function dateTimeUnixExtractDayOfMonth(int $timeUnix, string $timeZone): int
{
    return dateTimeUnixToParsed($timeUnix, $timeZone)['mday'];
}
//------------------
function dateTimeUnixExtractDayOfWeek(int $timeUnix, string $timeZone): int
{
    return dateTimeUnixToParsed($timeUnix, $timeZone)['wday'];
}
//------------------
function dateTimeUnixIsWeekEnd(int $timeUnix, string $timeZone): bool
{
    $dayOfWeek = dateTimeUnixExtractDayOfWeek($timeUnix, $timeZone);
    $isSaturday = $dayOfWeek == 6;
    $isSunday = $dayOfWeek == 0;
    return $isSaturday || $isSunday;
}
//------------------
function dateTimeUnixIsWeekDay(int $timeUnix, string $timeZone): bool
{
    return !dateTimeUnixIsWeekEnd($timeUnix, $timeZone);
}
//------------------
function dateTimeUnixExtractDayName(int $timeUnix, string $timeZone): string
{
    return dateTimeUnixToParsed($timeUnix, $timeZone)['weekday'];
}
//------------------
function dateTimeUnixExtractHours(int $timeUnix, string $timeZone): int
{
    return dateTimeUnixToParsed($timeUnix, $timeZone)['hours'];
}
//------------------
function dateTimeUnixExtractMinutes(int $timeUnix, string $timeZone): int
{
    return dateTimeUnixToParsed($timeUnix, $timeZone)['minutes'];
}
//------------------
function dateTimeUnixExtractSeconds(int $timeUnix, string $timeZone): int
{
    return dateTimeUnixToParsed($timeUnix, $timeZone)['seconds'];
}
//------------------
function dateTimeUnixToYearNumTest(): void
{
    test(dateTimeUnixExtractYear(0, cTimeZoneUtc) ==
        1970, 'basic test');
    test(dateTimeUnixExtractYear(0, cTimeZoneEastern) ==
        1969, 'eastern test date time');
    test(dateTimeUnixExtractYear(0, cTimeZoneMountain) ==
        1969, 'mountain test date time');
}
//------------------
//Need to convert human input to unix
//------------------
function dateTimeStrIsIsoUtc(string $isoStr): bool
{// 2024-07-12T02:48:52.339261Z
    if (!is_string($isoStr)) {
        return false;
    }

    $matched = preg_match(
        '/^(\d{4})-(\d{2})-(\d{2})T' .
        '(\d{2}):(\d{2}):(\d{2})' .
        '(\.\d+)?Z$/',
        $isoStr,
        $parts
    );
    if ($matched !== 1) {
        return false;
    }

    $year = intval($parts[1]);
    $month = intval($parts[2]);
    $day = intval($parts[3]);
    $hour = intval($parts[4]);
    $minute = intval($parts[5]);
    $second = intval($parts[6]);

    if (!checkdate($month, $day, $year)) {
        return false;
    }
    if ($hour < 0 || $hour > 23) {
        return false;
    }
    if ($minute < 0 || $minute > 59) {
        return false;
    }
    if ($second < 0 || $second > 59) {
        return false;
    }

    return true;
}
//------------------
function dateTimeStrIsIsoUtcTest(): void
{
    test(dateTimeStrIsIsoUtc('1970-01-01T00:00:00Z') === true, 'basic iso test');
    test(dateTimeStrIsIsoUtc('2024-07-12T02:48:52.339261Z') === true, 'fractional seconds test');
    test(dateTimeStrIsIsoUtc('2024-02-29T23:59:59Z') === true, 'leap year date test');

    test(dateTimeStrIsIsoUtc('2024-02-31T00:00:00Z') === false, 'invalid date rejected');
    test(dateTimeStrIsIsoUtc('2024-01-01T24:00:00Z') === false, 'invalid hour rejected');
    test(dateTimeStrIsIsoUtc('2024-01-01T00:60:00Z') === false, 'invalid minute rejected');
    test(dateTimeStrIsIsoUtc('2024-01-01T00:00:60Z') === false, 'invalid second rejected');
    test(dateTimeStrIsIsoUtc('2024-01-01T00:00:00+00:00') === false, 'offset format rejected');
    test(dateTimeStrIsIsoUtc('xxxxxxxxxxxxxxxxxxxZ') === false, 'nonsense rejected');
}
//------------------
function isDateTimeStr(string $dateStr): bool
{
    if (dateTimeStrIsIsoUtc($dateStr)) {
        return true;
    }

    // Match YYYY-MM-DD [HH:MM:SS]
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?: (\d{2}):(\d{2}):(\d{2}))?$/', $dateStr, $parts) !== 1) {
        return false;
    }

    $year = (int) $parts[1];
    $month = (int) $parts[2];
    $day = (int) $parts[3];

    if (!checkdate($month, $day, $year)) {
        return false;
    }

    if (isset($parts[4])) {
        $hour = (int) $parts[4];
        $min = (int) $parts[5];
        $sec = (int) $parts[6];
        if ($hour < 0 || $hour > 23 || $min < 0 || $min > 59 || $sec < 0 || $sec > 59) {
            return false;
        }
    }

    return true;
}
//------------------
function isDateTimeStrTest(): void
{
    test(isDateTimeStr('2024-02-29') === true, 'valid leap date');
    test(isDateTimeStr('2024-07-12') === true, 'valid date');
    test(isDateTimeStr('2024-07-12T02:48:52.339261Z') === true, 'valid ISO UTC date time');
    test(isDateTimeStr('2024-07-12 00:00:00') === true, 'local datetime format accepted');

    test(isDateTimeStr('2024-02-31') === false, 'invalid day rejected');
    test(isDateTimeStr('abcd-ef-gh') === false, 'nonsense rejected');
    test(isDateTimeStr('2024-7-1') === false, 'non padded date rejected');
    test(isDateTimeStr('2024-01-01 24:00:00') === false, 'invalid hour rejected');
}
//------------------time strings to unix are allowed to be many formats
function dateTimeStrToUnix(string $dateTimeStr, string $timeZone): int
{
    checkIsDateStrConvertable($dateTimeStr);

    $oldTimezone = dateTimeGetZone();
    dateTimeSetZone($timeZone);
    try {
        $result = strtotime($dateTimeStr);
    } finally {
        dateTimeSetZone($oldTimezone);
    }

    errIf($result === false, 'dateTimeStrToUnix failed');

    return $result;
}
//------------------
function dateTimeStrToUnixTest(): void
{
    $oldTimezone = dateTimeGetZone();
    test(
        dateTimeStrToUnix('1970-01-01', cTimeZoneUtc) == 0,
        'zero test'
    );
    test(
        dateTimeGetZone() == $oldTimezone,
        "timezone " . dateTimeGetZone() . " not restored correctly to $oldTimezone"
    );
    test(
        dateTimeStrToUnix('1970-01-01', cTimeZoneEastern) == 18000,
        'zero eastern test'
    );
    test(
        dateTimeGetZone() == $oldTimezone,
        "timezone " . dateTimeGetZone() . " not restored correctly to $oldTimezone"
    );
    test(
        dateTimeStrToUnix('1970-01-01 00:00:01', cTimeZoneUtc) == 1,
        '1 second test'
    );
    test(
        dateTimeGetZone() == $oldTimezone,
        "timezone " . dateTimeGetZone() . " not restored correctly to $oldTimezone"
    );

    try {
        dateTimeStrToUnix('not a date', cTimeZoneUtc);
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testExceptionThrown($e, 'invalid date throws');
    }
    test(
        dateTimeGetZone() == $oldTimezone,
        "timezone " . dateTimeGetZone() . " not restored correctly to $oldTimezone"
    );

}
//------------------
function dateTimeIsoUtcToUnix(string $dateTimeStr): int
{
    check(dateTimeStrIsIsoUtc($dateTimeStr), 'iso Utc time not given');
    checkIsDateStrConvertable($dateTimeStr);

    $oldTimezone = dateTimeGetZone();
    dateTimeSetZone(cTimeZoneUtc);
    try {
        $result = strtotime($dateTimeStr);
    } finally {
        dateTimeSetZone($oldTimezone);
    }

    if ($result === false) {
        err('dateTimeIsoUtcToUnix failed');
    }

    return $result;
}
//------------------
function dateTimeIsoUtcToUnixTest(): void
{
    $oldTimezone = dateTimeGetZone();
    test(
        dateTimeIsoUtcToUnix('1970-01-01T00:00:01Z') == 1,
        'IsoUtc 1 second test'
    );
    test(
        dateTimeGetZone() == $oldTimezone,
        "timezone " . dateTimeGetZone() . " not restored correctly to $oldTimezone"
    );
    try {
        dateTimeIsoUtcToUnix('1970-01-01 00:00:01');
        errExceptionNotThrown();
    } catch (Throwable $ex) {
        testExceptionThrown($ex, 'not a UTC time');
    }
    test(
        dateTimeGetZone() == $oldTimezone,
        "timezone " . dateTimeGetZone() . " not restored correctly to $oldTimezone"
    );
}
//------------------
function dateTimeStrToIsoUtc(string $dateTimeStr, string $timeZone): string
{
    $unixTime = dateTimeStrToUnix($dateTimeStr, $timeZone);
    $isoTime = dateTimeUnixToIsoUtc($unixTime);
    return $isoTime;
}
//------------------
function dateTimeStrToIsoUtcTest(): void
{
    test(
        dateTimeStrToIsoUtc(
            '1970-01-01 00:45:50',
            cTimeZoneUtc
        ) == '1970-01-01T00:45:50Z',
        'basic test'
    );
    test(
        dateTimeStrToIsoUtc(
            '1970-01-01 00:45:50',
            cTimeZoneMountain
        ) == '1970-01-01T07:45:50Z',
        'mountian time test'
    );
}
//------------------
function dateTimeIsoUtcToDateTimeStr(string $dateTimeIsoUtc, string $timeZone): string
{
    $unixTime = dateTimeIsoUtcToUnix($dateTimeIsoUtc);
    $dateTimeStr = dateTimeUnixToDateTimeStr($unixTime, $timeZone);
    return $dateTimeStr;
}
//------------------
function dateTimeIsoUtcToDateTimeStrTest(): void
{
    test(
        dateTimeIsoUtcToDateTimeStr(
            '1970-01-01T00:00:00Z',
            cTimeZoneUtc
        ) == '1970-01-01 00:00:00',
        'basic test'
    );
}
//------------------
function dateTimeUnixIsToday(int $givenUnix, string $timeZone): bool
{
    $givenDateStr = dateTimeUnixToDateStr($givenUnix, $timeZone);
    $todaysDateTime = dateTimeStrForZone($timeZone);
    $todaysDate = dateTimeStrToDateStr($todaysDateTime);
    return $givenDateStr == $todaysDate;
}
//------------------
function dateTimeStrToDateStr(string $dateTimeStr): string
{
    return strLeft(10, $dateTimeStr);
}
//------------------
function dateTimeStrToYearStr(string $dateTimeStr): string
{
    return strLeft(4, $dateTimeStr);
}
//------------------
function dateTimeStrToDateStrTest(): void
{
    test(dateTimeStrToDateStr('1970-01-01 03:50:45') == '1970-01-01', 'basic test');
}
//------------------
function dateTimeStrToTimeStr(string $dateTimeStr): string
{
    return substr($dateTimeStr, 11, 8);
}
//------------------
function dateTimeStrToTimeStrTest(): void
{
    test(dateTimeStrToTimeStr('1970-01-01 03:50:45') == '03:50:45', 'basic test');
}
//------------------
function checkIsDateStrConvertable(string $dateStr): void
{
    $result = strtotime($dateStr);
    errIf($result === false, "dateStr $dateStr is not convertable to unix time");
}
//------------------should be of the form 2024-07-12
function checkIsDateStrFormat(string $dateStr): void
{
    errIfNull($dateStr, "dateStr may not be null");
    check(strlen($dateStr) === 10, "dateStr $dateStr invalid. Should be 10 chars");
    check(
        preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr) === 1,
        "dateStr $dateStr invalid. Should be yyyy-mm-dd"
    );

    $year = (int) substr($dateStr, 0, 4);
    $month = (int) substr($dateStr, 5, 2);
    $day = (int) substr($dateStr, 8, 2);
    check(checkdate($month, $day, $year), "dateStr $dateStr invalid date");
}
//------------------
function checkIsDateStrFormatTest(): void
{
    checkIsDateStrFormat('2024-07-12');
    testPassed('valid format test');
    try {
        checkIsDateStrFormat('2024-02-31');
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testExceptionThrown($e, '31st Feb rejected');
    }

    try {
        checkIsDateStrFormat('2024-7-12');
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testExceptionThrown($e, 'non padded date rejected');
    }

    try {
        checkIsDateStrFormat('2024/07/12');
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testExceptionThrown($e, 'slash format rejected');
    }

    try {
        checkIsDateStrFormat('2024-07-123');
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testExceptionThrown($e, 'length check rejected');
    }

    try {
        checkIsDateStrFormat('2024-13-01');
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testExceptionThrown($e, 'month upper bound rejected');
    }

    try {
        checkIsDateStrFormat('2024-00-01');
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testExceptionThrown($e, 'month lower bound rejected');
    }

    try {
        checkIsDateStrFormat('2024-01-00');
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testExceptionThrown($e, 'day lower bound rejected');
    }

    try {
        checkIsDateStrFormat('2024-01-32');
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testExceptionThrown($e, 'day upper bound rejected');
    }
}
//-----------------------------------------
function dateTimeUnixRollbackToHourMinSec(int $unixTime, int $hour, int $min, int $sec, string $timeZone): int
{
    $alteredUnix = dateTimeUnixAlterHourMinSec($unixTime, $hour, $min, $sec, $timeZone);

    if ($unixTime >= $alteredUnix) {
        $result = $alteredUnix;
    } else {
        // Use local calendar rollback instead of fixed seconds for DST safety.
        $oldTimezone = dateTimeGetZone();
        dateTimeSetZone($timeZone);
        try {
            $parsed = getdate($alteredUnix);
            $result = mktime(
                $parsed['hours'],
                $parsed['minutes'],
                $parsed['seconds'],
                $parsed['mon'],
                $parsed['mday'] - 1,
                $parsed['year']
            );
        } finally {
            dateTimeSetZone($oldTimezone);
        }
    }

    return $result;
}
//------------------
function dateTimeUnixRollbackToHourMinSecTest(): void
{
    $timeZone = cTimeZoneMountain;

    $givenUnix = dateTimeStrToUnix('2024-05-15 10:00:00', $timeZone);
    $rolledUnix = dateTimeUnixRollbackToHourMinSec($givenUnix, 9, 30, 0, $timeZone);
    test(dateTimeUnixToDateTimeStr($rolledUnix, $timeZone) == '2024-05-15 09:30:00', 'same day rollback test');

    $givenUnix = dateTimeStrToUnix('2024-05-15 08:00:00', $timeZone);
    $rolledUnix = dateTimeUnixRollbackToHourMinSec($givenUnix, 9, 30, 0, $timeZone);
    test(dateTimeUnixToDateTimeStr($rolledUnix, $timeZone) == '2024-05-14 09:30:00', 'prior day rollback test');

    // DST spring-forward day: 02:00 local is normalized to 03:00.
    $givenUnix = dateTimeStrToUnix('2024-03-10 00:30:00', $timeZone);
    $rolledUnix = dateTimeUnixRollbackToHourMinSec($givenUnix, 2, 0, 0, $timeZone);
    test(dateTimeUnixToDateTimeStr($rolledUnix, $timeZone) == '2024-03-09 03:00:00', 'DST spring rollback test');

    // DST fall-back day keeps local calendar rollback without fixed-second drift.
    $givenUnix = dateTimeStrToUnix('2024-11-03 00:30:00', $timeZone);
    $rolledUnix = dateTimeUnixRollbackToHourMinSec($givenUnix, 2, 0, 0, $timeZone);
    test(dateTimeUnixToDateTimeStr($rolledUnix, $timeZone) == '2024-11-02 02:00:00', 'DST fall rollback test');
}
//------------------
function dateUnixWorkdayCountInclusive(int $startDate, int $endDate, string $timeZone): int
{
    errIf($startDate > $endDate, "startDate greater than endDate. $startDate > $endDate");

    $startDate = dateTimeUnixAlterHourMinSec($startDate, 0, 0, 0, $timeZone);
    $endDate = dateTimeUnixAlterHourMinSec($endDate, 0, 0, 0, $timeZone);

    $secondsPassed = $endDate - $startDate;
    $leftoverSeconds = $secondsPassed % cTimeSecsPerDay;
    //check($leftoverSeconds == 0, "there should be no seconds leftover $leftoverSeconds");
    //daylight saving time
    if (($leftoverSeconds == cTimeSecsPerHour)) {
        $startDate = $startDate + cTimeSecsPerHour;
        $secondsPassed = $endDate - $startDate;
        $leftoverSeconds = $secondsPassed % cTimeSecsPerDay;
    } else if ($leftoverSeconds == cTimeSecsPerHour * 23) {
        $endDate = $endDate + cTimeSecsPerHour;
        $secondsPassed = $endDate - $startDate;
        $leftoverSeconds = $secondsPassed % cTimeSecsPerDay;
    }

    check($leftoverSeconds == 0, 'Time calculation off. DST');

    $weeksPassed = $secondsPassed / cTimeSecsPerWeek;
    $wholeWeeksPassed = floor($weeksPassed);

    $startDayOfWeek = dateTimeUnixExtractDayOfWeek($startDate, $timeZone);
    $endDayOfWeek = dateTimeUnixExtractDayOfWeek($endDate, $timeZone);

    if ($wholeWeeksPassed > 0) {
        if ($startDayOfWeek <= $endDayOfWeek) {
            $inBetweenWeeksPassed = $wholeWeeksPassed - 1;
        } else {
            $inBetweenWeeksPassed = $wholeWeeksPassed;
        }

        $inBetweenWeekdaysPassed = $inBetweenWeeksPassed * 5;
        $weekdaysInFirstWeek = min(6 - $startDayOfWeek, 5);
        $weekdaysInLastWeek = min($endDayOfWeek, 5);
        return (int) ($inBetweenWeekdaysPassed + $weekdaysInFirstWeek + $weekdaysInLastWeek);
    } else if ($startDayOfWeek > $endDayOfWeek) {
        $weekdaysInFirstWeek = min(6 - $startDayOfWeek, 5);
        $weekdaysInLastWeek = min($endDayOfWeek, 5);
        return (int) ($weekdaysInFirstWeek + $weekdaysInLastWeek);
    } else {
        return (int) ((min($endDayOfWeek, 5) - max($startDayOfWeek, 1)) + 1);
    }
}
//------------------
function dateUnixWorkdayCountInclusiveTest(): void
{//inclusivity not tested
    //make this very thorugh expceiapy about if the end date is inclusive or not
    //less than a week tests
    $startDate = datetimeStrToUnix('2024-03-04', cTimeZoneMountain);//monday
    $endDate = datetimeStrToUnix('2024-03-04', cTimeZoneMountain);//monday the same day
    test(dateUnixWorkdayCountInclusive($startDate, $endDate, cTimeZoneMountain) == 1, "monday-monday same day test");

    $startDate = datetimeStrToUnix('2024-03-05', cTimeZoneMountain);//tuesday
    $endDate = datetimeStrToUnix('2024-03-05', cTimeZoneMountain);//tuesday the same day
    test(dateUnixWorkdayCountInclusive($startDate, $endDate, cTimeZoneMountain) == 1, "tuesday-tuesday same day test");

    $startDate = datetimeStrToUnix('2024-03-06', cTimeZoneMountain);//wednesday
    $endDate = datetimeStrToUnix('2024-03-06', cTimeZoneMountain);//wednesday the same day
    test(dateUnixWorkdayCountInclusive($startDate, $endDate, cTimeZoneMountain) == 1, "wednesday-wednesday same day test");

    $startDate = datetimeStrToUnix('2024-03-07', cTimeZoneMountain);//thursday
    $endDate = datetimeStrToUnix('2024-03-07', cTimeZoneMountain);//thursday the same day
    test(dateUnixWorkdayCountInclusive($startDate, $endDate, cTimeZoneMountain) == 1, "thursday-thursday same day test");

    $startDate = datetimeStrToUnix('2024-03-08', cTimeZoneMountain);//friday
    $endDate = datetimeStrToUnix('2024-03-08', cTimeZoneMountain);//friday the same day
    test(dateUnixWorkdayCountInclusive($startDate, $endDate, cTimeZoneMountain) == 1, "friday-friday same day test");

    $startDate = datetimeStrToUnix('2024-03-09', cTimeZoneMountain);//saturday
    $endDate = datetimeStrToUnix('2024-03-09', cTimeZoneMountain);//saturday the same day
    test(dateUnixWorkdayCountInclusive($startDate, $endDate, cTimeZoneMountain) == 0, "saturday-saturday same day test");

    $startDate = datetimeStrToUnix('2024-03-10', cTimeZoneMountain);//sunday
    $endDate = datetimeStrToUnix('2024-03-10', cTimeZoneMountain);//sunday the same day
    test(dateUnixWorkdayCountInclusive($startDate, $endDate, cTimeZoneMountain) == 0, "sunday-sunday same day test");

    $startDate = datetimeStrToUnix('2024-03-02', cTimeZoneMountain);//saturday
    $endDate = datetimeStrToUnix('2024-03-03', cTimeZoneMountain);//sunday
    test(dateUnixWorkdayCountInclusive($startDate, $endDate, cTimeZoneMountain) == 0, "saturday-sunday 0 workday test");

    $startDate = datetimeStrToUnix('2024-03-06', cTimeZoneMountain);//wednesday
    $endDate = datetimeStrToUnix('2024-03-07', cTimeZoneMountain);//thursday the day after
    test(dateUnixWorkdayCountInclusive($startDate, $endDate, cTimeZoneMountain) == 2, "wednesday-thursday same week test");

    $startDate = datetimeStrToUnix('2024-03-06', cTimeZoneMountain);//wednesday
    $endDate = datetimeStrToUnix('2024-03-08', cTimeZoneMountain);//friday 
    test(dateUnixWorkdayCountInclusive($startDate, $endDate, cTimeZoneMountain) == 3, "wednesday-friday same week test");

    //the following also checks the daylight savings change on 2024-03-10
    $startDate = datetimeStrToUnix('2024-03-08', cTimeZoneMountain);//friday
    $endDate = datetimeStrToUnix('2024-03-11', cTimeZoneMountain);//monday the next week
    test(dateUnixWorkdayCountInclusive($startDate, $endDate, cTimeZoneMountain) == 2, "friday-monday accross the weekend test DST spring");

    $startDate = datetimeStrToUnix('2024-03-06', cTimeZoneMountain);//wednesday
    $endDate = datetimeStrToUnix('2024-03-11', cTimeZoneMountain);//monday the next week
    test(dateUnixWorkdayCountInclusive($startDate, $endDate, cTimeZoneMountain) == 4, "wednesday-monday accross the weekend test DST spring");

    //1 week or greater but less than 2 week tests
    $startDate = datetimeStrToUnix('2024-03-06', cTimeZoneMountain);//wednesday
    $endDate = datetimeStrToUnix('2024-03-13', cTimeZoneMountain);//wednesday the next week
    test(dateUnixWorkdayCountInclusive($startDate, $endDate, cTimeZoneMountain) == 6, "wednesday-wednesday whole week test DST spring");

    $startDate = datetimeStrToUnix('2024-03-06', cTimeZoneMountain);//wednesday
    $endDate = datetimeStrToUnix('2024-03-15', cTimeZoneMountain);//friday the next week
    test(dateUnixWorkdayCountInclusive($startDate, $endDate, cTimeZoneMountain) == 8, "wednesday-friday whole week test DST spring");

    $startDate = datetimeStrToUnix('2024-03-06', cTimeZoneMountain);//wednesday
    $endDate = datetimeStrToUnix('2024-03-18', cTimeZoneMountain);//monday the next week
    test(dateUnixWorkdayCountInclusive($startDate, $endDate, cTimeZoneMountain) == 9, "wednesday-monday whole week test DST spring");

    //2 weeks or greater
    $startDate = datetimeStrToUnix('2024-02-28', cTimeZoneMountain);//wednesday
    $endDate = datetimeStrToUnix('2024-03-13', cTimeZoneMountain);//wednesday-wednesday accross 2 weeks
    test(dateUnixWorkdayCountInclusive($startDate, $endDate, cTimeZoneMountain) == 11, "wednesday-wednesday accross 2 weeks DST spring");

    $startDate = datetimeStrToUnix('2024-02-28', cTimeZoneMountain);//wednesday
    $endDate = datetimeStrToUnix('2024-03-15', cTimeZoneMountain);//wednesday-monday accross 2 weeks
    test(dateUnixWorkdayCountInclusive($startDate, $endDate, cTimeZoneMountain) == 13, "wednesday-monday accross 2 weeks DST spring");

    $startDate = datetimeStrToUnix('2024-02-28', cTimeZoneMountain);//wednesday
    $endDate = datetimeStrToUnix('2024-03-18', cTimeZoneMountain);//wednesday-monday accross 2 weeks
    test(dateUnixWorkdayCountInclusive($startDate, $endDate, cTimeZoneMountain) == 14, "wednesday-monday accross 2 weeks DST spring");

    //the following checks the daylight savings change on 2024-11-03
    $startDate = datetimeStrToUnix('2024-11-01', cTimeZoneMountain);//friday
    $endDate = datetimeStrToUnix('2024-11-04', cTimeZoneMountain);//friday-monday accross weekend DST fall
    test(dateUnixWorkdayCountInclusive($startDate, $endDate, cTimeZoneMountain) == 2, "friday-monday accross weekend DST fall");

    $startDate = datetimeStrToUnix('2024-05-14', cTimeZoneMountain);//tuesday
    $endDate = datetimeStrToUnix('2024-05-21', cTimeZoneMountain);//tuesday-tuesday accross weekend 
    test(dateUnixWorkdayCountInclusive($startDate, $endDate, cTimeZoneMountain) == 6, "tuesday-tuesday accross weekend");

    $startDate = datetimeStrToUnix('2024-05-14T05:19:05.06247Z', cTimeZoneMountain);//tuesday
    $endDate = datetimeStrToUnix('2024-05-21T03:18:05.229119Z', cTimeZoneMountain);//tuesday-tuesday accross weekend 
    test(dateUnixWorkdayCountInclusive($startDate, $endDate, cTimeZoneMountain) == 6, "special test tuesday-tuesday accross weekend");
}
//------------------
function dateUnixWorkdayCount(int $startDate, int $endDate, string $timeZone): int
{
    $daysPassed = dateUnixWorkdayCountInclusive($startDate, $endDate, $timeZone);
    if ($daysPassed == 0) {
        return 0;
    }
    return $daysPassed - 1;
}
//-----------------------------------------
function dateTimeStrNewYork(): string
{
    return dateTimeStrForZone(cTimeZoneNewYork);
}
//-----------------------------------------
function dateStrNewYork(): string
{
    return dateTimeStrToDateStr(dateTimeStrNewYork());
}
//-----------------------------------------
function timeStrNewYork(): string
{
    return dateTimeStrToTimeStr(dateTimeStrNewYork());
}
//---------------------------------------
function zDatesTest(): void
{
    echoLineBlockStart('zDateTest');
    dateTimeGetZoneTest();
    dateTimeSetZoneTest();

    dateTimeStrLocalTest();
    dateTimeUnixTest();

    dateTimeStrToIsoUtcTest();
    dateTimeStrIsIsoUtcTest();
    isDateTimeStrTest();
    checkIsDateStrFormatTest();
    dateTimeStrToUnixTest();
    dateTimeStrToDateStrTest();
    dateTimeStrToTimeStrTest();

    dateTimeIsoUtcToDateTimeStrTest();
    dateTimeIsoUtcToUnixTest();
    dateTimeUnixToDateStrTest();
    dateTimeUnixToTimeStrTest();
    dateTimeUnixToDateTimeStrTest();
    dateTimeUnixToIsoUtcTest();
    dateTimeUnixToFormattedTest();
    dateTimeUnixToFormattedAutoTest();
    dateTimeUnixToParsedTest();
    dateTimeUnixToYearNumTest();
    dateTimeUnixAlterHourMinSecTest();
    dateTimeUnixRollbackToHourMinSecTest();
    dateUnixWorkdayCountInclusiveTest();

    echoLineBlockFinish('zDateTest');
}
