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
"use strict";

export const cRadFullTurn = 2 * Math.PI;
export const cRad4thTurn = cRadFullTurn / 4;
export const cRad6thTurn = cRadFullTurn / 6;
export const cRad3rdTurn = cRadFullTurn / 3;

export const cSin3rdTurn = Math.sin(cRad3rdTurn);
export const cCos3rdTurn = Math.cos(cRad3rdTurn);

//hue values in radians
export const cHueRedRad = 0;
export const cHueRedGreenRad = cRad6thTurn;
export const cHueGreenRad = cRad6thTurn * 2;
export const cHueGreenBlueRad = cRad6thTurn * 3;
export const cHueBlueRad = cRad6thTurn * 4;
export const cHueBlueRedRad = cRad6thTurn * 5;

export const cHueYellowRad = cHueRedGreenRad;
export const cHueMagentaRad = cHueBlueRedRad;
export const cHueCyanRad = cHueGreenBlueRad;

export const cHueBlueMagentaRad = cRad4thTurn * 3;
export const cHueVioletRad = cHueBlueMagentaRad;

//hue values normalized to 0-1
export const cHueRedNorm = 0;
export const cHueRedGreenNorm = (1 / 6);
export const cHueGreenNorm = (1 / 6) * 2;
export const cHueGreenBlueNorm = (1 / 6) * 3;
export const cHueBlueNorm = (1 / 6) * 4;
export const cHueBlueRedNorm = (1 / 6) * 5;

export const cHueYellowNorm = cHueRedGreenNorm;
export const cHueMagentaNorm = cHueBlueRedNorm;
export const cHueCyanNorm = cHueGreenBlueNorm;

export const cHueBlueMagentaNorm = (1 / 4) * 3;
export const cHueVioletNorm = cHueBlueMagentaNorm;

//------------------------------------------------------------------------------
//the input values are normalized to a range of 0 to 1
//------------------------------------------------------------------------------
export function hgiNormToRgb(normHue, normGrayness, normIllumination) {
    let hue = normHue * cRadFullTurn;
    let grayness = normGrayness * cRad4thTurn;
    let maxIllumination = maxIlluminationForHg(hue, grayness);
    let illumination = normIllumination * maxIllumination;
    
    return hgiToRgb(hue, grayness, illumination);
}

//------------------------------------------------------------------------------
//the input values are normalized to a range of 0 to 1
//------------------------------------------------------------------------------
export function hsiNormToRgb(normHue, normSaturation, normIllumination) {
    let normGrayness = 1 - normSaturation;
    return hgiNormToRgb(normHue, normGrayness, normIllumination);
}

//------------------------------------------------------------------------------
//This function should never need to be called directly. Call hgiNormToRgb instead
//------------------------------------------------------------------------------
export function hgiToRgb(hue, grayness, illumination) {
    let chroma = illumination * Math.cos(grayness);
    let whiteness = illumination * Math.sin(grayness);
    return hcwToRgb(hue, chroma, whiteness);
}

//------------------------------------------------------------------------------
//this function should never need to be called directly. Call hgiNormToRgb instead
//this is a hexagonal pyramid. The params are not independent of each other
//------------------------------------------------------------------------------
export function hcwToRgb(hue, chroma, whiteness) {
    let r = 0;
    let g = 0;
    let b = 0;
    
    if(hue > cHueBlueRad) {
        let split = splitVectorToXandThirdTurnMag(chroma, hue - cHueBlueRad);
        b = split.x;
        r = split.thirdTurnMag;
    }
    else if(hue > cHueGreenRad) {
        let split = splitVectorToXandThirdTurnMag(chroma, hue - cHueGreenRad);
        g = split.x;
        b = split.thirdTurnMag;
    }
    else {
        let split = splitVectorToXandThirdTurnMag(chroma, hue - cHueRedRad);
        r = split.x;
        g = split.thirdTurnMag;
    }
    
    r = Math.round(r + whiteness);
    g = Math.round(g + whiteness);
    b = Math.round(b + whiteness);

    r = Math.max(0, Math.min(255, r));
    g = Math.max(0, Math.min(255, g));
    b = Math.max(0, Math.min(255, b));

    return { r, g, b };
}

//------------------------------------------------------------------------------
//This function should never need to be called directly. 
//------------------------------------------------------------------------------
export function maxIlluminationForHg(hue, grayness) {
    let maxChroma = maxChromaForH(hue);
    let grayX = Math.cos(grayness);
    let grayY = Math.sin(grayness);
    
    let cross = pointWhereLinesCross(0, 0, grayX, grayY, maxChroma, 0, 0, 255);
    let maxIlluminationForHg = Math.sqrt((cross.x * cross.x) + (cross.y * cross.y));
    
    return maxIlluminationForHg;
}

//------------------------------------------------------------------------------
//this function should never need to be called directly.
//------------------------------------------------------------------------------
export function maxChromaForH(hue) {
    let rotateBack = Math.floor(hue / cRad6thTurn) * cRad6thTurn;
    let h = hue - rotateBack;
    let hueX = Math.cos(h);
    let hueY = Math.sin(h);
    
    let maxChromaLimit = 255;
    let hexLineX1 = Math.cos(0) * maxChromaLimit;
    let hexLineY1 = Math.sin(0) * maxChromaLimit;
    let hexLineX2 = Math.cos(cRad6thTurn) * maxChromaLimit;
    let hexLineY2 = Math.sin(cRad6thTurn) * maxChromaLimit;

    let cross = pointWhereLinesCross(0, 0, hueX, hueY, hexLineX1, hexLineY1, hexLineX2, hexLineY2);
    let maxChromaForH = Math.sqrt((cross.x * cross.x) + (cross.y * cross.y));
    
    if (maxChromaForH > maxChromaLimit) {
        maxChromaForH = maxChromaLimit;
    }
    return maxChromaForH;
}

//------------------------------------------------------------------------------
//this function should never need to be called directly.
//------------------------------------------------------------------------------
export function splitVectorToXandThirdTurnMag(mag, angle) {
    let tempX = mag * Math.cos(angle);
    let y = mag * Math.sin(angle);

    let thirdTurnMag = y / cSin3rdTurn;
    let thirdTurnX = thirdTurnMag * cCos3rdTurn;
    let x = tempX - thirdTurnX;
    
    let epsilon = 1e-9;
    if (thirdTurnMag < 0 && thirdTurnMag >= -epsilon) thirdTurnMag = 0;
    if (thirdTurnMag > 255 && thirdTurnMag <= 255 + epsilon) thirdTurnMag = 255;
    if (x < 0 && x >= -epsilon) x = 0;
    if (x > 255 && x <= 255 + epsilon) x = 255;
    
    return { x, thirdTurnMag };
}

//------------------------------------------------------------------------------
//normalize the return values to a range from 0 to 1
//------------------------------------------------------------------------------
export function rgbToHglNorm(red, green, blue) {
    let result = rgbToHgl(red, green, blue);
    let hueNorm = result.hue / cRadFullTurn;
    let graynessNorm = result.grayness / cRad4thTurn;
    
    let maxIllum = maxIlluminationForHg(result.hue, result.grayness);
    let illuminationNorm = 0;
    if (maxIllum !== 0) {
        illuminationNorm = result.illumination / maxIllum;
    }
    
    hueNorm = Math.max(0, Math.min(1, hueNorm));
    graynessNorm = Math.max(0, Math.min(1, graynessNorm));
    illuminationNorm = Math.max(0, Math.min(1, illuminationNorm));
    
    return { hueNorm, graynessNorm, illuminationNorm };
}

//------------------------------------------------------------------------------
export function rgbToHgl(red, green, blue) {
    let x = (red * Math.cos(cHueRedRad)) + (green * Math.cos(cHueGreenRad)) + (blue * Math.cos(cHueBlueRad));
    let y = (red * Math.sin(cHueRedRad)) + (green * Math.sin(cHueGreenRad)) + (blue * Math.sin(cHueBlueRad));

    let hue = Math.atan2(y, x);
    if(hue < 0) {
        hue += cRadFullTurn;
    }
    
    let chroma = Math.sqrt((x * x) + (y * y));
    let white = Math.min(red, green, blue);
    let grayness = Math.atan2(white, chroma);

    let illumination = Math.sqrt((chroma * chroma) + (white * white));
    
    return { hue, grayness, illumination };
}

//------------------------------------------------------------------------------
export function pointWhereLinesCross(lineAx1, lineAy1, lineAx2, lineAy2, lineBx1, lineBy1, lineBx2, lineBy2) {
    let dxA = lineAx2 - lineAx1;
    let dyA = lineAy2 - lineAy1;
    let dxB = lineBx2 - lineBx1;
    let dyB = lineBy2 - lineBy1;

    let det = dxA * dyB - dyA * dxB;
    let EPS = 1e-9;
    if (Math.abs(det) < EPS) {
        return {x: null, y: null};
    }

    let t = ((lineBx1 - lineAx1) * dyB - (lineBy1 - lineAy1) * dxB) / det;
    let x = lineAx1 + t * dxA;
    let y = lineAy1 + t * dyA;
    return {x, y};
}
