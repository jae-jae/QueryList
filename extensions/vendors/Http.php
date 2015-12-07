<?php

/**
 * 
 * @desc  HTTP 请求类, 支持 CURL 和 Socket, 默认使用 CURL , 当手动指定  
 *              useCurl 或者 curl 扩展没有安装时, 会使用 Socket
 *              目前支持 get 和 post 两种请求方式
 * 
 * @example 
 * 
1. 基本 get 请求: 

    $http = new Http();         // 实例化对象
    $result =  $http->get('http://weibo.com/at/comment');
    
2. 基本 post 请求: 

    $http = new Http();         // 实例化对象
    $result = $http->post('http://someurl.com/post-new-article', array('title'=>$title, 'body'=>$body) );
    
3. 模拟登录 ( post 和 get 同时使用, 利用 cookie 存储状态 ) : 

    $http = new Http();         // 实例化对象
    $http->setCookiepath(substr(md5($username), 0, 10));        // 设置 cookie, 如果是多个用户请求的话
    // 提交 post 数据
    $loginData = $http->post('http://login.sina.com.cn/sso/login.php?client=ssologin.js(v1.3.19)', array('username'=>$username, 'loginPass'=>$password) );
    $result =  $http->get('http://weibo.com/at/comment');
    
4. 利用 initialize 函数设置多个 config 信息

    $httpConfig['method']     = 'GET';
    $httpConfig['target']     = 'http://www.somedomain.com/index.html';
    $httpConfig['referrer']   = 'http://www.somedomain.com';
    $httpConfig['user_agent'] = 'My Crawler';
    $httpConfig['timeout']    = '30';
    $httpConfig['params']     = array('var1' => 'testvalue', 'var2' => 'somevalue');
    
    $http = new Http();
    $http->initialize($httpConfig);
    
    $result = $http->result;

5. 复杂的设置: 
    
    $http = new Http();
    $http->useCurl(false);      // 不使用 curl
    $http->setMethod('POST');       // 使用 POST method
    
    // 设置 POST 数据
    $http->addParam('user_name' , 'yourusername');
    $http->addParam('password'  , 'yourpassword');
    
    // Referrer
    $http->setReferrer('https://yourproject.projectpath.com/login');
    
    // 开始执行请求
    $http->execute('https://yourproject.projectpath.com/login/authenticate');
    $result = $http->getResult();

6. 获取开启了 basic auth 的请求
    
    $http = new Http();
    
    // Set HTTP basic authentication realms
    $http->setAuth('yourusername', 'yourpassword');
    
    // 获取某个被保护的应用的 feed
    $http->get('http://www.someblog.com/protected/feed.xml');
    
    $result = $http->result;

 * 
 * @from http://www.phpfour.com/lib/http
 * @since Version 0.1
 * @original author      Md Emran Hasan <phpfour@gmail.com>
 * @modify by       Charlie Jade
 */

class Http
{
    /**  目标请求 @var string */
    var $target;
    
    /**  目标 URL 的 host @var string */
    var $host;
    
    /**  请求目标的端口 @var integer */
    var $port;
    
    /** 请求目标的 path @var string */
    var $path;
    
    /** 请求目标的 schema  @var string */
    var $schema;
    
    /** 请求的 method (GET 或者 POST)  @var string */
    var $method;
    
    /** 请求的数据  @var array */
    var $params;
    
    /**  请求时候的 cookie 数据  @var array */
    var $cookies;
    
    /**  请求返回的 cookie 数据 @var array  */
    var $_cookies;
    
    /** 请求超时时间, 默认是 25 @var integer */
    var $timeout;
    
    /** 是否使用 cURL , 默认为 TRUE @var boolean */
    var $useCurl;
    
    /**   referrer 信息 @var string */
    var $referrer;
    
    /** 请求客户端 User agent  @var string */
    var $userAgent;
    
    /**  Contains the cookie path (to be used with cURL) @var string */
    var $cookiePath;
    
    /**  是否使用 Cookie @var boolean  */
    var $useCookie;
    
    /** 是否为下一次请求保存 Cookie @var boolean */
    var $saveCookie;
    
    /** HTTP Basic Auth 用户名 (for authentication) @var string */
    var $username;
    
    /** HTTP Basic Auth 密码 (for authentication) @var string */
    var $password;
    
    /** 请求的结果集 @var string */
    var $result;
    
    /** 最后一个请求的 headers 信息  @var array */
    var $headers;
    
    /** Contains the last call's http status code @var string  */
    var $status;
    
    /** 是否跟随 http redirect 跳转  @var boolean */
    var $redirect;
    
    /** 最大 http redirect 调整数 @var integer */
    var $maxRedirect;
    
    /** 当前请求有多少个 URL  @var integer */
    var $curRedirect;
    
    /** 错误代码 @var string */
    var $error;
    
    /** Store the next token  @var string */
    var $nextToken;
    
    /** 是否存储 bug 信息 @var boolean  */
    var $debug;
    
    /** Stores the debug messages  @var array @todo will keep debug messages */
    var $debugMsg;
    
    /**  Constructor for initializing the class with default values. @return void   */
    public function __construct()
    {
        // 先初始化
        $this->clear();    
    }
    
    /**
     * 初始化配置信息
     * Initialize preferences
     * 
     * This function will take an associative array of config values and 
     * will initialize the class variables using them. 
     * 
     * Example use:
     * 
     * <pre>
     * $httpConfig['method']     = 'GET';
     * $httpConfig['target']     = 'http://www.somedomain.com/index.html';
     * $httpConfig['referrer']   = 'http://www.somedomain.com';
     * $httpConfig['user_agent'] = 'My Crawler';
     * $httpConfig['timeout']    = '30';
     * $httpConfig['params']     = array('var1' => 'testvalue', 'var2' => 'somevalue');
     * 
     * $http = new Http();
     * $http->initialize($httpConfig);
     * </pre>
     *
     * @param array Config values as associative array
     * @return void
     */    
    public function initialize($config = array())
    {
        $this->clear();
        foreach ($config as $key => $val)
        {
            if (isset($this->$key))
            {
                $method = 'set' . ucfirst(str_replace('_', '', $key));
                
                if (method_exists($this, $method))
                {
                    $this->$method($val);
                }
                else
                {
                    $this->$key = $val;
                }            
            }
        }
    }
    
    /**
     * 初始化所有
     * 
     * Clears all the properties of the class and sets the object to
     * the beginning state. Very handy if you are doing subsequent calls 
     * with different data.
     *
     * @return void
     */
    public function clear()
    {
        // Set the request defaults
        $this->host         = '';
        $this->port         = 0;
        $this->path         = '';
        $this->target       = '';
        $this->method       = 'GET';
        $this->schema       = 'http';
        $this->params       = array();
        $this->headers      = array();
        $this->cookies      = array();
        $this->_cookies     = array();
        
        // Set the config details        
        $this->debug        = FALSE;
        $this->error        = '';
        $this->status       = 0;
        $this->timeout      = '25';
        $this->useCurl      = TRUE;
        $this->referrer     = '';
        $this->username     = '';
        $this->password     = '';
        $this->redirect     = TRUE;
        
        // Set the cookie and agent defaults
        $this->nextToken    = '';
        $this->useCookie    = FALSE;
        $this->saveCookie   = FALSE;
        $this->maxRedirect  = 3;
        $this->cookiePath   = 'cookie.txt';
        $this->userAgent    = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.7 (KHTML, like Gecko) Chrome/16.0.912.63 Safari/535.7';
    }
    
    /** 设置目标 @return void */
    public function setTarget($url)
    {
        $this->target = $url;
    }
    
    /** 设置 http 请求方法  @param string HTTP method to use (GET or POST)  @return void */
    public function setMethod($method)
    {
         $this->method = $method;
    }
    
    /** 设置 referrer URL @param string URL of referrer page @return void */
    public function setReferrer($referrer)
    {
         $this->referrer = $referrer;
    }
    
    /**  设置 User agent  @param string Full user agent string @return void */
    public function setUseragent($agent)
    {
        $this->userAgent = $agent;
    }
    
    /** 设置请求 timeout  @param integer Timeout delay in seconds @return void */
    public function setTimeout($seconds)
    {
        $this->timeout = $seconds;
    }
    
    /** 设置  cookie path (只支持cURL ) @param string File location of cookiejar @return void */
    public function setCookiepath($path)
    {
        
        $this->cookiePath = $path;
        $this->useCookie(TRUE);
        $this->saveCookie(TRUE);
    }
    
    /** 设置请求参数 parameters @param array GET or POST 的请求数据 @return void  */
    public function setParams($dataArray)
    {
        $this->params = array_merge($this->params, $dataArray);
    }
    
    /** 设置 basic http auth 域验证 @param string 用户名  @param string 密码  @return void */
    public function setAuth($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }
    
    /** 设置最大跳转数 @param integer Maximum number of redirects @return void */
    public function setMaxredirect($value)
    {
        $this->maxRedirect = $value;
    }
    
    /** 添加多一个新的请求数据 @param string Name of the parameter @param string Value of the paramete  @return void  */
    public function addParam($name, $value)
    {
        $this->params[$name] = $value;
    }
    
    /** 添加 cookie 请求数据  @param string Name of cookie  @param string Value of cookie */
    public function addCookie($name, $value)
    {
        $this->cookies[$name] = $value;
    }
    
    /** 是否使用 curl, 默认 true, false 为使用 socket  */
    public function useCurl($value = TRUE)
    {
        if (is_bool($value))
        {
        $this->useCurl = $value;
        }   
    }
    
    /** 是否使用 cookie , 默认为 false  @param boolean Whether to use cookies or not  @return void  */
    public function useCookie($value = FALSE)
    {
        $this->useCookie = $value;
    }
    
    /** 是否使用 cookie , 以供下一次请求使用 @param boolean Whether to save persistent cookies or not @return void  */
    public function saveCookie($value = FALSE)
    {
        $this->saveCookie = $value;
    }
    
    /** 是否跟随 302 跳转 @param boolean Whether to follow HTTP redirects or not  */
    public function followRedirects($value = TRUE)
    {
        $this->redirect = $value;
    }
    
    /** 获取结果集  @return string output of execution */
    public function getResult()
    {
        return $this->result;
    }
    
    /** 获取最后一个返回的 headers 数组 */
    public function getHeaders()
    {
        return $this->headers;
    }

    /** 获取请求的状态码  */
    public function getStatus()
    {
        return $this->status;
    }
        
    /** 获取最后运行错误   */
    public function getError()
    {
        return $this->error;
    }
    
    /** 执行一条 http get 请求  */
    public function get($url, $data=array()){
        return $this->execute($url, '', 'GET', $data);
    }
    
    /** 执行一条 http post 请求  */
    public function post($url, $data=array()){
        return $this->execute($url, '', 'POST', $data);
    }
    
    /**
     * 使用当前的配置, 发送一条 HTTP 请求  
     * 
     * @param string URL of the target page (optional)
     * @param string URL of the referrer page (optional)
     * @param string 请求方法 (GET or POST) (optional)
     * @param array 请求数据, key 和 value 对应的数组 (optional)
     * @return string 请求的结果集
     */    
    public function execute($target = '', $referrer = '', $method = '', $data = array())
    {
        // Populate the properties
        $this->target = ($target) ? $target : $this->target;
        $this->method = ($method) ? $method : $this->method;
        
        $this->referrer = ($referrer) ? $referrer : $this->referrer;
        
        // Add the new params
        if (is_array($data) && count($data) > 0) 
        {
            $this->params = array_merge($this->params, $data);
        }
        
        // Process data, if presented
        if(is_array($this->params) && count($this->params) > 0)
        {
            // Get a blank slate
            $tempString = array();
            
            // Convert data array into a query string (ie animal=dog&sport=baseball)
            foreach ($this->params as $key => $value) 
            {
                if(strlen(trim($value))>0)
                {
                    $tempString[] = $key . "=" . urlencode($value);
                }
            }
            
            $queryString = join('&', $tempString);
        }
        
        // 如果 cURL 没有安装就使用 fscokopen 执行请求
        $this->useCurl = $this->useCurl && in_array('curl', get_loaded_extensions());
        
        // GET method configuration
        if($this->method == 'GET')
        {
            if(isset($queryString))
            {
                $this->target = $this->target . "?" . $queryString;
            }
        }
        
        // Parse target URL
        $urlParsed = parse_url($this->target);
        
        // Handle SSL connection request
        if ($urlParsed['scheme'] == 'https')
        {
            $this->host = 'ssl://' . $urlParsed['host'];
            $this->port = ($this->port != 0) ? $this->port : 443;
        }
        else
        {
            $this->host = $urlParsed['host'];
            $this->port = ($this->port != 0) ? $this->port : 80;
        }
        
        // Finalize the target path
        $this->path   = (isset($urlParsed['path']) ? $urlParsed['path'] : '/') . (isset($urlParsed['query']) ? '?' . $urlParsed['query'] : '');
        $this->schema = $urlParsed['scheme'];
        
        // Pass the requred cookies
        $this->_passCookies();
        
        // Process cookies, if requested
        if(is_array($this->cookies) && count($this->cookies) > 0)
        {
            // Get a blank slate
            $tempString   = array();
            
            // Convert cookiesa array into a query string (ie animal=dog&sport=baseball)
            foreach ($this->cookies as $key => $value) 
            {
                if(strlen(trim($value)) > 0)
                {
                    $tempString[] = $key . "=" . urlencode($value);
                }
            }
            
            $cookieString = join('&', $tempString);
        }
        
        // Do we need to use cURL
        if ($this->useCurl)
        {
            // Initialize PHP cURL handle
            $ch = curl_init();
    
            // GET method configuration
            if($this->method == 'GET')
            {
                curl_setopt ($ch, CURLOPT_HTTPGET, TRUE); 
                curl_setopt ($ch, CURLOPT_POST, FALSE); 
            }
            // POST method configuration
            else
            {
                if(isset($queryString))
                {
                    curl_setopt ($ch, CURLOPT_POSTFIELDS, $queryString);
                }
                
                curl_setopt ($ch, CURLOPT_POST, TRUE); 
                curl_setopt ($ch, CURLOPT_HTTPGET, FALSE); 
            }
            
            // Basic Authentication configuration
            if ($this->username && $this->password)
            {
                curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
            }
            
            // Custom cookie configuration
            if($this->useCookie && isset($cookieString))
            {
                curl_setopt ($ch, CURLOPT_COOKIE, $cookieString);
            }
            
            curl_setopt($ch, CURLOPT_HEADER,         array('Accept-Language: zh-cn','Connection: Keep-Alive','Cache-Control: no-cache')); 
            curl_setopt($ch, CURLOPT_NOBODY,         FALSE);                // Return body

            curl_setopt($ch, CURLOPT_COOKIEJAR,      $this->cookiePath);    // cookie 文件
            curl_setopt($ch, CURLOPT_COOKIEFILE,      $this->cookiePath);    // cookie 文件

            curl_setopt($ch, CURLOPT_TIMEOUT,        $this->timeout);       // Timeout
            curl_setopt($ch, CURLOPT_USERAGENT,      $this->userAgent);     // Webbot name
            curl_setopt($ch, CURLOPT_URL,            $this->target);        // Target site
            curl_setopt($ch, CURLOPT_REFERER,        $this->referrer);      // Referer value
            
            curl_setopt($ch, CURLOPT_VERBOSE,        FALSE);                // Minimize logs
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);                // No certificate
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->redirect);      // Follow redirects
            curl_setopt($ch, CURLOPT_MAXREDIRS,      $this->maxRedirect);   // Limit redirections to four
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);                 // 是否以 string 格式返回
            
            // Get the target contents
            $content = curl_exec($ch);
            
            // Get the request info 
            $curl_info = curl_getinfo($ch);
            $header_size = $curl_info["header_size"];

            // 赋值结果集
            $this->result = substr($content, $header_size);
            
            $reader = explode("\r\n\r\n", trim(substr($content, 0, $header_size))); 
            $this->status = $curl_info['http_code'];
            
            // Parse the headers
            $this->_parseHeaders( explode("\r\n\r\n", trim(substr($content, 0, $header_size))) );
            
            // Store the error (is any)
            $this->_setError(curl_error($ch));
            
            // Close PHP cURL handle
            curl_close($ch);
        }
        else
        {
            // Get a file pointer
            $filePointer = fsockopen($this->host, $this->port, $errorNumber, $errorString, $this->timeout);
       
            // We have an error if pointer is not there
            if (!$filePointer)
            {
                $this->_setError('Failed opening http socket connection: ' . $errorString . ' (' . $errorNumber . ')');
                return FALSE;
            }

            // Set http headers with host, user-agent and content type
            $requestHeader  = $this->method . " " . $this->path . "  HTTP/1.1\r\n";
            $requestHeader .= "Host: " . $urlParsed['host'] . "\r\n";
            $requestHeader .= "User-Agent: " . $this->userAgent . "\r\n";
            $requestHeader .= "Content-Type: application/x-www-form-urlencoded\r\n";
            
            // Specify the custom cookies
            if ($this->useCookie && $cookieString != '')
            {
                $requestHeader.= "Cookie: " . $cookieString . "\r\n";
            }

            // POST method configuration
            if ($this->method == "POST")
            {
                $requestHeader.= "Content-Length: " . strlen($queryString) . "\r\n";
            }
            
            // Specify the referrer
            if ($this->referrer != '')
            {
                $requestHeader.= "Referer: " . $this->referrer . "\r\n";
            }
            
            // Specify http authentication (basic)
            if ($this->username && $this->password)
            {
                $requestHeader.= "Authorization: Basic " . base64_encode($this->username . ':' . $this->password) . "\r\n";
            }
       
            $requestHeader.= "Connection: close\r\n\r\n";
       
            // POST method configuration
            if ($this->method == "POST")
            {
                $requestHeader .= $queryString;
            }           

            // We're ready to launch
            fwrite($filePointer, $requestHeader);
       
            // Clean the slate
            $responseHeader = '';
            $responseContent = '';

            // 3...2...1...Launch !
            do
            {
                $responseHeader .= fread($filePointer, 1);
            }
            while (!preg_match('/\\r\\n\\r\\n$/', $responseHeader));
            
            // Parse the headers
            $this->_parseHeaders($responseHeader);
            
            // Do we have a 301/302 redirect ?
            if (($this->status == '301' || $this->status == '302') && $this->redirect == TRUE)
            {
                if ($this->curRedirect < $this->maxRedirect)
                {
                    // Let's find out the new redirect URL
                    $newUrlParsed = parse_url($this->headers['location']);
                    
                    if ($newUrlParsed['host'])
                    {
                        $newTarget = $this->headers['location'];    
                    }
                    else
                    {
                        $newTarget = $this->schema . '://' . $this->host . '/' . $this->headers['location'];
                    }
                    
                    // Reset some of the properties
                    $this->port   = 0;
                    $this->status = 0;
                    $this->params = array();
                    $this->method = 'GET';
                    $this->referrer = $this->target;
                    
                    // Increase the redirect counter
                    $this->curRedirect++;
                    
                    // Let's go, go, go !
                    $this->result = $this->execute($newTarget);
                }
                else
                {
                    $this->_setError('Too many redirects.');
                    return FALSE;
                }
            }
            else
            {
                // Nope...so lets get the rest of the contents (non-chunked)
                if ($this->headers['transfer-encoding'] != 'chunked')
                {
                    while (!feof($filePointer))
                    {
                        $responseContent .= fgets($filePointer, 128);
                    }
                }
                else
                {
                    // Get the contents (chunked)
                    while ($chunkLength = hexdec(fgets($filePointer)))
                    {
                        $responseContentChunk = '';
                        $readLength = 0;
                       
                        while ($readLength < $chunkLength)
                        {
                            $responseContentChunk .= fread($filePointer, $chunkLength - $readLength);
                            $readLength = strlen($responseContentChunk);
                        }

                        $responseContent .= $responseContentChunk;
                        fgets($filePointer);  
                    }
                }
                
                // Store the target contents
                $this->result = chop($responseContent);
            }
        }
        
        // There it is! We have it!! Return to base !!!
        return $this->result;
    }
    
    /** 解析 header 信息*/
    private function _parseHeaders($responseHeader)
    {
        // Break up the headers
        $headers = $responseHeader;
        
        // Clear the header array
        $this->_clearHeaders();
        
        // Get resposne status
        if($this->status == 0)
        {
            // Oooops !
            if(!eregi($match = "^http/[0-9]+\\.[0-9]+[ \t]+([0-9]+)[ \t]*(.*)\$", $headers[0], $matches))
            {
                $this->_setError('Unexpected HTTP response status');
                return FALSE;
            }
            
            // Gotcha!
            $this->status = $matches[1];
            array_shift($headers);
        }
        
        // Prepare all the other headers
        foreach ($headers as $header)
        {
            // Get name and value
            $headerName  = strtolower($this->_tokenize($header, ':'));
            $headerValue = trim(chop($this->_tokenize("\r\n")));
            
            // If its already there, then add as an array. Otherwise, just keep there
            if(isset($this->headers[$headerName]))
            {
                if(gettype($this->headers[$headerName]) == "string")
                {
                    $this->headers[$headerName] = array($this->headers[$headerName]);
                }
                    
                $this->headers[$headerName][] = $headerValue;
            }
            else
            {
                $this->headers[$headerName] = $headerValue;
            }
        }
            
        // Save cookies if asked 
        if ($this->saveCookie && isset($this->headers['set-cookie']))
        {
            $this->_parseCookie();
        }
    }
    
    /** 去除所有 header 信息 */
    private function _clearHeaders()
    {
        $this->headers = array();
    }
    
    /** 解析 COOKIE */
    private function _parseCookie()
    {
        // Get the cookie header as array
        if(gettype($this->headers['set-cookie']) == "array")
        {
            $cookieHeaders = $this->headers['set-cookie'];
        }
        else
        {
            $cookieHeaders = array($this->headers['set-cookie']);
        }

        // Loop through the cookies
        for ($cookie = 0; $cookie < count($cookieHeaders); $cookie++)
        {
            $cookieName  = trim($this->_tokenize($cookieHeaders[$cookie], "="));
            $cookieValue = $this->_tokenize(";");
            
            $urlParsed   = parse_url($this->target);
            
            $domain      = $urlParsed['host'];
            $secure      = '0';
            
            $path        = "/";
            $expires     = "";
            
            while(($name = trim(urldecode($this->_tokenize("=")))) != "")
            {
                $value = urldecode($this->_tokenize(";"));
                
                switch($name)
                {
                    case "path"     : $path     = $value; break;
                    case "domain"   : $domain   = $value; break;
                    case "secure"   : $secure   = ($value != '') ? '1' : '0'; break;
                }
            }
            
            $this->_setCookie($cookieName, $cookieValue, $expires, $path , $domain, $secure);
        }
    }
    
    /** 设置 cookie , 为下一次请求做准备 */
    private function _setCookie($name, $value, $expires = "" , $path = "/" , $domain = "" , $secure = 0)
    {
        if(strlen($name) == 0)
        {
            return($this->_setError("No valid cookie name was specified."));
        }

        if(strlen($path) == 0 || strcmp($path[0], "/"))
        {
            return($this->_setError("$path is not a valid path for setting cookie $name."));
        }
            
        if($domain == "" || !strpos($domain, ".", $domain[0] == "." ? 1 : 0))
        {
            return($this->_setError("$domain is not a valid domain for setting cookie $name."));
        }
        
        $domain = strtolower($domain);
        
        if(!strcmp($domain[0], "."))
        {
            $domain = substr($domain, 1);
        }
            
        $name  = $this->_encodeCookie($name, true);
        $value = $this->_encodeCookie($value, false);
        
        $secure = intval($secure);
        
        $this->_cookies[] = array( "name"      =>  $name,
                                   "value"     =>  $value,
                                   "domain"    =>  $domain,
                                   "path"      =>  $path,
                                   "expires"   =>  $expires,
                                   "secure"    =>  $secure
                                 );
    }
    
    /** cookie  数据集编码  */
    private function _encodeCookie($value, $name)
    {
        return($name ? str_replace("=", "%25", $value) : str_replace(";", "%3B", $value));
    }
    
    /** 把正确的 cookie 传输给当前请求 */
    private function _passCookies()
    {
        if (is_array($this->_cookies) && count($this->_cookies) > 0)
        {
            $urlParsed = parse_url($this->target);
            $tempCookies = array();
            
            foreach($this->_cookies as $cookie)
            {
                if ($this->_domainMatch($urlParsed['host'], $cookie['domain']) && (0 === strpos($urlParsed['path'], $cookie['path']))
                    && (empty($cookie['secure']) || $urlParsed['protocol'] == 'https')) 
                {
                    $tempCookies[$cookie['name']][strlen($cookie['path'])] = $cookie['value'];
                }
            }
            
            // cookies with longer paths go first
            foreach ($tempCookies as $name => $values) 
            {
                krsort($values);
                foreach ($values as $value) 
                {
                    $this->addCookie($name, $value);
                }
            }
        }
    }
    
    /** 匹配域名 */
    private function _domainMatch($requestHost, $cookieDomain)
    {
        if ('.' != $cookieDomain{0}) 
        {
            return $requestHost == $cookieDomain;
        } 
        elseif (substr_count($cookieDomain, '.') < 2) 
        {
            return false;
        } 
        else 
        {
            return substr('.'. $requestHost, - strlen($cookieDomain)) == $cookieDomain;
        }
    }
    
    /** 给当前操作做记号用的 */
    private function _tokenize($string, $separator = '')
    {
        if(!strcmp($separator, ''))
        {
            $separator = $string;
            $string = $this->nextToken;
        }
        
        for($character = 0; $character < strlen($separator); $character++)
        {
            if(gettype($position = strpos($string, $separator[$character])) == "integer")
            {
                $found = (isset($found) ? min($found, $position) : $position);
            }
        }
        
        if(isset($found))
        {
            $this->nextToken = substr($string, $found + 1);
            return(substr($string, 0, $found));
        }
        else
        {
            $this->nextToken = '';
            return($string);
        }
    }
    
    /** 设置错误信息 */
    private function _setError($error)
    {
        if ($error != '')
        {
            $this->error = $error;
            return $error;
        }
    }
}

?>