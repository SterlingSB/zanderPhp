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
require_once dirname(__FILE__).'/../thirdParty/tcpdf/tcpdf.php';
//define('cNumPtPerMm', 25.4);

//--------------------------
function numMmToPt(float $mm): float {
    return $mm * 72 / 25.4;
}
//--------------------------
class SnapPdf extends TCPDF {
    public float $fontSizeToRestore;
    function __construct(string $orientation = 'P', string $unit = 'in', array $format = [8.5, 11]) {
        parent::__construct($orientation, $unit, $format);
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
        $this->setFontSize(12);
        $this->setFont('helvetica');
    }
    //------------------------------------------------------------
    function fontSizeSave(): void {
        $this->fontSizeToRestore = $this->getFontSizePt();
    }
    //------------------------------------------------------------
    function setFontSizeRestore(bool $out = true): void {
        parent::setFontSize($this->fontSizeToRestore, $out);
    }
    //------------------------------------------------------------
    function setFontSizeShrinkPt(float $amountToShrinkPt = 0.25, bool $out = true): void {
        $sizePt = $this->getFontSizePt();
        $smallerSizePt = max($sizePt - $amountToShrinkPt, 4);
        parent::setFontSize($smallerSizePt, $out);
    }
    //------------------------------------------------------------
    function setDrawColorHgi(float $hue, float $grayness, float $illumination): void {
        hgiNormToRgb($hue, $grayness, $illumination, $r, $g, $b);
        $this->setDrawColor($r, $g, $b);
    }
    //------------------------------------------------------------
    function setTextColorHgi(float $hue, float $grayness, float $illumination): void {
        hgiNormToRgb($hue, $grayness, $illumination, $r, $g, $b);
        $this->setTextColor($r, $g, $b);
    }
    //------------------------------------------------------------
    function setTextColorBlack(): void {
        $this->setTextColorHgi(0, 0, 0);
    }
    //------------------------------------------------------------
    function setFillColorHgi(float $hue, float $grayness, float $illumination): void {
        hgiNormToRgb($hue, $grayness, $illumination, $r, $g, $b);
        $this->setFillColor($r, $g, $b);
    }
    //------------------------------------------------------------
    function setFillColorWhite(): void {
        $this->setFillColorHgi(0, 1, 1);
    }
    //------------------------------------------------------------
    function setFillColorSepia(): void {
        $this->setFillColorHgi(cHueYellowNorm, 0.9, 1);
    }
    //------------------------------------------------------------
    function snapCell(float $left, float $top, float $width, float $height, string $text, string $alignH = 'L', string $alignV = 'T', string $border = ''): int {
        $lineCount = $this->MultiCell($width, $height, $text, $border, $alignH, true, 1, $left, $top, true, 0, false, true, $height, $alignV, true);//the last param one is like stretchStyle
        
        return $lineCount;
    }
    //------------------------------------------------------------
    function snapCellShrinkFont(float $left, float $top, float $width, float $height, string $text, string $alignH = 'L', string $alignV = 'T', string $border = ''): int {
        $this->fontSizeSave();
        do { 
            $this->startTransaction();
            $lineCount = $this->snapCell($left, $top, $width, $height, $text, $alignH, $alignV, $border);
            if($lineCount > 1) {
                $this->rollbackTransaction(true);
                $this->setFontSizeShrinkPt();
            }
        } while($lineCount > 1 && $this->fontSize >= 4);
        
        $this->commitTransaction();
        $this->setFontSizeRestore();

        return $lineCount;
    }
    //------------------------------------------------------------
    function borderCharToVerticalBorderChar(string $char): string {
        check(is_string($char), 'border char not passed in');
        if(strToUpper($char) === 'L') {
            return 'B';
        }
        elseif(strToUpper($char) === 'T') {
            return 'L';
        }
        elseif(strToUpper($char) === 'R') {
            return 'T';
        }
        elseif(strToUpper($char) === 'B') {
            return 'R';
        }
        else {
            err("$char not BRTL");
        }
    }
    //------------------------------------------------------------
    function borderToVerticalBorder(string $border): string {
        $vBorder = '';
        for ($i = 0; $i < strlen($border); $i++) {
            $vBorder = $vBorder . $this->borderCharToVerticalBorderChar($border[$i]);
        }
        return $vBorder;
    }
    //------------------------------------------------------------
    function snapCellVertical(float $left, float $top, float $width, float $height, string $text, string $alignH = 'L', string $alignV = 'T', string $border = ''): void {
        $rotationOffset = $width / 2;
        $this->StartTransform();
        $this->Rotate(-90, $left + $rotationOffset, $top + $rotationOffset);
        $changedBorder = $this->borderToVerticalBorder($border);
        $this->snapCell($left, $top, $height, $width, $text, $alignH, $alignV, $changedBorder);
        $this->StopTransform();
    }
    //------------------------------------------------------------
    function snapCellShrinkFontVertical(float $left, float $top, float $width, float $height, string $text, string $alignH = 'L', string $alignV = 'T', string $border = ''): void {
        $rotationOffset = $width / 2;
        $this->StartTransform();
        $this->Rotate(-90, $left + $rotationOffset, $top + $rotationOffset);
        $changedBorder = $this->borderToVerticalBorder($border);
        $this->snapCellShrinkFont($left, $top, $height, $width, $text, $alignH, $alignV, $changedBorder);
        $this->StopTransform();
    }
}

//===========================
class SnapCard extends SnapPdf {
    public float $fillColorGrayness = 0.5;
    public float $fillColorIllumination = 1;
    //------------------------------------------------------------
    function __construct(string $orientation = 'P', string $unit = 'in', array $format = [2.5, 3.5]) {
        parent::__construct($orientation, $unit, $format);
    }
}