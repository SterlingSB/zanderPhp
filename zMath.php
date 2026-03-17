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
//--------------------------
define('cRadFullTurn', 2 * pi());
define('cRadHalfTurn', cRadFullTurn / 2);
define('cRad3rdTurn', cRadFullTurn / 3);
define('cRad4thTurn', cRadFullTurn / 4);
define('cRad6thTurn', cRadFullTurn / 6);
define('cRad8thTurn', cRadFullTurn / 8);
define('cRad12thTurn', cRadFullTurn / 12);
define('cNumMmPerInch', 25.4);
//--------------------------
function modFloat(float $floatValue, float $wrapValue): float
{
    errIf($wrapValue === 0.0, 'modFloat: wrapValue is zero');
    $intDiv = floor($floatValue / $wrapValue);
    return $floatValue - ($intDiv * $wrapValue);
}
//--------------------------
function modFloatTest(): void
{
    test(modFloat(7.5, 3.0) === 1.5, 'basic test');
    test(modFloat(6.0, 3.0) === 0.0, 'exact multiple test');
    test(modFloat(0.0, 3.0) === 0.0, 'zero test');
    test(modFloat(1.0, 360.0) === 1.0, 'less than wrap test');
    test(modFloat(361.0, 360.0) === 1.0, 'just over wrap test');
    test(modFloat(720.0, 360.0) === 0.0, 'two full wraps test');
}
//--------------------------
function pointWhereLinesCross(
    float $lineAx1,
    float $lineAy1,
    float $lineAx2,
    float $lineAy2,
    float $lineBx1,
    float $lineBy1,
    float $lineBx2,
    float $lineBy2,
    ?float &$crossX,
    ?float &$crossY
): bool {
    // Line A represented as: A1 + t(A2 - A1)
    // Line B represented as: B1 + u(B2 - B1)

    $dxA = $lineAx2 - $lineAx1;
    $dyA = $lineAy2 - $lineAy1;

    $dxB = $lineBx2 - $lineBx1;
    $dyB = $lineBy2 - $lineBy1;

    // Determinant
    $det = $dxA * $dyB - $dyA * $dxB;

    // Parallel or coincident lines → no single intersection
    $EPS = 1e-9;
    if (abs($det) < $EPS) {
        return false;
    }

    // Solve for t
    $t = (
        ($lineBx1 - $lineAx1) * $dyB -
        ($lineBy1 - $lineAy1) * $dxB
    ) / $det;

    // Intersection point
    $crossX = $lineAx1 + $t * $dxA;
    $crossY = $lineAy1 + $t * $dyA;

    return true;
}
//--------------------------
function pointWhereLinesCrossTest(): void
{
    $crossX = null;
    $crossY = null;

    $result = pointWhereLinesCross(0, 0, 4, 4, 0, 4, 4, 0, $crossX, $crossY);
    test($result === true, 'intersecting lines returns true');
    test(abs($crossX - 2.0) < 1e-9, 'intersection X test');
    test(abs($crossY - 2.0) < 1e-9, 'intersection Y test');

    $result2 = pointWhereLinesCross(0, 0, 1, 0, 0, 1, 1, 1, $crossX, $crossY);
    test($result2 === false, 'parallel lines returns false');

    $result3 = pointWhereLinesCross(1, 0, 1, 10, 0, 3, 10, 3, $crossX, $crossY);
    test($result3 === true, 'perpendicular returns true');
    test(abs($crossX - 1.0) < 1e-9, 'perpendicular X test');
    test(abs($crossY - 3.0) < 1e-9, 'perpendicular Y test');
}
//--------------------------
class Vector
{
    private float $angle = 0;
    private float $magnitude = 0;
    private float $x = 0;
    private float $y = 0;
    //------------------------
    private function updatePolar(): void
    {
        $this->angle = atan2($this->y, $this->x);
        $this->magnitude = sqrt(($this->x * $this->x) + ($this->y * $this->y));
    }
    //------------------------
    private function updateRectangular(): void
    {
        $this->x = cos($this->angle) * $this->magnitude;
        $this->y = sin($this->angle) * $this->magnitude;
    }
    //------------------------
    function setXY(float $x, float $y): void
    {
        $this->x = $x;
        $this->y = $y;
        $this->updatePolar();
    }
    //------------------------
    function setMagnitude(float $mag): void
    {
        $this->magnitude = $mag;
        $this->updateRectangular();
    }
    //------------------------
    function setAngle(float $angle): void
    {
        $this->angle = $angle;
        $this->updateRectangular();
    }
    //------------------------
    function setPolar(float $newMagnitude, float $newAngle): void
    {
        $this->magnitude = $newMagnitude;
        $this->angle = $newAngle;
        $this->updateRectangular();
    }
    //------------------------
    function getAngle(): float
    {
        return $this->angle;
    }
    //------------------------
    function getMagnitude(): float
    {
        return $this->magnitude;
    }
    //------------------------
    function getX(): float
    {
        return $this->x;
    }
    //------------------------
    function getY(): float
    {
        return $this->y;
    }
    //------------------------
    function add(Vector $otherVec): void
    {
        $this->x += $otherVec->x;
        $this->y += $otherVec->y;
        $this->updatePolar();
    }
    //------------------------
    function subtract(Vector $otherVec): void
    {
        $this->x -= $otherVec->x;
        $this->y -= $otherVec->y;
        $this->updatePolar();
    }
    //------------------------
    function multiply(float $scalar): void
    {
        $this->x *= $scalar;
        $this->y *= $scalar;
        $this->updatePolar();
    }
    //------------------------
    function normalize(): void
    {
        if ($this->magnitude > 1e-12) {
            $this->x /= $this->magnitude;
            $this->y /= $this->magnitude;
            $this->magnitude = 1.0;
        }
    }
    //------------------------
    function dotProduct(Vector $other): float
    {
        return ($this->x * $other->getX()) + ($this->y * $other->getY());
    }
    //------------------------
    function getDistanceTo(Vector $other): float
    {
        return $this->getDistanceToXY($other->getX(), $other->getY());
    }
    //------------------------
    function getDistanceToXY(float $x, float $y): float
    {
        $dx = $this->x - $x;
        $dy = $this->y - $y;
        return sqrt(($dx * $dx) + ($dy * $dy));
    }
    //------------------------
    function copy(): Vector
    {
        $newVec = new Vector();
        $newVec->setXY($this->x, $this->y);
        return $newVec;
    }
    //------------------------
    function echoMe(): void
    {
        echoLine("X: " . $this->x);
        echoLine("Y: " . $this->y);
        echoLine("Angle: " . $this->angle);
        echoLine("Magnitude: " . $this->magnitude);
    }
}
//------------------------
function VectorTest(): void
{
    $v = new Vector();

    // Test setXY and polar updates
    $v->setXY(3.0, 4.0);
    test(floatEqual($v->getX(), 3.0), 'Vector X set correctly');
    test(floatEqual($v->getY(), 4.0), 'Vector Y set correctly');
    test(floatEqual($v->getMagnitude(), 5.0), 'Vector magnitude calculated correctly');
    test(floatEqual($v->getAngle(), atan2(4.0, 3.0)), 'Vector angle calculated correctly');

    // Test setPolar and rectangular updates
    $v->setPolar(10.0, pi() / 2); // 90 degrees
    test(floatEqual($v->getMagnitude(), 10.0), 'Vector magnitude set correctly');
    test(floatEqual($v->getAngle(), pi() / 2), 'Vector angle set correctly');
    test(floatEqual($v->getX(), 0.0), 'Vector X recalculated from polar');
    test(floatEqual($v->getY(), 10.0), 'Vector Y recalculated from polar');

    // Test arithmetic
    $v1 = new Vector();
    $v1->setXY(1, 2);
    $v2 = new Vector();
    $v2->setXY(3, 4);
    $v1->add($v2);
    test(floatEqual($v1->getX(), 4.0) && floatEqual($v1->getY(), 6.0), 'Vector addition');

    $v1->subtract($v2);
    test(floatEqual($v1->getX(), 1.0) && floatEqual($v1->getY(), 2.0), 'Vector subtraction');

    $v1->multiply(2.0);
    test(floatEqual($v1->getX(), 2.0) && floatEqual($v1->getY(), 4.0), 'Vector multiplication');

    // Test normalize
    $v1->normalize();
    test(floatEqual($v1->getMagnitude(), 1.0), 'Vector normalized magnitude');
    test(floatEqual($v1->getX(), 2.0 / sqrt(20)), 'Vector normalized X');

    // Test zero vector normalize (should handle gracefully)
    $vZero = new Vector();
    $vZero->setXY(0, 0);
    $vZero->normalize();
    test(floatEqual($vZero->getMagnitude(), 0.0), 'Zero vector normalization');

    // Test dot product
    $vDot1 = new Vector();
    $vDot1->setXY(1, 0);
    $vDot2 = new Vector();
    $vDot2->setXY(0, 1);
    test(floatEqual($vDot1->dotProduct($vDot2), 0.0), 'Dot product orthogonal');
    $vDot3 = new Vector();
    $vDot3->setXY(3, 4);
    test(floatEqual($vDot1->dotProduct($vDot3), 3.0), 'Dot product projection');

    // Test distances
    $vDist1 = new Vector();
    $vDist1->setXY(0, 0);
    $vDist2 = new Vector();
    $vDist2->setXY(3, 4);
    test(floatEqual($vDist1->getDistanceTo($vDist2), 5.0), 'Distance to vector');
    test(floatEqual($vDist1->getDistanceToXY(0, 5), 5.0), 'Distance to XY');

    // Test copy
    $vCopy = $vDist2->copy();
    test(floatEqual($vCopy->getX(), 3.0) && floatEqual($vCopy->getY(), 4.0), 'Vector copy values');
    $vCopy->setXY(10, 10);
    test(!floatEqual($vDist2->getX(), 10.0), 'Copy is deep (not reference)');
}
//----------------------------------------
function numIsOdd(int $number): bool
{
    return $number % 2 !== 0;
}
//------------------
function numIsOddTest(): void
{
    test(numIsOdd(1), '1 is odd');
    test(numIsOdd(3), '3 is odd');
    test(numIsOdd(-1), '-1 is odd');
    test(numIsOdd(-3), '-3 is odd');
    test(!numIsOdd(0), '0 is not odd');
    test(!numIsOdd(2), '2 is not odd');
    test(!numIsOdd(-2), '-2 is not odd');
}
//----------------------------------------
function numIsEven(int $number): bool
{
    return $number % 2 === 0;
}
//------------------
function numIsEvenTest(): void
{
    test(numIsEven(0), '0 is even');
    test(numIsEven(2), '2 is even');
    test(numIsEven(-2), '-2 is even');
    test(!numIsEven(1), '1 is not even');
    test(!numIsEven(-1), '-1 is not even');
}
//----------------------------------------
function numRoundToIncrement(float $value, float $increment): float
{
    errIf($increment === 0.0, 'numRoundToIncrement: increment is zero');
    $incCount = round($value / $increment, 15);
    $incCount = round($incCount);
    $finalValue = round($increment * $incCount, 15);
    return $finalValue;
}
//----------------------------------------
function numRoundToIncrementTest(): void
{
    test(numRoundToIncrement(5.24, 0.5) === 5.0, 'round down to increment');
    test(numRoundToIncrement(5.26, 0.5) === 5.5, 'round up to increment');
    test(numRoundToIncrement(-5.26, 0.5) === -5.5, 'negative round');
    test(numRoundToIncrement(7.5, 0.5) === 7.5, 'already on increment');
}
//----------------------------------------
function numFloorToIncrement(float $value, float $increment): float
{
    errIf($increment === 0.0, 'numFloorToIncrement: increment is zero');
    $incCount = round($value / $increment, 15);
    $incCount = floor($incCount);
    $finalValue = round($increment * $incCount, 15);
    return $finalValue;
}
//----------------------------------------
function numFloorToIncrementTest(): void
{
    test(numFloorToIncrement(5.74, 0.5) === 5.5, 'floor positive');
    test(numFloorToIncrement(5.75, 0.5) === 5.5, 'half step floors down');
    test(numFloorToIncrement(-5.26, 0.5) === -5.5, 'floor negative');
    test(numFloorToIncrement(7.5, 0.5) === 7.5, 'already on increment');
}
//----------------------------------------
function numCeilToIncrement(float $value, float $increment): float
{
    errIf($increment === 0.0, 'numCeilToIncrement: increment is zero');
    $incCount = round($value / $increment, 15);
    $incCount = ceil($incCount);
    $finalValue = round($increment * $incCount, 15);
    return $finalValue;
}
//----------------------------------------
function numCeilToIncrementTest(): void
{
    test(numCeilToIncrement(5.24, 0.5) === 5.5, 'ceil positive');
    test(numCeilToIncrement(5.0, 0.5) === 5.0, 'already on increment');
    test(numCeilToIncrement(-5.26, 0.5) === -5.0, 'ceil negative');
    test(numCeilToIncrement(-5.5, 0.5) === -5.5, 'negative increment boundary');
}
//---------------------------------------
function numRatioToPercentDiff(float $ratio): string
{
    return numRatioToPercent($ratio - 1);
}
//---------------------------------------
function numRatioToPercentDiffTest(): void
{
    test(numRatioToPercentDiff(1.10) === '10%', 'positive percent diff');
    test(numRatioToPercentDiff(0.9) === '-10%', 'negative percent diff');
    test(numRatioToPercentDiff(1.005) === '0.5%', 'fractional percent diff');
}
//---------------------------------------
function numRatioToPercent(float $ratio): string
{
    return round($ratio * 100, 2) . '%';
}
//---------------------------------------
function numRatioToPercentTest(): void
{
    test(numRatioToPercent(0.5) === '50%', 'whole percent');
    test(numRatioToPercent(0.12345) === '12.35%', 'rounded percent');
    test(numRatioToPercent(-0.25) === '-25%', 'negative percent');
}
//============================================================
function numToNorm(float $number, float $rangeMin, float $rangeMax): float
{
    errIf($rangeMin > $rangeMax, 'numToNorm: rangeMin > rangeMax');
    if ($rangeMin == $rangeMax) {
        return 0;
    }

    $delta = $rangeMax - $rangeMin;
    $positionInRange = $number - $rangeMin;
    $norm = $positionInRange / $delta;

    return $norm;
}
//------------------
function numToNormTest(): void
{
    test(numToNorm(5, 0, 10) === 0.5, 'basic test');
    test(numToNorm(10, 0, 10) === 1.0, 'upper bound');
    test(numToNorm(0, 0, 10) === 0.0, 'lower bound');
    test(numToNorm(10, 10, 10) === 0.0, 'zero delta test');
    try {
        numToNorm(5, 10, 0);
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testExceptionThrown($e, 'rangeMin > rangeMax');
    }
}
//------------------------------------------------------------------------------
function numInterp(float $numNorm, float $rangeMin, float $rangeMax): float
{
    $delta = $rangeMax - $rangeMin;
    $positionInRange = $delta * $numNorm;
    $finalValue = $rangeMin + $positionInRange;

    return $finalValue;
}
//------------------------------------------------------------------------------
function numInterpTest(): void
{
    test(numInterp(0.5, 0, 10) === 5.0, 'midpoint');
    test(numInterp(0.0, 2, 4) === 2.0, 'lower bound');
    test(numInterp(1.0, 2, 4) === 4.0, 'upper bound');
    test(numInterp(-0.5, 0, 10) === -5.0, 'extrapolate below range');
}
//------------------------------------------------------------------------------
function numMapRangeToRange(float $number, float $rangeMin, float $rangeMax, float $newRangeMin, float $newRangeMax): float
{
    $norm = numToNorm($number, $rangeMin, $rangeMax);
    $finalValue = numInterp($norm, $newRangeMin, $newRangeMax);

    return $finalValue;
}
//============================================================
function numMapRangeToRangeTest(): void
{
    test(numMapRangeToRange(0, 0, 10, 10, 20) == 10, 'BasicTest1');
    test(numMapRangeToRange(1, 0, 10, 10, 20) == 11, 'BasicTest2');
    test(numMapRangeToRange(5, 0, 10, 10, 20) == 15, 'BasicTest3');
    test(numMapRangeToRange(9, 0, 10, 10, 20) == 19, 'BasicTest4');
    test(numMapRangeToRange(10, 0, 10, 10, 20) == 20, 'BasicTest5');
    test(numMapRangeToRange(3, 0, 9, 0, 99) == 33, 'BasicTest6');
    test(numMapRangeToRange(3, 0, 9, 0, 1) == 1 / 3, 'BasicTest7');
    test(numMapRangeToRange(9, 0, 9, 0, 1) == 1, 'BasicTest8');
    test(numMapRangeToRange(1, 1, 2, 0, 1) == 0, 'BasicTest9');
    test(numMapRangeToRange(2, 1, 2, 0, 1) == 1, 'BasicTest10');
}
//------------------
function floatEqual(float $float1, float $float2, float $alikeRatio = 1.00001): bool
{
    $epsilon = $alikeRatio - 1.0;

    // Check absolute difference first - handles zeros and values near zero with different signs
    if (abs($float1 - $float2) <= $epsilon) {
        return true;
    }

    // If they have different signs and didn't pass the absolute check, they are not equal
    if ($float1 * $float2 < 0) {
        return false;
    }

    if ($float1 == 0.0 || $float2 == 0.0) {
        return false;
    }

    // Ratio check for larger numbers (using absolute values to handle negative pairs)
    $abs1 = abs($float1);
    $abs2 = abs($float2);

    if ($abs1 > $abs2) {
        $ratio = $abs1 / $abs2;
    } else {
        $ratio = $abs2 / $abs1;
    }

    return $ratio <= $alikeRatio;
}
//------------------
function floatEqualTest(): void
{
    test(floatEqual(0, 0.000001), 'zero vs small positive');
    test(floatEqual(0, -0.000001), 'zero vs small negative');
    test(floatEqual(0.000001, -0.000001), 'small positive vs small negative');
    test(!floatEqual(0, 0.1), 'zero vs large positive');
    test(!floatEqual(0, -0.1), 'zero vs large negative');
    test(floatEqual(100.0, 100.0001), 'large numbers close');
}
//------------------
function floatGreaterThanOrEqual(float $float1, float $float2, float $alikeRatio = 1.00001): bool
{
    $isEqual = floatEqual($float1, $float2, $alikeRatio);

    if ($isEqual) {
        return true;
    }

    return $float1 > $float2;
}
//------------------
function floatGreaterThanOrEqualTest(): void
{
    test(floatGreaterThanOrEqual(100.0, 100.0001), 'equal within tolerance');
    test(floatGreaterThanOrEqual(100.01, 100.0), 'greater value');
    test(!floatGreaterThanOrEqual(99.99, 100.0), 'lesser value');
}
//------------------
function floatLessThanOrEqual(float $float1, float $float2, float $alikeRatio = 1.00001): bool
{
    $isEqual = floatEqual($float1, $float2, $alikeRatio);

    if ($isEqual) {
        return true;
    }

    return $float1 < $float2;
}
//------------------
function floatLessThanOrEqualTest(): void
{
    test(floatLessThanOrEqual(100.0, 100.0001), 'equal within tolerance');
    test(floatLessThanOrEqual(99.99, 100.0), 'less value');
    test(!floatLessThanOrEqual(100.01, 100.0), 'greater value');
}
//------------------
function floatGreaterThan(float $float1, float $float2, float $alikeRatio = 1.00001): bool
{
    $isEqual = floatEqual($float1, $float2, $alikeRatio);

    if ($isEqual) {
        return false;
    }

    return $float1 > $float2;
}
//------------------
function floatGreaterThanTest(): void
{
    test(!floatGreaterThan(100.0, 100.0001), 'equal within tolerance is not greater');
    test(floatGreaterThan(100.01, 100.0), 'greater value');
    test(!floatGreaterThan(99.99, 100.0), 'less value is not greater');
}
//------------------
function floatLessThan(float $float1, float $float2, float $alikeRatio = 1.00001): bool
{
    $isEqual = floatEqual($float1, $float2, $alikeRatio);

    if ($isEqual) {
        return false;
    }

    return $float1 < $float2;
}
//------------------
function floatLessThanTest(): void
{
    test(!floatLessThan(100.0, 100.0001), 'equal within tolerance is not less');
    test(floatLessThan(99.99, 100.0), 'less value');
    test(!floatLessThan(100.01, 100.0), 'greater value is not less');
}
//---------------------------------------
function numClamp(float $number, float $lowerBound, float $upperBound): float
{
    if ($number < $lowerBound) {
        return $lowerBound;
    }

    if ($number > $upperBound) {
        return $upperBound;
    }
    return $number;
}
//---------------------------------------
function numClampTest(): void
{
    test(numClamp(5, 0, 10) === 5.0, 'inside range');
    test(numClamp(-1, 0, 10) === 0.0, 'below range');
    test(numClamp(11, 0, 10) === 10.0, 'above range');
    test(numClamp(0, 0, 10) === 0.0, 'at lower bound');
    test(numClamp(10, 0, 10) === 10.0, 'at upper bound');
}
//---------------------------------------
function numClampTolerance(float $number, float $lowerBound, float $upperBound, float $tolerance): float
{
    errIf($number < $lowerBound - $tolerance, "number below lower bound");
    errIf($number > $upperBound + $tolerance, "number above upper bound");

    return numClamp($number, $lowerBound, $upperBound);
}
//---------------------------------------
function numClampToleranceTest(): void
{
    test(numClampTolerance(0.5, 0, 1, 0.1) === 0.5, 'inside bounds');
    test(numClampTolerance(-0.05, 0, 1, 0.1) === 0.0, 'below bound but within tolerance');
    test(numClampTolerance(1.05, 0, 1, 0.1) === 1.0, 'above bound but within tolerance');

    try {
        numClampTolerance(-0.2, 0, 1, 0.1);
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testExceptionThrown($e, 'below lower bound throws');
    }

    try {
        numClampTolerance(1.2, 0, 1, 0.1);
        errExceptionNotThrown();
    } catch (Throwable $e) {
        testExceptionThrown($e, 'above upper bound throws');
    }
}
//---------------------------------------
function numIsBetween(float $number, float $rangeA, float $rangeB, bool $inclusive = true): bool
{
    $min = min($rangeA, $rangeB);
    $max = max($rangeA, $rangeB);

    if ($inclusive) {
        return $number >= $min && $number <= $max;
    }

    return $number > $min && $number < $max;
}
//---------------------------------------
function numIsBetweenTest(): void
{
    test(numIsBetween(5, 0, 10) === true, 'inside range inclusive');
    test(numIsBetween(0, 0, 10) === true, 'at lower bound inclusive');
    test(numIsBetween(10, 0, 10) === true, 'at upper bound inclusive');
    test(numIsBetween(-1, 0, 10) === false, 'below range inclusive');
    test(numIsBetween(11, 0, 10) === false, 'above range inclusive');

    test(numIsBetween(5, 0, 10, false) === true, 'inside range exclusive');
    test(numIsBetween(0, 0, 10, false) === false, 'at lower bound exclusive');
    test(numIsBetween(10, 0, 10, false) === false, 'at upper bound exclusive');

    test(numIsBetween(5, 10, 0) === true, 'swapped range handles correctly');
}
//---------------------------------------
function zMathTest(): void
{
    echoLineBlockStart('zMathTest');
    VectorTest();
    modFloatTest();
    pointWhereLinesCrossTest();
    numIsOddTest();
    numIsEvenTest();
    numRoundToIncrementTest();
    numFloorToIncrementTest();
    numCeilToIncrementTest();
    numRatioToPercentDiffTest();
    numRatioToPercentTest();
    numToNormTest();
    numInterpTest();
    numMapRangeToRangeTest();
    floatEqualTest();
    floatGreaterThanOrEqualTest();
    floatLessThanOrEqualTest();
    floatGreaterThanTest();
    floatLessThanTest();
    numClampTest();
    numClampToleranceTest();
    numIsBetweenTest();
    echoLineBlockFinish('zMathTest');
}
//--------------------------

/*function pointWhereLinesCross($lineAx1, $lineAy1, $lineAx2, $lineAy2, $lineBx1, $lineBy1, $lineBx2, $lineBy2, &$crossX, &$crossY) {
    //y = mx+b
    //b = y-mx
    //m = rise/run
    //rise = y2-y1
    //run = x2-x1
    
    $lineArise = $lineAy2 - $lineAy1;
    $lineBrise = $lineBy2 - $lineBy1;

    $lineArun = $lineAx2 - $lineAx1;
    $lineBrun = $lineBx2 - $lineBx1;
    
    //if both runs are 0 then they do not cross at a single point
    if($lineArun == 0 && $lineBrun == 0) {
        return false;
    }
    
    if($lineArun == 0) {
        $lineBm = $lineBrise / $lineBrun;
        $lineBb = $lineBy1 - ($lineBm * $lineBx1);
        $crossX = $lineAx1;
        $crossY = $lineBm * $crossX + $lineBb;
        return true;
    }

    $lineAm = $lineArise / $lineArun;
    $lineAb = $lineAy1 - ($lineAm * $lineAx1);
    if($lineBrun == 0) {
        $crossX = $lineBx1;
        $crossY = $lineAm * $crossX + $lineAb;
        return true;
    }
    
    $lineBm = $lineBrise / $lineBrun;
    $lineBb = $lineBy1 - ($lineBm * $lineBx1);

    //if lines are parallel return false
    if($lineAm == $lineBm) {
        return false;
    }
    
    //solve all other cases
    //$crossY = $lineAm*$crossX+$lineAb 
    //$crossY = $lineBm*$crossX+$lineBb
    //$lineAm*$crossX+$lineAb           = $lineBm*$crossX+$lineBb
    //$lineAm*$crossX                   = $lineBm*$crossX+$lineBb-$lineAb
    //$lineAm*$crossX-$lineBm*$crossX   =                 $line2b-$line1b
    //$crossX($lineAm-$lineBm)          =                 $lineBb-$lineAb
    $crossX = ($lineBb - $lineAb) / ($lineAm - $lineBm);
    $crossY = $lineAm * $crossX + $lineAb;

    return true;
}*/
