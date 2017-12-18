(function(f){if(typeof exports==="object"&&typeof module!=="undefined"){module.exports=f()}else if(typeof define==="function"&&define.amd){define([],f)}else{var g;if(typeof window!=="undefined"){g=window}else if(typeof global!=="undefined"){g=global}else if(typeof self!=="undefined"){g=self}else{g=this}g.JID = f()}})(function(){var define,module,exports;return (function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
'use strict'

var JID = require('./lib/JID')
var tag = require('./lib/tag')

module.exports = function createJID (a, b, c) {
  if (Array.isArray(a)) {
    return tag.apply(null, arguments)
  }

  return new JID(a, b, c)
}
module.exports.JID = JID
module.exports.tag = tag
module.exports.equal = function (a, b) {
  return a.equals(b)
}
module.exports.is = function (a) {
  return a instanceof JID
}

},{"./lib/JID":2,"./lib/tag":4}],2:[function(require,module,exports){
'use strict'

var escaping = require('./escaping')

/**
 * JID implements
 * - XMPP addresses according to RFC6122
 * - XEP-0106: JID Escaping
 *
 * @see http://tools.ietf.org/html/rfc6122#section-2
 * @see http://xmpp.org/extensions/xep-0106.html
 */
function JID (a, b, c) {
  this._local = null
  this.user = null // DEPRECATED
  this._domain = null
  this._resource = null

  if (a && (!b) && (!c)) {
    this.parseJID(a)
  } else if (b) {
    this.setLocal(a)
    this.setDomain(b)
    this.setResource(c)
  } else {
    throw new Error('Argument error')
  }
}

JID.prototype.parseJID = function (s) {
  var resourceStart = s.indexOf('/')
  if (resourceStart !== -1) {
    this.setResource(s.substr(resourceStart + 1))
    s = s.substr(0, resourceStart)
  }

  var atStart = s.indexOf('@')
  if (atStart !== -1) {
    this.setLocal(s.substr(0, atStart))
    s = s.substr(atStart + 1)
  }

  this.setDomain(s)
}

JID.prototype.toString = function (unescape) {
  var s = this._domain
  if (this._local) s = this.getLocal(unescape) + '@' + s
  if (this._resource) s = s + '/' + this._resource
  return s
}

/**
 * Convenience method to distinguish users
 **/
JID.prototype.bare = function () {
  if (this._resource) {
    return new JID(this._local, this._domain, null)
  } else {
    return this
  }
}

/**
 * Comparison function
 **/
JID.prototype.equals = function (other) {
  return (this._local === other._local) &&
    (this._domain === other._domain) &&
    (this._resource === other._resource)
}

/**
 * http://xmpp.org/rfcs/rfc6122.html#addressing-localpart
 **/
JID.prototype.setLocal = function (local, escape) {
  escape = escape || escaping.detect(local)

  if (escape) {
    local = escaping.escape(local)
  }

  this._local = local && local.toLowerCase()
  this.user = this._local
  return this
}

JID.prototype.setUser = function () {
  console.log('JID.setUser: Use JID.setLocal instead')
  this.setLocal.apply(this, arguments)
}

JID.prototype.getUser = function () {
  console.log('JID.getUser: Use JID.getLocal instead')
  return this.getLocal.apply(this, arguments)
}

JID.prototype.getLocal = function (unescape) {
  unescape = unescape || false
  var local = null

  if (unescape) {
    local = escaping.unescape(this._local)
  } else {
    local = this._local
  }

  return local
}

Object.defineProperty(JID.prototype, 'local', {
  get: JID.prototype.getLocal,
  set: JID.prototype.setLocal
})

/**
 * http://xmpp.org/rfcs/rfc6122.html#addressing-domain
 */
JID.prototype.setDomain = function (domain) {
  this._domain = domain.toLowerCase()
  return this
}

JID.prototype.getDomain = function () {
  return this._domain
}

Object.defineProperty(JID.prototype, 'domain', {
  get: JID.prototype.getDomain,
  set: JID.prototype.setDomain
})

/**
 * http://xmpp.org/rfcs/rfc6122.html#addressing-resourcepart
 */
JID.prototype.setResource = function (resource) {
  this._resource = resource
  return this
}

JID.prototype.getResource = function () {
  return this._resource
}

Object.defineProperty(JID.prototype, 'resource', {
  get: JID.prototype.getResource,
  set: JID.prototype.setResource
})

JID.prototype.detectEscape = escaping.detectEscape // DEPRECATED
JID.prototype.escapeLocal = escaping.escape // DEPRECATED
JID.prototype.unescapeLocal = escaping.unescape // DEPRECATED

module.exports = JID

},{"./escaping":3}],3:[function(require,module,exports){
'use strict'

module.exports.detect = function (local) {
  if (!local) return false

  // remove all escaped sequences
  var tmp = local
    .replace(/\\20/g, '')
    .replace(/\\22/g, '')
    .replace(/\\26/g, '')
    .replace(/\\27/g, '')
    .replace(/\\2f/g, '')
    .replace(/\\3a/g, '')
    .replace(/\\3c/g, '')
    .replace(/\\3e/g, '')
    .replace(/\\40/g, '')
    .replace(/\\5c/g, '')

  // detect if we have unescaped sequences
  var search = tmp.search(/\\| |"|&|'|\/|:|<|>|@/g)
  if (search === -1) {
    return false
  } else {
    return true
  }
}

/**
 * Escape the local part of a JID.
 *
 * @see http://xmpp.org/extensions/xep-0106.html
 * @param String local local part of a jid
 * @return An escaped local part
 */
module.exports.escape = function (local) {
  if (local === null) return null

  return local
    .replace(/^\s+|\s+$/g, '')
    .replace(/\\/g, '\\5c')
    .replace(/ /g, '\\20')
    .replace(/"/g, '\\22')
    .replace(/&/g, '\\26')
    .replace(/'/g, '\\27')
    .replace(/\//g, '\\2f')
    .replace(/:/g, '\\3a')
    .replace(/</g, '\\3c')
    .replace(/>/g, '\\3e')
    .replace(/@/g, '\\40')
    .replace(/\3a/g, '\u0005c3a')
}

/**
 * Unescape a local part of a JID.
 *
 * @see http://xmpp.org/extensions/xep-0106.html
 * @param String local local part of a jid
 * @return unescaped local part
 */
module.exports.unescape = function (local) {
  if (local === null) return null

  return local
    .replace(/\\20/g, ' ')
    .replace(/\\22/g, '"')
    .replace(/\\26/g, '&')
    .replace(/\\27/g, '\'')
    .replace(/\\2f/g, '/')
    .replace(/\\3a/g, ':')
    .replace(/\\3c/g, '<')
    .replace(/\\3e/g, '>')
    .replace(/\\40/g, '@')
    .replace(/\\5c/g, '\\')
}

},{}],4:[function(require,module,exports){
'use strict'

var JID = require('./JID')

module.exports = function tag (/* [literals], ...substitutions */) {
  var literals = arguments[0]
  var substitutions = Array.prototype.slice.call(arguments, 1)

  var str = ''

  for (var i = 0; i < substitutions.length; i++) {
    str += literals[i]
    str += substitutions[i]
  }
  str += literals[literals.length - 1]

  return new JID(str)
}

},{"./JID":2}]},{},[1])(1)
});