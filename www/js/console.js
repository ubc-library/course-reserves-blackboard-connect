/**
 * Protect window.console method calls, e.g. console is not defined on IE
 * unless dev tools are open, and IE doesn't define console.debug
 */
(function () {
    if (!window.console) {
        window.console = {};
    }
    // union of Chrome, FF, IE, and Safari console methods
    var m = [
        "log", "info", "warn", "error", "debug", "trace", "dir", "group",
        "groupCollapsed", "groupEnd", "time", "timeEnd", "profile", "profileEnd",
        "dirxml", "assert", "count", "markTimeline", "timeStamp", "clear"
    ];
    // define undefined methods as noops to prevent errors
    for (var i = 0; i < m.length; i++) {
        if (!window.console[m[i]]) {
            window.console[m[i]] = function () {
            };
        }
    }
})();

if (!Array.prototype.forEach) {
    Array.prototype.forEach = function (fun /*, thisArg */) {
        "use strict";

        if (this === void 0 || this === null)
            throw new TypeError();

        var t = Object(this);
        var len = t.length >>> 0;
        if (typeof fun !== "function")
            throw new TypeError();

        var thisArg = arguments.length >= 2 ? arguments[1] : void 0;
        for (var i = 0; i < len; i++) {
            if (i in t)
                fun.call(thisArg, t[i], i, t);
        }
    };
}

if (!Object.keys) {
    Object.keys = (function () {
        'use strict';
        var hasOwnProperty = Object.prototype.hasOwnProperty,
            hasDontEnumBug = !({toString: null}).propertyIsEnumerable('toString'),
            dontEnums = [
                'toString',
                'toLocaleString',
                'valueOf',
                'hasOwnProperty',
                'isPrototypeOf',
                'propertyIsEnumerable',
                'constructor'
            ],
            dontEnumsLength = dontEnums.length;

        return function (obj) {
            if (typeof obj !== 'object' && (typeof obj !== 'function' || obj === null)) {
                throw new TypeError('Object.keys called on non-object');
            }

            var result = [], prop, i;

            for (prop in obj) {
                if (hasOwnProperty.call(obj, prop)) {
                    result.push(prop);
                }
            }

            if (hasDontEnumBug) {
                for (i = 0; i < dontEnumsLength; i++) {
                    if (hasOwnProperty.call(obj, dontEnums[i])) {
                        result.push(dontEnums[i]);
                    }
                }
            }
            return result;
        };
    }());
}

var verboseDebug = true;

if (!verboseDebug) {

    console.log("\n\nVerbose debug is not enabled: all console.log are suppressed.\nYou can toggle this in js/console.js\n\n");

    setTimeout(function(){
        console.log = function(){};
    },125);
}

jQuery.fn.redraw = function () {
    return this.hide(0, function () {
        $(this).show(0);
    });
};


/**
 * @author Erik Karlsson, www.nonobtrusive.com
 **/
function countEventHandlers() {

    var elements = document.getElementsByTagName("*"), len = elements.length,
        counter = 0,
        countermap = [],
    /* fill up with more events if needed or just use those you want to look for */
        events = ['onmousedown', 'onmouseup', 'onmouseover', 'onmouseout',
            'onclick', 'onmousemove', 'ondblclick', 'onerror', 'onresize', 'onscroll',
            'onkeydown', 'onkeyup', 'onkeypress', 'onchange', 'onsubmit'],
        eventlen = events.length;

    for (var i = eventlen - 1; i >= 0; --i) {
        countermap[events[i]] = 0;   //reset the map
    }


    while (len--) {  //go through all DOM-nodes
        var element = elements[len],
            tmp = eventlen;
        while (tmp--) {  //go through all events defined above for each node and see if it exists.
            if (element[events[tmp]]) {
                counter++;
                countermap[events[tmp]]++;
            }
        }
    }

    var someStats = counter + " events found in total\n\n";
    for (var o in countermap) {
        someStats += o + " was found " + countermap[o] + " times\n";
    }
    alert(someStats);

}