<?php
/*
Copyright 2019-2026 Sterling Butts, Andrew Butts

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

require_once dirname(__FILE__).'/zMath.php';
require_once dirname(__FILE__).'/zStrings.php';

//hue values in radians
define('cHueRedRad', 0);
define('cHueRedGreenRad',   cRad6thTurn);
define('cHueGreenRad',      cRad6thTurn * 2);
define('cHueGreenBlueRad',  cRad6thTurn * 3);
define('cHueBlueRad',       cRad6thTurn * 4);
define('cHueBlueRedRad',    cRad6thTurn * 5);

define('cHueYellowRad', cHueRedGreenRad);
define('cHueMagentaRad', cHueBlueRedRad);
define('cHueCyanRad', cHueGreenBlueRad);

define('cHueBlueMagentaRad', cRad4thTurn * 3);
define('cHueVioletRad', cHueBlueMagentaRad);

//hue values normalized to 0-1
define('cHueRedNorm', 0);
define('cHueRedGreenNorm',  (1 / 6));
define('cHueGreenNorm',     (1 / 6) * 2);
define('cHueGreenBlueNorm', (1 / 6) * 3);
define('cHueBlueNorm',      (1 / 6) * 4);
define('cHueBlueRedNorm',   (1 / 6) * 5);

define('cHueYellowNorm', cHueRedGreenNorm);
define('cHueMagentaNorm', cHueBlueRedNorm);
define('cHueCyanNorm', cHueGreenBlueNorm);

define('cHueBlueMagentaNorm', (1 / 4) * 3);
define('cHueVioletNorm', cHueBlueMagentaNorm);

define('cSin3rdTurn', sin(cRad3rdTurn));
define('cCos3rdTurn', cos(cRad3rdTurn));
//------------------------------------------------------------------------------
//the input values are normalized to a range of 0 to 1
//------------------------------------------------------------------------------
function hgiNormToRgb(float $normHue, float $normGrayness, float $normIllumination, &$r, &$g, &$b): void {
    checkNumInRange($normHue, 0, 1, 'Hue out of range');
    checkNumInRange($normGrayness, 0, 1, 'Gray out of range');
    checkNumInRange($normIllumination, 0, 1, 'Luminance out of range');

    $hue =              $normHue * cRadFullTurn;
    $grayness =         $normGrayness * cRad4thTurn;
    $maxIllumination = maxIlluminationForHg($hue, $grayness);
    $illumination =     $normIllumination * $maxIllumination;
    
    hgiToRgb($hue, $grayness, $illumination, $r, $g, $b);
}
//------------------------------------------------------------------------------
//the input values are normalized to a range of 0 to 1
//------------------------------------------------------------------------------
function hsiNormToRgb(float $normHue, float $normSaturation, float $normIllumination, &$r, &$g, &$b): void {
    $normGrayness = 1 - $normSaturation;
    hgiNormToRgb($normHue, $normGrayness, $normIllumination, $r, $g, $b);
}
//------------------------------------------------------------------------------
//This function should never need to be called directly. Call hgiNormToRgb instead
//------------------------------------------------------------------------------
function hgiToRgb(float $hue, float $grayness, float $illumination, &$r, &$g, &$b): void {
    checkNumInRange($hue, 0, cRadFullTurn, 'Hue out of range');
    checkNumInRange($grayness, 0, cRad4thTurn, 'Grayness out of range');
    $maxIlluminationForHg = maxIlluminationForHg($hue, $grayness);
    checkNumInRange($illumination, 0, $maxIlluminationForHg, 'Illumination out of range');

    $chroma = $illumination * cos($grayness);
    $whiteness = $illumination * sin($grayness);
    hcwToRgb($hue, $chroma, $whiteness, $r, $g, $b);
}
//------------------------------------------------------------------------------
//this function should never need to be called directly. Call hgiNormToRgb instead
//this is a hexagonal pyramid. The params are not independent of each other
//------------------------------------------------------------------------------
function hcwToRgb(float $hue, float $chroma, float $whiteness, &$r, &$g, &$b): void {
    checkNumInRange($hue, 0, cRadFullTurn, 'Hue out of range');
    //ranges for chroma and whitness should be valid if hglToRgb was called to get here

    $r = 0;
    $g = 0;
    $b = 0;
    if($hue > cHueBlueRad) {
        splitVectorToXandThirdTurnMag($chroma, $hue - cHueBlueRad, $b, $r);
    }
    else if($hue > cHueGreenRad) {
        splitVectorToXandThirdTurnMag($chroma, $hue - cHueGreenRad, $g, $b);
    }
    else {
        splitVectorToXandThirdTurnMag($chroma, $hue - cHueRedRad, $r, $g);
    }

    $r = round($r + $whiteness);
    $g = round($g + $whiteness);
    $b = round($b + $whiteness);

    checkNumInRange($r, 0, 255, "r output $r out of range");
    checkNumInRange($g, 0, 255, "g output $g out of range");
    checkNumInRange($b, 0, 255, "b output $b out of range");
}
//------------------------------------------------------------------------------
function hcwToRgbTest(): void {
    hcwToRgb(cHueRedRad, 255, 0, $r, $g, $b);
    test($r == 255, 'red r test');
    test($g == 0, 'red g test');
    test($b == 0, 'red b test');
    hcwToRgb(cHueGreenRad, 255, 0, $r, $g, $b);
    test($r == 0, 'Green r test');
    test($g == 255, 'Green g test');
    test($b == 0, 'Green b test');
    hcwToRgb(cHueBlueRad, 255, 0, $r, $g, $b);
    test($r == 0, 'blue r test');
    test($g == 0, 'blue g test');
    test($b == 255, 'blue b test');
    hcwToRgb(cHueYellowRad, 255, 0, $r, $g, $b);
    test($r == 255, 'yellow r test');
    test($g == 255, 'yellow g test');
    test($b == 0, 'yellow b test');
}
//------------------------------------------------------------------------------
//This function should never need to be called directly. 
//not sure if the logic is correct
//------------------------------------------------------------------------------
function maxIlluminationForHg(float $hue, float $grayness): float {
    checkNumInRange($hue, 0, cRadFullTurn, 'Hue out of range');
    checkNumInRange($grayness, 0, cRad4thTurn, 'Grayness out of range');

    $maxChroma = maxChromaForH($hue);
    $grayX = cos($grayness);
    $grayY = sin($grayness);
    pointWhereLinesCross(0, 0, $grayX, $grayY, $maxChroma, 0, 0, 255, $crossX, $crossY);
    $maxIlluminationForHg = sqrt(($crossX * $crossX) + ($crossY * $crossY));

    return $maxIlluminationForHg;     
}
//------------------------------------------------------------------------------
function maxIlluminationForHgTest(): void {
    test(maxIlluminationForHg(cHueRedRad, 0) == 255, 'red test');
    test(maxIlluminationForHg(cHueYellowRad, 0) == 255, 'yellow test');
    test(maxIlluminationForHg(cHueGreenRad, 0) == 255, 'green test');
    test(maxIlluminationForHg(cHueCyanRad, 0) == 255, 'cyan test');
    test(maxIlluminationForHg(cHueBlueRad, 0) == 255, 'blue test');
    test(maxIlluminationForHg(cHueMagentaRad, 0) == 255, 'magenta test');
    
    test(round(maxIlluminationForHg(cHueYellowRad / 2, 0)) == 221, 'half yellow test');
    $x = 255/2;
    $y = 255/2;
    $v = sqrt(pow($x, 2) + pow($y, 2));
    test(floatEqual(maxIlluminationForHg(cHueYellowRad, cRad8thTurn), $v), 'half chroma yellow test');
}
//------------------------------------------------------------------------------
//this function should never need to be called directly.
//------------------------------------------------------------------------------
function maxChromaForH(float $hue): float {
    checkNumInRange($hue, 0, cRadFullTurn, 'Hue out of range');

    $crossX = 0;
    $crossY = 0;
    $rotateBack = numFloorToIncrement($hue, cRad6thTurn);
    $hue = $hue - $rotateBack;
    $hueX = cos($hue);
    $hueY = sin($hue);
    
    $maxChromaLimit = 255;
    $hexLineX1 = cos(0) * $maxChromaLimit;//will be 255
    $hexLineY1 = sin(0) * $maxChromaLimit;//will be 0
    $hexLineX2 = cos(cRad6thTurn) * $maxChromaLimit;
    $hexLineY2 = sin(cRad6thTurn) * $maxChromaLimit;

    pointWhereLinesCross(0, 0, $hueX, $hueY, 
                            $hexLineX1, $hexLineY1, $hexLineX2, $hexLineY2, 
                            $crossX, $crossY);

    $maxChromaForH = sqrt($crossX * $crossX + $crossY * $crossY);

    if($maxChromaForH > $maxChromaLimit) {
        $maxChromaForH = $maxChromaLimit;
    } 
    checkNumInRange($maxChromaForH, 0, $maxChromaLimit, "Chroma $maxChromaForH output out of range");

    return $maxChromaForH;
}
//------------------------------------------------------------------------------
function maxChromaForHTest(): void {
    test(maxChromaForH(cHueRedRad) == 255, 'red test');
    test(maxChromaForH(cHueGreenRad) == 255, 'green test');
    test(maxChromaForH(cHueBlueRad) == 255, 'blue test');
    test(maxChromaForH(cHueYellowRad) == 255, 'yellow test');
    test(maxChromaForH(cHueCyanRad) == 255, 'cyan Test');
    test(maxChromaForH(cHueMagentaRad) == 255, 'magenta test');
    
    test(round(maxChromaForH(cHueYellowRad / 2)) == 221, 'half yellow test');
}
//------------------------------------------------------------------------------
//this function should never need to be called directly.
//------------------------------------------------------------------------------
function splitVectorToXandThirdTurnMag(float $mag, float $angle, &$x, &$thirdTurnMag): void {
    checkNumInRange($mag, 0, 255, 'Mag out of range');
    checkNumInRange($angle, 0, cRad3rdTurn, 'Angle out of range');
    
    $tempX = $mag * cos($angle);
    $y = $mag * sin($angle);

    $thirdTurnMag = $y / cSin3rdTurn;
    $thirdTurnX = $thirdTurnMag * cCos3rdTurn;
    $x = $tempX - $thirdTurnX;

    $epsilon = 1e-9;
    numClampTolerance($thirdTurnMag, 0, 255, $epsilon);
    numClampTolerance($x, 0, 255, $epsilon);
    // Clamp tiny IEEE-754 rounding noise at boundaries (e.g. -1e-14).
}
//------------------------------------------------------------------------------
//normalize the return values to a range from 0 to 1
//------------------------------------------------------------------------------
function rgbToHglNorm($red, $green, $blue, &$hueNorm, &$graynessNorm, &$illuminationNorm) {
    checkNumInRange($red, 0, 255, 'Red out of range');
    checkNumInRange($green, 0, 255, 'Green out of range');
    checkNumInRange($blue, 0, 255, 'Blue out of range');

    rgbToHgl($red, $green, $blue, $hue, $grayness, $illumination);
    $hueNorm = $hue / cRadFullTurn;
    $graynessNorm = $grayness / cRad4thTurn;
    $illuminationNorm = $illumination / maxIlluminationForHg($hue, $grayness);

    checkNumInRange($hueNorm, 0, 1, 'Hue output out of range');
    checkNumInRange($graynessNorm, 0, 1, 'Grayness output out of range');
    checkNumInRange($illuminationNorm, 0, 1, 'Illumination output out of range');
}
//------------------------------------------------------------------------------
function rgbToHgl($red, $green, $blue, &$hue, &$grayness, &$luminance) {
    checkNumInRange($red, 0, 255, 'Red out of range');
    checkNumInRange($green, 0, 255, 'Green out of range');
    checkNumInRange($blue, 0, 255, 'Blue out of range');

    $x = ($red * cos(cHueRedRad)) + ($green * cos(cHueGreenRad)) + ($blue * cos(cHueBlueRad));
    $y = ($red * sin(cHueRedRad)) + ($green * sin(cHueGreenRad)) + ($blue * sin(cHueBlueRad));

    //find hue
    $hue = aTan2($y, $x);
    if($hue < 0) {
        $hue += cRadFullTurn;
    }
    checkNumInRange($hue, 0, cRadFullTurn, 'Hue output out of range');

    //find grayness
    $chroma = sqrt(($x * $x) + ($y * $y));
    $white = min($red, $green, $blue);
    $grayness = aTan2($white, $chroma);
    checkNumInRange($grayness, 0, cRad4thTurn, 'Gray output out of range');

    //find illumination
    $illumination = sqrt(($chroma * $chroma) + ($white * $white));
    checkNumInRange($illumination, 0, maxIlluminationForHg($grayness, $hue), 'Gray output out of range');
}
//------------------------------------------------------------------------------
function numNormToStandardHueRangeNorm(float $numNorm): float {
    return numInterp($numNorm, cHueRedNorm, cHueVioletNorm);
}
//------------------------------------------------------------------------------
function strToStandardHueRangeNorm(string $string): float {
    $norm = strToNorm($string);
    return numNormToStandHueRangeNorm($norm);
}
//------------------------------------------------------------------------------
function zColorDomeTest(): void {
    echoLineBlockStart('zColorDomeTest');
    
    maxChromaForHTest();
    maxIlluminationForHgTest();
    hcwToRgbTest();
    
    echoLineBlockFinish('zColorDomeTest');
}
