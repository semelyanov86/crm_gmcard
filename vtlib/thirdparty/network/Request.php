<?php

/**
 * Class for performing HTTP requests.
 *
 * PHP versions 4 and 5
 *
 * LICENSE:
 *
 * Copyright (c) 2002-2007, Richard Heyes
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * o Redistributions of source code must retain the above copyright
 *   notice, this list of conditions and the following disclaimer.
 * o Redistributions in binary form must reproduce the above copyright
 *   notice, this list of conditions and the following disclaimer in the
 *   documentation and/or other materials provided with the distribution.
 * o The names of the authors may not be used to endorse or promote
 *   products derived from this software without specific prior written
 *   permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category    HTTP
 * @author      Richard Heyes <richard@phpguru.org>
 * @author      Alexey Borzov <avb@php.net>
 * @copyright   2002-2007 Richard Heyes
 * @license     http://opensource.org/licenses/bsd-license.php New BSD License
 * @version     CVS: $Id: Request.php,v 1.63 2008/10/11 11:07:10 avb Exp $
 * @see        http://pear.php.net/package/HTTP_Request/
 */

/**
 * PEAR and PEAR_Error classes (for error handling).
 */
require_once dirname(__FILE__) . '/PEAR.php';

/**
 * Socket class.
 */
require_once dirname(__FILE__) . '/Net/Socket.php';
/**
 * URL handling class.
 */
require_once dirname(__FILE__) . '/Net/URL.php';

/*#@+
 * Constants for HTTP request methods
 */
define('HTTP_REQUEST_METHOD_GET', 'GET');
define('HTTP_REQUEST_METHOD_HEAD', 'HEAD');
define('HTTP_REQUEST_METHOD_POST', 'POST');
define('HTTP_REQUEST_METHOD_PUT', 'PUT');
define('HTTP_REQUEST_METHOD_DELETE', 'DELETE');
define('HTTP_REQUEST_METHOD_OPTIONS', 'OPTIONS');
define('HTTP_REQUEST_METHOD_TRACE', 'TRACE');
/*#@-*/

/*#@+
 * Constants for HTTP request error codes
 */
define('HTTP_REQUEST_ERROR_FILE', 1);
define('HTTP_REQUEST_ERROR_URL', 2);
define('HTTP_REQUEST_ERROR_PROXY', 4);
define('HTTP_REQUEST_ERROR_REDIRECTS', 8);
define('HTTP_REQUEST_ERROR_RESPONSE', 16);
define('HTTP_REQUEST_ERROR_GZIP_METHOD', 32);
define('HTTP_REQUEST_ERROR_GZIP_READ', 64);
define('HTTP_REQUEST_ERROR_GZIP_DATA', 128);
define('HTTP_REQUEST_ERROR_GZIP_CRC', 256);
/*#@-*/

/*#@+
 * Constants for HTTP protocol versions
 */
define('HTTP_REQUEST_HTTP_VER_1_0', '1.0');
define('HTTP_REQUEST_HTTP_VER_1_1', '1.1');
/*#@-*/

if (extension_loaded('mbstring') && (2 & ini_get('mbstring.func_overload'))) {
    /**
     * Whether string functions are overloaded by their mbstring equivalents.
     */
    define('HTTP_REQUEST_MBSTRING', true);
} else {
    /**
     * @ignore
     */
    define('HTTP_REQUEST_MBSTRING', false);
}

/**
 * Class for performing HTTP requests.
 *
 * Simple example (fetches yahoo.com and displays it):
 * <code>
 * $a = new HTTP_Request('http://www.yahoo.com/');
 * $a->sendRequest();
 * echo $a->getResponseBody();
 * </code>
 *
 * @category    HTTP
 * @author      Richard Heyes <richard@phpguru.org>
 * @author      Alexey Borzov <avb@php.net>
 * @version     Release: 1.4.4
 */
class HTTP_Request
{
    /*#@+
     * @access private
     */
    /**
     * Instance of Net_URL.
     * @var Net_URL
     */
    public $_url;

    /**
     * Type of request.
     * @var string
     */
    public $_method;

    /**
     * HTTP Version.
     * @var string
     */
    public $_http;

    /**
     * Request headers.
     * @var array
     */
    public $_requestHeaders;

    /**
     * Basic Auth Username.
     * @var string
     */
    public $_user;

    /**
     * Basic Auth Password.
     * @var string
     */
    public $_pass;

    /**
     * Socket object.
     * @var Net_Socket
     */
    public $_sock;

    /**
     * Proxy server.
     * @var string
     */
    public $_proxy_host;

    /**
     * Proxy port.
     * @var int
     */
    public $_proxy_port;

    /**
     * Proxy username.
     * @var string
     */
    public $_proxy_user;

    /**
     * Proxy password.
     * @var string
     */
    public $_proxy_pass;

    /**
     * Post data.
     * @var array
     */
    public $_postData;

    /**
     * Request body.
     * @var string
     */
    public $_body;

    /**
     * A list of methods that MUST NOT have a request body, per RFC 2616.
     * @var array
     */
    public $_bodyDisallowed = ['TRACE'];

    /**
     * Methods having defined semantics for request body.
     *
     * Content-Length header (indicating that the body follows, section 4.3 of
     * RFC 2616) will be sent for these methods even if no body was added
     *
     * @var array
     */
    public $_bodyRequired = ['POST', 'PUT'];

    /**
     * Files to post.
     * @var array
     */
    public $_postFiles = [];

    /**
     * Connection timeout.
     * @var float
     */
    public $_timeout;

    /**
     * HTTP_Response object.
     * @var HTTP_Response
     */
    public $_response;

    /**
     * Whether to allow redirects.
     * @var bool
     */
    public $_allowRedirects;

    /**
     * Maximum redirects allowed.
     * @var int
     */
    public $_maxRedirects;

    /**
     * Current number of redirects.
     * @var int
     */
    public $_redirects;

    /**
     * Whether to append brackets [] to array variables.
     * @var bool
     */
    public $_useBrackets = true;

    /**
     * Attached listeners.
     * @var array
     */
    public $_listeners = [];

    /**
     * Whether to save response body in response object property.
     * @var bool
     */
    public $_saveBody = true;

    /**
     * Timeout for reading from socket (array(seconds, microseconds)).
     * @var array
     */
    public $_readTimeout;

    /**
     * Options to pass to Net_Socket::connect. See stream_context_create.
     * @var array
     */
    public $_socketOptions;
    /*#@-*/

    /**
     * Constructor.
     *
     * Sets up the object
     * @param    string  The url to fetch/access
     * @param    array   Associative array of parameters which can have the following keys:
     * <ul>
     *   <li>method         - Method to use, GET, POST etc (string)</li>
     *   <li>http           - HTTP Version to use, 1.0 or 1.1 (string)</li>
     *   <li>user           - Basic Auth username (string)</li>
     *   <li>pass           - Basic Auth password (string)</li>
     *   <li>proxy_host     - Proxy server host (string)</li>
     *   <li>proxy_port     - Proxy server port (integer)</li>
     *   <li>proxy_user     - Proxy auth username (string)</li>
     *   <li>proxy_pass     - Proxy auth password (string)</li>
     *   <li>timeout        - Connection timeout in seconds (float)</li>
     *   <li>allowRedirects - Whether to follow redirects or not (bool)</li>
     *   <li>maxRedirects   - Max number of redirects to follow (integer)</li>
     *   <li>useBrackets    - Whether to append [] to array variable names (bool)</li>
     *   <li>saveBody       - Whether to save response body in response object property (bool)</li>
     *   <li>readTimeout    - Timeout for reading / writing data over the socket (array (seconds, microseconds))</li>
     *   <li>socketOptions  - Options to pass to Net_Socket object (array)</li>
     * </ul>
     */
    public function __construct($url = '', $params = [])
    {
        $this->_method         =  HTTP_REQUEST_METHOD_GET;
        $this->_http           =  HTTP_REQUEST_HTTP_VER_1_1;
        $this->_requestHeaders = [];
        $this->_postData       = [];
        $this->_body           = null;

        $this->_user = null;
        $this->_pass = null;

        $this->_proxy_host = null;
        $this->_proxy_port = null;
        $this->_proxy_user = null;
        $this->_proxy_pass = null;

        $this->_allowRedirects = false;
        $this->_maxRedirects   = 3;
        $this->_redirects      = 0;

        $this->_timeout  = null;
        $this->_response = null;

        foreach ($params as $key => $value) {
            $this->{'_' . $key} = $value;
        }

        if (!empty($url)) {
            $this->setURL($url);
        }

        // Default useragent
        $this->addHeader('User-Agent', 'PEAR HTTP_Request class ( http://pear.php.net/ )');

        // We don't do keep-alives by default
        $this->addHeader('Connection', 'close');

        // Basic authentication
        if (!empty($this->_user)) {
            $this->addHeader('Authorization', 'Basic ' . base64_encode($this->_user . ':' . $this->_pass));
        }

        // Proxy authentication (see bug #5913)
        if (!empty($this->_proxy_user)) {
            $this->addHeader('Proxy-Authorization', 'Basic ' . base64_encode($this->_proxy_user . ':' . $this->_proxy_pass));
        }

        // Use gzip encoding if possible
        if ($this->_http == HTTP_REQUEST_HTTP_VER_1_1 && extension_loaded('zlib')) {
            $this->addHeader('Accept-Encoding', 'gzip');
        }
    }

    public function HTTP_Request($url = '', $params = [])
    {
        self::__construct($url, $params);
    }

    /**
     * Generates a Host header for HTTP/1.1 requests.
     *
     * @return string
     */
    public function _generateHostHeader()
    {
        if ($this->_url->port != 80 and strcasecmp($this->_url->protocol, 'http') == 0) {
            $host = $this->_url->host . ':' . $this->_url->port;

        } elseif ($this->_url->port != 443 and strcasecmp($this->_url->protocol, 'https') == 0) {
            $host = $this->_url->host . ':' . $this->_url->port;

        } elseif ($this->_url->port == 443 and strcasecmp($this->_url->protocol, 'https') == 0 and strpos($this->_url->url, ':443') !== false) {
            $host = $this->_url->host . ':' . $this->_url->port;

        } else {
            $host = $this->_url->host;
        }

        return $host;
    }

    /**
     * Resets the object to its initial state (DEPRECATED).
     * Takes the same parameters as the constructor.
     *
     * @param  string $url    The url to be requested
     * @param  array  $params Associative array of parameters
     *                        (see constructor for details)
     * @deprecated deprecated since 1.2, call the constructor if this is necessary
     */
    public function reset($url, $params = [])
    {
        $this->HTTP_Request($url, $params);
    }

    /**
     * Sets the URL to be requested.
     *
     * @param  string The url to be requested
     */
    public function setURL($url)
    {
        $this->_url = new Net_URL($url, $this->_useBrackets);

        if (!empty($this->_url->user) || !empty($this->_url->pass)) {
            $this->setBasicAuth($this->_url->user, $this->_url->pass);
        }

        if ($this->_http == HTTP_REQUEST_HTTP_VER_1_1) {
            $this->addHeader('Host', $this->_generateHostHeader());
        }

        // set '/' instead of empty path rather than check later (see bug #8662)
        if (empty($this->_url->path)) {
            $this->_url->path = '/';
        }
    }

    /**
     * Returns the current request URL.
     *
     * @return   string  Current request URL
     */
    public function getUrl()
    {
        return empty($this->_url) ? '' : $this->_url->getUrl();
    }

    /**
     * Sets a proxy to be used.
     *
     * @param string     Proxy host
     * @param int        Proxy port
     * @param string     Proxy username
     * @param string     Proxy password
     */
    public function setProxy($host, $port = 8_080, $user = null, $pass = null)
    {
        $this->_proxy_host = $host;
        $this->_proxy_port = $port;
        $this->_proxy_user = $user;
        $this->_proxy_pass = $pass;

        if (!empty($user)) {
            $this->addHeader('Proxy-Authorization', 'Basic ' . base64_encode($user . ':' . $pass));
        }
    }

    /**
     * Sets basic authentication parameters.
     *
     * @param string     Username
     * @param string     Password
     */
    public function setBasicAuth($user, $pass)
    {
        $this->_user = $user;
        $this->_pass = $pass;

        $this->addHeader('Authorization', 'Basic ' . base64_encode($user . ':' . $pass));
    }

    /**
     * Sets the method to be used, GET, POST etc.
     *
     * @param string     Method to use. Use the defined constants for this
     */
    public function setMethod($method)
    {
        $this->_method = $method;
    }

    /**
     * Sets the HTTP version to use, 1.0 or 1.1.
     *
     * @param string     Version to use. Use the defined constants for this
     */
    public function setHttpVer($http)
    {
        $this->_http = $http;
    }

    /**
     * Adds a request header.
     *
     * @param string     Header name
     * @param string     Header value
     */
    public function addHeader($name, $value)
    {
        $this->_requestHeaders[strtolower($name)] = $value;
    }

    /**
     * Removes a request header.
     *
     * @param string     Header name to remove
     */
    public function removeHeader($name)
    {
        if (isset($this->_requestHeaders[strtolower($name)])) {
            unset($this->_requestHeaders[strtolower($name)]);
        }
    }

    /**
     * Adds a querystring parameter.
     *
     * @param string     Querystring parameter name
     * @param string     Querystring parameter value
     * @param bool       Whether the value is already urlencoded or not, default = not
     */
    public function addQueryString($name, $value, $preencoded = false)
    {
        $this->_url->addQueryString($name, $value, $preencoded);
    }

    /**
     * Sets the querystring to literally what you supply.
     *
     * @param string     The querystring data. Should be of the format foo=bar&x=y etc
     * @param bool       Whether data is already urlencoded or not, default = already encoded
     */
    public function addRawQueryString($querystring, $preencoded = true)
    {
        $this->_url->addRawQueryString($querystring, $preencoded);
    }

    /**
     * Adds postdata items.
     *
     * @param string     Post data name
     * @param string     Post data value
     * @param bool       Whether data is already urlencoded or not, default = not
     */
    public function addPostData($name, $value, $preencoded = false)
    {
        if ($preencoded) {
            $this->_postData[$name] = $value;
        } else {
            $this->_postData[$name] = $this->_arrayMapRecursive('urlencode', $value);
        }
    }

    /**
     * Recursively applies the callback function to the value.
     *
     * @param    mixed   Callback function
     * @param    mixed   Value to process
     * @return   mixed   Processed value
     */
    public function _arrayMapRecursive($callback, $value)
    {
        if (!is_array($value)) {
            return call_user_func($callback, $value);
        }
        $map = [];
        foreach ($value as $k => $v) {
            $map[$k] = $this->_arrayMapRecursive($callback, $v);
        }

        return $map;

    }

    /**
     * Adds a file to form-based file upload.
     *
     * Used to emulate file upload via a HTML form. The method also sets
     * Content-Type of HTTP request to 'multipart/form-data'.
     *
     * If you just want to send the contents of a file as the body of HTTP
     * request you should use setBody() method.
     *
     * @param  string    name of file-upload field
     * @param  mixed     file name(s)
     * @param  mixed     content-type(s) of file(s) being uploaded
     * @return bool      true on success
     * @throws PEAR_Error
     */
    public function addFile($inputName, $fileName, $contentType = 'application/octet-stream')
    {
        if (!is_array($fileName) && !is_readable($fileName)) {
            return PEAR::raiseError("File '{$fileName}' is not readable", HTTP_REQUEST_ERROR_FILE);
        }
        if (is_array($fileName)) {
            foreach ($fileName as $name) {
                if (!is_readable($name)) {
                    return PEAR::raiseError("File '{$name}' is not readable", HTTP_REQUEST_ERROR_FILE);
                }
            }
        }
        $this->addHeader('Content-Type', 'multipart/form-data');
        $this->_postFiles[$inputName] = [
            'name' => $fileName,
            'type' => $contentType,
        ];

        return true;
    }

    /**
     * Adds raw postdata (DEPRECATED).
     *
     * @param string     The data
     * @param bool       Whether data is preencoded or not, default = already encoded
     * @deprecated       deprecated since 1.3.0, method setBody() should be used instead
     */
    public function addRawPostData($postdata, $preencoded = true)
    {
        $this->_body = $preencoded ? $postdata : urlencode($postdata);
    }

    /**
     * Sets the request body (for POST, PUT and similar requests).
     *
     * @param    string  Request body
     */
    public function setBody($body)
    {
        $this->_body = $body;
    }

    /**
     * Clears any postdata that has been added (DEPRECATED).
     *
     * Useful for multiple request scenarios.
     *
     * @deprecated deprecated since 1.2
     */
    public function clearPostData()
    {
        $this->_postData = null;
    }

    /**
     * Appends a cookie to "Cookie:" header.
     *
     * @param string $name cookie name
     * @param string $value cookie value
     */
    public function addCookie($name, $value)
    {
        $cookies = isset($this->_requestHeaders['cookie']) ? $this->_requestHeaders['cookie'] . '; ' : '';
        $this->addHeader('Cookie', $cookies . $name . '=' . $value);
    }

    /**
     * Clears any cookies that have been added (DEPRECATED).
     *
     * Useful for multiple request scenarios
     *
     * @deprecated deprecated since 1.2
     */
    public function clearCookies()
    {
        $this->removeHeader('Cookie');
    }

    /**
     * Sends the request.
     *
     * @param  bool   Whether to store response body in Response object property,
     *                set this to false if downloading a LARGE file and using a Listener
     * @return mixed  PEAR error on error, true otherwise
     */
    public function sendRequest($saveBody = true)
    {
        if (!is_a($this->_url, 'Net_URL')) {
            return PEAR::raiseError('No URL given', HTTP_REQUEST_ERROR_URL);
        }

        $host = $this->_proxy_host ?? $this->_url->host;
        $port = $this->_proxy_port ?? $this->_url->port;

        if (strcasecmp($this->_url->protocol, 'https') == 0) {
            // Bug #14127, don't try connecting to HTTPS sites without OpenSSL
            if (version_compare(PHP_VERSION, '4.3.0', '<') || !extension_loaded('openssl')) {
                return PEAR::raiseError(
                    'Need PHP 4.3.0 or later with OpenSSL support for https:// requests',
                    HTTP_REQUEST_ERROR_URL,
                );
            }
            if (isset($this->_proxy_host)) {
                return PEAR::raiseError('HTTPS proxies are not supported', HTTP_REQUEST_ERROR_PROXY);
            }
            $host = 'ssl://' . $host;
        }

        // magic quotes may fuck up file uploads and chunked response processing
        $magicQuotes = ini_get('magic_quotes_runtime');
        ini_set('magic_quotes_runtime', false);

        // RFC 2068, section 19.7.1: A client MUST NOT send the Keep-Alive
        // connection token to a proxy server...
        if (isset($this->_proxy_host) && !empty($this->_requestHeaders['connection'])
            && $this->_requestHeaders['connection'] == 'Keep-Alive') {
            $this->removeHeader('connection');
        }

        $keepAlive = ($this->_http == HTTP_REQUEST_HTTP_VER_1_1 && empty($this->_requestHeaders['connection']))
                     || (!empty($this->_requestHeaders['connection']) && $this->_requestHeaders['connection'] == 'Keep-Alive');
        $sockets   = PEAR::getStaticProperty('HTTP_Request', 'sockets');
        $sockKey   = $host . ':' . $port;
        unset($this->_sock);

        // There is a connected socket in the "static" property?
        if ($keepAlive && !empty($sockets[$sockKey])
            && !empty($sockets[$sockKey]->fp)) {
            $this->_sock = $sockets[$sockKey];
            $err = null;
        } else {
            $this->_notify('connect');
            $this->_sock = new Net_Socket();
            $err = $this->_sock->connect($host, $port, null, $this->_timeout, $this->_socketOptions);
        }
        PEAR::isError($err) or $err = $this->_sock->write($this->_buildRequest());

        if (!PEAR::isError($err)) {
            if (!empty($this->_readTimeout)) {
                $this->_sock->setTimeout($this->_readTimeout[0], $this->_readTimeout[1]);
            }

            $this->_notify('sentRequest');

            // Read the response
            $this->_response = new HTTP_Response($this->_sock, $this->_listeners);
            $err = $this->_response->process(
                $this->_saveBody && $saveBody,
                $this->_method != HTTP_REQUEST_METHOD_HEAD,
            );

            if ($keepAlive) {
                $keepAlive = (isset($this->_response->_headers['content-length'])
                              || (isset($this->_response->_headers['transfer-encoding'])
                                  && strtolower($this->_response->_headers['transfer-encoding']) == 'chunked'));
                if ($keepAlive) {
                    if (isset($this->_response->_headers['connection'])) {
                        $keepAlive = strtolower($this->_response->_headers['connection']) == 'keep-alive';
                    } else {
                        $keepAlive = 'HTTP/' . HTTP_REQUEST_HTTP_VER_1_1 == $this->_response->_protocol;
                    }
                }
            }
        }

        ini_set('magic_quotes_runtime', $magicQuotes);

        if (PEAR::isError($err)) {
            return $err;
        }

        if (!$keepAlive) {
            $this->disconnect();
            // Store the connected socket in "static" property
        } elseif (empty($sockets[$sockKey]) || empty($sockets[$sockKey]->fp)) {
            $sockets[$sockKey] = $this->_sock;
        }

        // Check for redirection
        if ($this->_allowRedirects
            and $this->_redirects <= $this->_maxRedirects
            and $this->getResponseCode() > 300
            and $this->getResponseCode() < 399
            and !empty($this->_response->_headers['location'])) {


            $redirect = $this->_response->_headers['location'];

            // Absolute URL
            if (preg_match('/^https?:\/\//i', $redirect)) {
                $this->_url = new Net_URL($redirect);
                $this->addHeader('Host', $this->_generateHostHeader());
                // Absolute path
            } elseif ($redirect[0] == '/') {
                $this->_url->path = $redirect;

                // Relative path
            } elseif (substr($redirect, 0, 3) == '../' or substr($redirect, 0, 2) == './') {
                if (substr($this->_url->path, -1) == '/') {
                    $redirect = $this->_url->path . $redirect;
                } else {
                    $redirect = dirname($this->_url->path) . '/' . $redirect;
                }
                $redirect = Net_URL::resolvePath($redirect);
                $this->_url->path = $redirect;

                // Filename, no path
            } else {
                if (substr($this->_url->path, -1) == '/') {
                    $redirect = $this->_url->path . $redirect;
                } else {
                    $redirect = dirname($this->_url->path) . '/' . $redirect;
                }
                $this->_url->path = $redirect;
            }

            ++$this->_redirects;

            return $this->sendRequest($saveBody);

            // Too many redirects
        }
        if ($this->_allowRedirects and $this->_redirects > $this->_maxRedirects) {
            return PEAR::raiseError('Too many redirects', HTTP_REQUEST_ERROR_REDIRECTS);
        }

        return true;
    }

    /**
     * Disconnect the socket, if connected. Only useful if using Keep-Alive.
     */
    public function disconnect()
    {
        if (!empty($this->_sock) && !empty($this->_sock->fp)) {
            $this->_notify('disconnect');
            $this->_sock->disconnect();
        }
    }

    /**
     * Returns the response code.
     *
     * @return mixed     Response code, false if not set
     */
    public function getResponseCode()
    {
        return $this->_response->_code ?? false;
    }

    /**
     * Returns the response reason phrase.
     *
     * @return mixed     Response reason phrase, false if not set
     */
    public function getResponseReason()
    {
        return $this->_response->_reason ?? false;
    }

    /**
     * Returns either the named header or all if no name given.
     *
     * @param string     The header name to return, do not set to get all headers
     * @return mixed     either the value of $headername (false if header is not present)
     *                   or an array of all headers
     */
    public function getResponseHeader($headername = null)
    {
        if (!isset($headername)) {
            return $this->_response->_headers ?? [];
        }
        $headername = strtolower($headername);

        return $this->_response->_headers[$headername] ?? false;

    }

    /**
     * Returns the body of the response.
     *
     * @return mixed     response body, false if not set
     */
    public function getResponseBody()
    {
        return $this->_response->_body ?? false;
    }

    /**
     * Returns cookies set in response.
     *
     * @return mixed     array of response cookies, false if none are present
     */
    public function getResponseCookies()
    {
        return $this->_response->_cookies ?? false;
    }

    /**
     * Builds the request string.
     *
     * @return string The request string
     */
    public function _buildRequest()
    {
        $separator = ini_get('arg_separator.output');
        ini_set('arg_separator.output', '&');
        $querystring = ($querystring = $this->_url->getQueryString()) ? '?' . $querystring : '';
        ini_set('arg_separator.output', $separator);

        $host = isset($this->_proxy_host) ? $this->_url->protocol . '://' . $this->_url->host : '';
        $port = (isset($this->_proxy_host) and $this->_url->port != 80) ? ':' . $this->_url->port : '';
        $path = $this->_url->path . $querystring;
        $url  = $host . $port . $path;

        if (!strlen($url)) {
            $url = '/';
        }

        $request = $this->_method . ' ' . $url . ' HTTP/' . $this->_http . "\r\n";

        if (in_array($this->_method, $this->_bodyDisallowed)
            || (strlen($this->_body ? $this->_body : '') == 0 && ($this->_method != HTTP_REQUEST_METHOD_POST
             || (empty($this->_postData) && empty($this->_postFiles))))) {
            $this->removeHeader('Content-Type');
        } else {
            if (empty($this->_requestHeaders['content-type'])) {
                // Add default content-type
                $this->addHeader('Content-Type', 'application/x-www-form-urlencoded');
            } elseif ($this->_requestHeaders['content-type'] == 'multipart/form-data') {
                $boundary = 'HTTP_Request_' . md5(uniqid('request') . microtime());
                $this->addHeader('Content-Type', 'multipart/form-data; boundary=' . $boundary);
            }
        }

        // Request Headers
        if (!empty($this->_requestHeaders)) {
            foreach ($this->_requestHeaders as $name => $value) {
                $canonicalName = implode('-', array_map('ucfirst', explode('-', $name)));
                $request      .= $canonicalName . ': ' . $value . "\r\n";
            }
        }

        // Method does not allow a body, simply add a final CRLF
        if (in_array($this->_method, $this->_bodyDisallowed)) {

            $request .= "\r\n";

            // Post data if it's an array
        } elseif ($this->_method == HTTP_REQUEST_METHOD_POST
                  && (!empty($this->_postData) || !empty($this->_postFiles))) {

            // "normal" POST request
            if (!isset($boundary)) {
                $callback = static function ($a) { return $a[0] . '=' . $a[1]; };

                $postdata = implode('&', array_map($callback, $this->_flattenArray('', $this->_postData)));

                // multipart request, probably with file uploads
            } else {
                $postdata = '';
                if (!empty($this->_postData)) {
                    $flatData = $this->_flattenArray('', $this->_postData);
                    foreach ($flatData as $item) {
                        $postdata .= '--' . $boundary . "\r\n";
                        $postdata .= 'Content-Disposition: form-data; name="' . $item[0] . '"';
                        $postdata .= "\r\n\r\n" . urldecode($item[1]) . "\r\n";
                    }
                }
                foreach ($this->_postFiles as $name => $value) {
                    if (is_array($value['name'])) {
                        $varname       = $name . ($this->_useBrackets ? '[]' : '');
                    } else {
                        $varname       = $name;
                        $value['name'] = [$value['name']];
                    }
                    foreach ($value['name'] as $key => $filename) {
                        $fp       = fopen($filename, 'r');
                        $basename = basename($filename);
                        $type     = is_array($value['type']) ? @$value['type'][$key] : $value['type'];

                        $postdata .= '--' . $boundary . "\r\n";
                        $postdata .= 'Content-Disposition: form-data; name="' . $varname . '"; filename="' . $basename . '"';
                        $postdata .= "\r\nContent-Type: " . $type;
                        $postdata .= "\r\n\r\n" . fread($fp, filesize($filename)) . "\r\n";
                        fclose($fp);
                    }
                }
                $postdata .= '--' . $boundary . "--\r\n";
            }
            $request .= 'Content-Length: '
                        . (HTTP_REQUEST_MBSTRING ? mb_strlen($postdata, 'iso-8859-1') : strlen($postdata))
                        . "\r\n\r\n";
            $request .= $postdata;

            // Explicitly set request body
        } elseif (strlen($this->_body) > 0) {

            $request .= 'Content-Length: '
                        . (HTTP_REQUEST_MBSTRING ? mb_strlen($this->_body, 'iso-8859-1') : strlen($this->_body))
                        . "\r\n\r\n";
            $request .= $this->_body;

            // No body: send a Content-Length header nonetheless (request #12900),
            // but do that only for methods that require a body (bug #14740)
        } else {

            if (in_array($this->_method, $this->_bodyRequired)) {
                $request .= "Content-Length: 0\r\n";
            }
            $request .= "\r\n";
        }

        return $request;
    }

    /**
     * Helper function to change the (probably multidimensional) associative array
     * into the simple one.
     *
     * @param    string  name for item
     * @param    mixed   item's values
     * @return   array   array with the following items: array('item name', 'item value');
     */
    public function _flattenArray($name, $values)
    {
        if (!is_array($values)) {
            return [[$name, $values]];
        }
        $ret = [];
        foreach ($values as $k => $v) {
            if (empty($name)) {
                $newName = $k;
            } elseif ($this->_useBrackets) {
                $newName = $name . '[' . $k . ']';
            } else {
                $newName = $name;
            }
            $ret = array_merge($ret, $this->_flattenArray($newName, $v));
        }

        return $ret;

    }

    /**
     * Adds a Listener to the list of listeners that are notified of
     * the object's events.
     *
     * Events sent by HTTP_Request object
     * - 'connect': on connection to server
     * - 'sentRequest': after the request was sent
     * - 'disconnect': on disconnection from server
     *
     * Events sent by HTTP_Response object
     * - 'gotHeaders': after receiving response headers (headers are passed in $data)
     * - 'tick': on receiving a part of response body (the part is passed in $data)
     * - 'gzTick': on receiving a gzip-encoded part of response body (ditto)
     * - 'gotBody': after receiving the response body (passes the decoded body in $data if it was gzipped)
     *
     * @param    HTTP_Request_Listener   listener to attach
     * @return   bool                 whether the listener was successfully attached
     */
    public function attach(&$listener)
    {
        if (!is_a($listener, 'HTTP_Request_Listener')) {
            return false;
        }
        $this->_listeners[$listener->getId()] = $listener;

        return true;
    }

    /**
     * Removes a Listener from the list of listeners.
     *
     * @param    HTTP_Request_Listener   listener to detach
     * @return   bool                 whether the listener was successfully detached
     */
    public function detach(&$listener)
    {
        if (!is_a($listener, 'HTTP_Request_Listener')
            || !isset($this->_listeners[$listener->getId()])) {
            return false;
        }
        unset($this->_listeners[$listener->getId()]);

        return true;
    }

    /**
     * Notifies all registered listeners of an event.
     *
     * @param    string  Event name
     * @param    mixed   Additional data
     * @see      HTTP_Request::attach()
     */
    public function _notify($event, $data = null)
    {
        foreach (array_keys($this->_listeners) as $id) {
            $this->_listeners[$id]->update($this, $event, $data);
        }
    }
}


/**
 * Response class to complement the Request class.
 *
 * @category    HTTP
 * @author      Richard Heyes <richard@phpguru.org>
 * @author      Alexey Borzov <avb@php.net>
 * @version     Release: 1.4.4
 */
class HTTP_Response
{
    /**
     * Socket object.
     * @var Net_Socket
     */
    public $_sock;

    /**
     * Protocol.
     * @var string
     */
    public $_protocol;

    /**
     * Return code.
     * @var string
     */
    public $_code;

    /**
     * Response reason phrase.
     * @var string
     */
    public $_reason;

    /**
     * Response headers.
     * @var array
     */
    public $_headers;

    /**
     * Cookies set in response.
     * @var array
     */
    public $_cookies;

    /**
     * Response body.
     * @var string
     */
    public $_body = '';

    /**
     * Used by _readChunked(): remaining length of the current chunk.
     * @var string
     */
    public $_chunkLength = 0;

    /**
     * Attached listeners.
     * @var array
     */
    public $_listeners = [];

    /**
     * Bytes left to read from message-body.
     * @var null|int
     */
    public $_toRead;

    /**
     * Constructor.
     *
     * @param  Net_Socket    socket to read the response from
     * @param  array         listeners attached to request
     */
    public function __construct(&$sock, &$listeners)
    {
        $this->_sock      = $sock;
        $this->_listeners = $listeners;
    }

    public function HTTP_Response(&$sock, &$listeners)
    {
        self::__construct($sock, $listeners);
    }

    /**
     * Processes a HTTP response.
     *
     * This extracts response code, headers, cookies and decodes body if it
     * was encoded in some way
     *
     * @param  bool      Whether to store response body in object property, set
     *                   this to false if downloading a LARGE file and using a Listener.
     *                   This is assumed to be true if body is gzip-encoded.
     * @param  bool      Whether the response can actually have a message-body.
     *                   Will be set to false for HEAD requests.
     * @return mixed     true on success, PEAR_Error in case of malformed response
     * @throws PEAR_Error
     */
    public function process($saveBody = true, $canHaveBody = true)
    {
        do {
            $line = $this->_sock->readLine();
            if (!preg_match('!^(HTTP/\d\.\d) (\d{3})(?: (.+))?!', $line, $s)) {
                return PEAR::raiseError('Malformed response', HTTP_REQUEST_ERROR_RESPONSE);
            }
            $this->_protocol = $s[1];
            $this->_code     = intval($s[2]);
            $this->_reason   = empty($s[3]) ? null : $s[3];

            while ('' !== ($header = $this->_sock->readLine())) {
                $this->_processHeader($header);
            }
        } while ($this->_code == 100);

        $this->_notify('gotHeaders', $this->_headers);

        // RFC 2616, section 4.4:
        // 1. Any response message which "MUST NOT" include a message-body ...
        // is always terminated by the first empty line after the header fields
        // 3. ... If a message is received with both a
        // Transfer-Encoding header field and a Content-Length header field,
        // the latter MUST be ignored.
        $canHaveBody = $canHaveBody && $this->_code >= 200
                       && $this->_code != 204 && $this->_code != 304;

        // If response body is present, read it and decode
        $chunked = isset($this->_headers['transfer-encoding']) && ($this->_headers['transfer-encoding'] == 'chunked');
        $gzipped = isset($this->_headers['content-encoding']) && ($this->_headers['content-encoding'] == 'gzip');
        $hasBody = false;
        if ($canHaveBody && ($chunked || !isset($this->_headers['content-length'])
                || $this->_headers['content-length'] != 0)) {
            if ($chunked || !isset($this->_headers['content-length'])) {
                $this->_toRead = null;
            } else {
                $this->_toRead = $this->_headers['content-length'];
            }

            while (!$this->_sock->eof() && (is_null($this->_toRead) || $this->_toRead > 0)) {
                if ($chunked) {
                    $data = $this->_readChunked();
                } elseif (is_null($this->_toRead)) {
                    $data = $this->_sock->read(4_096);
                } else {
                    $data = $this->_sock->read(min(4_096, $this->_toRead));
                    $this->_toRead -= HTTP_REQUEST_MBSTRING ? mb_strlen($data, 'iso-8859-1') : strlen($data);
                }
                if ($data == '' && (!$this->_chunkLength || $this->_sock->eof())) {
                    break;
                }
                $hasBody = true;
                if ($saveBody || $gzipped) {
                    $this->_body .= $data;
                }
                $this->_notify($gzipped ? 'gzTick' : 'tick', $data);

            }
        }

        if ($hasBody) {
            // Uncompress the body if needed
            if ($gzipped) {
                $body = $this->_decodeGzip($this->_body);
                if (PEAR::isError($body)) {
                    return $body;
                }
                $this->_body = $body;
                $this->_notify('gotBody', $this->_body);
            } else {
                $this->_notify('gotBody');
            }
        }

        return true;
    }

    /**
     * Processes the response header.
     *
     * @param  string    HTTP header
     */
    public function _processHeader($header)
    {
        if (strpos($header, ':') === false) {
            return;
        }
        [$headername, $headervalue] = explode(':', $header, 2);
        $headername  = strtolower($headername);
        $headervalue = ltrim($headervalue);

        if ($headername != 'set-cookie') {
            if (isset($this->_headers[$headername])) {
                $this->_headers[$headername] .= ',' . $headervalue;
            } else {
                $this->_headers[$headername]  = $headervalue;
            }
        } else {
            $this->_parseCookie($headervalue);
        }
    }

    /**
     * Parse a Set-Cookie header to fill $_cookies array.
     *
     * @param  string    value of Set-Cookie header
     */
    public function _parseCookie($headervalue)
    {
        $cookie = [
            'expires' => null,
            'domain'  => null,
            'path'    => null,
            'secure'  => false,
        ];

        // Only a name=value pair
        if (!strpos($headervalue, ';')) {
            $pos = strpos($headervalue, '=');
            $cookie['name']  = trim(substr($headervalue, 0, $pos));
            $cookie['value'] = trim(substr($headervalue, $pos + 1));

            // Some optional parameters are supplied
        } else {
            $elements = explode(';', $headervalue);
            $pos = strpos($elements[0], '=');
            $cookie['name']  = trim(substr($elements[0], 0, $pos));
            $cookie['value'] = trim(substr($elements[0], $pos + 1));

            for ($i = 1; $i < count($elements); ++$i) {
                if (strpos($elements[$i], '=') === false) {
                    $elName  = trim($elements[$i]);
                    $elValue = null;
                } else {
                    [$elName, $elValue] = array_map('trim', explode('=', $elements[$i]));
                }
                $elName = strtolower($elName);
                if ($elName == 'secure') {
                    $cookie['secure'] = true;
                } elseif ($elName == 'expires') {
                    $cookie['expires'] = str_replace('"', '', $elValue);
                } elseif ($elName == 'path' || $elName == 'domain') {
                    $cookie[$elName] = urldecode($elValue);
                } else {
                    $cookie[$elName] = $elValue;
                }
            }
        }
        $this->_cookies[] = $cookie;
    }

    /**
     * Read a part of response body encoded with chunked Transfer-Encoding.
     *
     * @return string
     */
    public function _readChunked()
    {
        // at start of the next chunk?
        if ($this->_chunkLength == 0) {
            $line = $this->_sock->readLine();
            if (preg_match('/^([0-9a-f]+)/i', $line, $matches)) {
                $this->_chunkLength = hexdec($matches[1]);
                // Chunk with zero length indicates the end
                if ($this->_chunkLength == 0) {
                    $this->_sock->readLine(); // make this an eof()

                    return '';
                }
            } else {
                return '';
            }
        }
        $data = $this->_sock->read($this->_chunkLength);
        $this->_chunkLength -= HTTP_REQUEST_MBSTRING ? mb_strlen($data, 'iso-8859-1') : strlen($data);
        if ($this->_chunkLength == 0) {
            $this->_sock->readLine(); // Trailing CRLF
        }

        return $data;
    }

    /**
     * Notifies all registered listeners of an event.
     *
     * @param    string  Event name
     * @param    mixed   Additional data
     * @see HTTP_Request::_notify()
     */
    public function _notify($event, $data = null)
    {
        foreach (array_keys($this->_listeners) as $id) {
            $this->_listeners[$id]->update($this, $event, $data);
        }
    }

    /**
     * Decodes the message-body encoded by gzip.
     *
     * The real decoding work is done by gzinflate() built-in function, this
     * method only parses the header and checks data for compliance with
     * RFC 1952
     *
     * @param    string  gzip-encoded data
     * @return   string  decoded data
     */
    public function _decodeGzip($data)
    {
        if (HTTP_REQUEST_MBSTRING) {
            $oldEncoding = mb_internal_encoding();
            mb_internal_encoding('iso-8859-1');
        }
        $length = strlen($data);
        // If it doesn't look like gzip-encoded data, don't bother
        if ($length < 18 || strcmp(substr($data, 0, 2), "\x1f\x8b")) {
            return $data;
        }
        $method = ord(substr($data, 2, 1));
        if ($method != 8) {
            return PEAR::raiseError('_decodeGzip(): unknown compression method', HTTP_REQUEST_ERROR_GZIP_METHOD);
        }
        $flags = ord(substr($data, 3, 1));
        if ($flags & 224) {
            return PEAR::raiseError('_decodeGzip(): reserved bits are set', HTTP_REQUEST_ERROR_GZIP_DATA);
        }

        // header is 10 bytes minimum. may be longer, though.
        $headerLength = 10;
        // extra fields, need to skip 'em
        if ($flags & 4) {
            if ($length - $headerLength - 2 < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short', HTTP_REQUEST_ERROR_GZIP_DATA);
            }
            $extraLength = unpack('v', substr($data, 10, 2));
            if ($length - $headerLength - 2 - $extraLength[1] < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short', HTTP_REQUEST_ERROR_GZIP_DATA);
            }
            $headerLength += $extraLength[1] + 2;
        }
        // file name, need to skip that
        if ($flags & 8) {
            if ($length - $headerLength - 1 < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short', HTTP_REQUEST_ERROR_GZIP_DATA);
            }
            $filenameLength = strpos(substr($data, $headerLength), chr(0));
            if ($filenameLength === false || $length - $headerLength - $filenameLength - 1 < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short', HTTP_REQUEST_ERROR_GZIP_DATA);
            }
            $headerLength += $filenameLength + 1;
        }
        // comment, need to skip that also
        if ($flags & 16) {
            if ($length - $headerLength - 1 < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short', HTTP_REQUEST_ERROR_GZIP_DATA);
            }
            $commentLength = strpos(substr($data, $headerLength), chr(0));
            if ($commentLength === false || $length - $headerLength - $commentLength - 1 < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short', HTTP_REQUEST_ERROR_GZIP_DATA);
            }
            $headerLength += $commentLength + 1;
        }
        // have a CRC for header. let's check
        if ($flags & 1) {
            if ($length - $headerLength - 2 < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short', HTTP_REQUEST_ERROR_GZIP_DATA);
            }
            $crcReal   = 0xFF_FF & crc32(substr($data, 0, $headerLength));
            $crcStored = unpack('v', substr($data, $headerLength, 2));
            if ($crcReal != $crcStored[1]) {
                return PEAR::raiseError('_decodeGzip(): header CRC check failed', HTTP_REQUEST_ERROR_GZIP_CRC);
            }
            $headerLength += 2;
        }
        // unpacked data CRC and size at the end of encoded data
        $tmp = unpack('V2', substr($data, -8));
        $dataCrc  = $tmp[1];
        $dataSize = $tmp[2];

        // finally, call the gzinflate() function
        // don't pass $dataSize to gzinflate, see bugs #13135, #14370
        $unpacked = gzinflate(substr($data, $headerLength, -8));
        if ($unpacked === false) {
            return PEAR::raiseError('_decodeGzip(): gzinflate() call failed', HTTP_REQUEST_ERROR_GZIP_READ);
        }
        if ($dataSize != strlen($unpacked)) {
            return PEAR::raiseError('_decodeGzip(): data size check failed', HTTP_REQUEST_ERROR_GZIP_READ);
        }
        if ((0xFF_FF_FF_FF & $dataCrc) != (0xFF_FF_FF_FF & crc32($unpacked))) {
            return PEAR::raiseError('_decodeGzip(): data CRC check failed', HTTP_REQUEST_ERROR_GZIP_CRC);
        }
        if (HTTP_REQUEST_MBSTRING) {
            mb_internal_encoding($oldEncoding);
        }

        return $unpacked;
    }
} // End class HTTP_Response
