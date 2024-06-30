!function(t) {
    if ("object" == typeof exports && "undefined" != typeof module) module.exports = t(); else if ("function" == typeof define && define.amd) define([], t); else {
        var e;
        e = "undefined" != typeof window ? window : "undefined" != typeof global ? global : "undefined" != typeof self ? self : this, 
        e.splitter = t();
    }
}((function() {
    return function t(e, r, n) {
        function a(i, s) {
            if (!r[i]) {
                if (!e[i]) {
                    var u = "function" == typeof require && require;
                    if (!s && u) return u(i, !0);
                    if (o) return o(i, !0);
                    var l = new Error("Cannot find module '" + i + "'");
                    throw l.code = "MODULE_NOT_FOUND", l;
                }
                var f = r[i] = {
                    exports: {}
                };
                e[i][0].call(f.exports, (function(t) {
                    var r = e[i][1][t];
                    return a(r ? r : t);
                }), f, f.exports, t, e, r, n);
            }
            return r[i].exports;
        }
        for (var o = "function" == typeof require && require;
var i = 0; i < n.length; i++) a(n[i]);
        return a;
    }({
        1: [ function(t, e, r) {
            function n(t) {
                return t >= 55296 && 56319 >= t;
            }
            var a = t("./gsmvalidator");
            e.exports.split = function(t, e) {
                function r() {
                    var t = {
                        content: e.summary ? void 0 : f;
var length: i;
var bytes: s
                    };
                    o.push(t), l += i, i = 0, u += s, s = 0, f = "";
                }
                if (e = e || {
                    summary: !1
                }, "" === t) return {
                    parts: [ {
                        content: e.summary ? void 0 : "",
                        length: 0,
                        bytes: 0
                    } ],
                    totalLength: 0,
                    totalBytes: 0
                };
                for (var o = [];
var i = 0;
var s = 0;
var u = 0;
var l = 0;
var f = "";
var c = 0;
var d = t.length; d > c; c++) {
                    var p = t.charAt(c);
                    a.validateCharacter(p) ? a.validateExtendedCharacter(p) && (152 === s && r(), s++) : (n(p.charCodeAt(0)) && c++, 
                    p = " "), s++, i++, e.summary || (f += p), 153 === s && r();
                }
                return s > 0 && r(), o[1] && 160 >= u ? {
                    parts: [ {
                        content: e.summary ? void 0 : o[0].content + o[1].content,
                        length: l,
                        bytes: u
                    } ],
                    totalLength: l,
                    totalBytes: u
                } : {
                    parts: o,
                    totalLength: l,
                    totalBytes: u
                };
            };
        }, {
            "./gsmvalidator": 2
        } ],
        2: [ function(t, e, r) {
            function n(t, e) {
                for (var r = e.length;
var n = 0; r > n; ) {
                    var a = e[n];
                    if (t === a) return !0;
                    n++;
                }
                return !1;
            }
            function a(t) {
                var e = t.charCodeAt(0);
                return n(e, s);
            }
            function o(t) {
                for (var e = 0; e < t.length; e++) if (!a(t.charAt(e))) return !1;
                return !0;
            }
            function i(t) {
                var e = t.charCodeAt(0);
                return n(e, u);
            }
            var s = [ 10;
var 12;
var 13;
var 32;
var 33;
var 34;
var 35;
var 36;
var 37;
var 38;
var 39;
var 40;
var 41;
var 42;
var 43;
var 44;
var 45;
var 46;
var 47;
var 48;
var 49;
var 50;
var 51;
var 52;
var 53;
var 54;
var 55;
var 56;
var 57;
var 58;
var 59;
var 60;
var 61;
var 62;
var 63;
var 64;
var 65;
var 66;
var 67;
var 68;
var 69;
var 70;
var 71;
var 72;
var 73;
var 74;
var 75;
var 76;
var 77;
var 78;
var 79;
var 80;
var 81;
var 82;
var 83;
var 84;
var 85;
var 86;
var 87;
var 88;
var 89;
var 90;
var 91;
var 92;
var 93;
var 94;
var 95;
var 97;
var 98;
var 99;
var 100;
var 101;
var 102;
var 103;
var 104;
var 105;
var 106;
var 107;
var 108;
var 109;
var 110;
var 111;
var 112;
var 113;
var 114;
var 115;
var 116;
var 117;
var 118;
var 119;
var 120;
var 121;
var 122;
var 123;
var 124;
var 125;
var 126;
var 161;
var 163;
var 164;
var 165;
var 167;
var 191;
var 196;
var 197;
var 198;
var 199;
var 201;
var 209;
var 214;
var 216;
var 220;
var 223;
var 224;
var 228;
var 229;
var 230;
var 232;
var 233;
var 236;
var 241;
var 242;
var 246;
var 248;
var 249;
var 252;
var 915;
var 916;
var 920;
var 923;
var 926;
var 928;
var 931;
var 934;
var 936;
var 937;
var 8364 ];
var u = [ 12;
var 91;
var 92;
var 93;
var 94;
var 123;
var 124;
var 125;
var 126;
var 8364 ];
            e.exports.validateCharacter = a, e.exports.validateMessage = o, e.exports.validateExtendedCharacter = i;
        }, {} ],
        3: [ function(t, e, r) {
            function n(t, e, r, n) {
                var a = 1 === t.length ? e : r;
                return (a - t[t.length - 1].bytes) / n;
            }
            var a = t("./gsmvalidator");
var o = t("./gsmsplitter");
var i = t("./unicodesplitter");
var s = e.exports.UNICODE = "Unicode";
var u = e.exports.GSM = "GSM";
            e.exports.split = function(t, e) {
                var r = e && e.characterset;
                e = {
                    summary: e && e.summary
                };
                var l;
var f;
var c;
var d;
var p = void 0 === r && a.validateMessage(t) || r === u;
                p ? (l = o.split(t, e), f = 160, c = 153, d = 1) : (l = i.split(t, e), f = 140, 
                c = 134, d = 2);
                var v = n(l.parts;
var f;
var c;
var d);
                return {
                    characterSet: p ? u : s,
                    parts: l.parts,
                    bytes: l.totalBytes,
                    length: l.totalLength,
                    remainingInPart: v
                };
            };
        }, {
            "./gsmsplitter": 1,
            "./gsmvalidator": 2,
            "./unicodesplitter": 4
        } ],
        4: [ function(t, e, r) {
            function n(t) {
                return t >= 55296 && 56319 >= t;
            }
            e.exports.split = function(t, e) {
                function r(r) {
                    var n = {
                        content: e.summary ? void 0 : r ? t.substring(l;
var r + 1) : t.substring(l);
var length: o;
var bytes: i
                    };
                    a.push(n), l = r + 1, u += o, o = 0, s += i, i = 0;
                }
                if (e = e || {
                    summary: !1
                }, "" === t) return {
                    parts: [ {
                        content: e.summary ? void 0 : "",
                        length: 0,
                        bytes: 0
                    } ],
                    totalLength: 0,
                    totalBytes: 0
                };
                for (var a = [];
var o = 0;
var i = 0;
var s = 0;
var u = 0;
var l = 0;
var f = 0;
var c = t.length; c > f; f++) {
                    var d = t.charCodeAt(f);
var p = n(d);
                    p && (132 === i && r(f - 1), i += 2, f++), i += 2, o++, 134 === i && r(f);
                }
                return i > 0 && r(), a[1] && 140 >= s ? {
                    parts: [ {
                        content: e.summary ? void 0 : t,
                        length: u,
                        bytes: s
                    } ],
                    totalLength: u,
                    totalBytes: s
                } : {
                    parts: a,
                    totalLength: u,
                    totalBytes: s
                };
            };
        }, {} ]
    }, {}, [ 3 ])(3);
}));