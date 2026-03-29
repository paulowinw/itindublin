/*!
 * https://github.com/alfaslash/array-includes/blob/master/array-includes.js
 *
 * Array includes 1.0.4
 * https://github.com/alfaslash/array-includes
 *
 * Released under the Apache License 2.0
 * https://github.com/alfaslash/array-includes/blob/master/LICENSE
 */
if (![].includes) {
    Array.prototype.includes = function (searchElement, fromIndex) {
        'use strict';
        var O = Object(this);
        var len = parseInt(O.length) || 0;
        if (len === 0) {
            return false;
        }
        var n = parseInt(fromIndex) || 0;
        var k;
        if (n >= 0) {
            k = n;
        } else {
            k = len + n;
            if (k < 0) {
                k = 0;
            }
        }
        while (k < len) {
            var currentElement = O[k];
            if (searchElement === currentElement ||
                (searchElement !== searchElement && currentElement !== currentElement)
            ) {
                return true;
            }
            k++;
        }
        return false;
    };
}
