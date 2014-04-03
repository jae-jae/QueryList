<?php
/**
 * WebBrowser plugin.
 *
 */
class phpQueryObjectPlugin_WebBrowser {
	/**
	 * Limit binded methods to specified ones.
	 *
	 * @var array
	 */
	public static $phpQueryMethods = null;
	/**
	 * Enter description here...
	 *
	 * @param phpQueryObject $self
	 * @todo support 'reset' event
	 */
	public static function WebBrowser($self, $callback = null, $location = null) {
		$self = $self->_clone()->toRoot();
		$location = $location
			? $location
			// TODO use document.location
			: $self->document->xhr->getUri(true);
		// FIXME tmp
		$self->document->WebBrowserCallback = $callback;
		if (! $location)
			throw new Exception('Location needed to activate WebBrowser plugin !');
		else {
			$self->bind('click', array($location, $callback), array('phpQueryPlugin_WebBrowser', 'hadleClick'));
			$self->bind('submit', array($location, $callback), array('phpQueryPlugin_WebBrowser', 'handleSubmit'));
		}
	}
	public static function browser($self, $callback = null, $location = null) {
		return $self->WebBrowser($callback, $location);
	}
	public static function downloadTo($self, $dir = null, $filename = null) {
		$url = null;
		if ($self->is('a[href]'))
			$url = $self->attr('href');
		else if ($self->find('a')->length)
			$url = $self->find('a')->attr('href');
		if ($url) {
			$url = resolve_url($self->document->location, $url);
			if (! $dir)
				$dir = getcwd();
			// TODO resolv name from response headers
			if (! $filename) {
				$matches = null;
				preg_match('@/([^/]+)$@', $url, $matches);
				$filename = $matches[1];
			}
			//print $url;
			$path = rtrim($dir, '/').'/'.$filename;
			phpQuery::debug("Requesting download of $url\n");
			// TODO use AJAX instead of file_get_contents
			file_put_contents($path, file_get_contents($url));
		}
		return $self;
	}
	/**
	 * Method changing browser location.
	 * Fires callback registered with WebBrowser(), if any.
	 * @param $self
	 * @param $url
	 * @return unknown_type
	 */
	public static function location($self, $url = null) {
		// TODO if ! $url return actual location ???
		$xhr = isset($self->document->xhr)
			? $self->document->xhr
			: null;
		$xhr = phpQuery::ajax(array(
			'url' => $url,
		), $xhr);
		$return = false;
		if ($xhr->getLastResponse()->isSuccessful()) {
			$return = phpQueryPlugin_WebBrowser::browserReceive($xhr);
			if (isset($self->document->WebBrowserCallback))
				phpQuery::callbackRun(
					$self->document->WebBrowserCallback,
					array($return)
				);
		}
		return $return;
	}
}
class phpQueryPlugin_WebBrowser {
	/**
	 *
	 * @param $url
	 * @param $callback
	 * @param $param1
	 * @param $param2
	 * @param $param3
	 * @return Zend_Http_Client
	 */
	public static function browserGet($url, $callback,
		$param1 = null, $param2 = null, $param3 = null) {
		phpQuery::debug("[WebBrowser] GET: $url");
		self::authorizeHost($url);
		$xhr = phpQuery::ajax(array(
			'type' => 'GET',
			'url' => $url,
			'dataType' => 'html',
		));
		$paramStructure = null;
		if (func_num_args() > 2) {
			$paramStructure = func_get_args();
			$paramStructure = array_slice($paramStructure, 2);
		}
		if ($xhr->getLastResponse()->isSuccessful()) {
			phpQuery::callbackRun($callback,
				array(self::browserReceive($xhr)->WebBrowser()),
				$paramStructure
			);
//			phpQuery::callbackRun($callback, array(
//				self::browserReceive($xhr)//->WebBrowser($callback)
//			));
			return $xhr;
		} else {
			throw new Exception("[WebBrowser] GET request failed; url: $url");
			return false;
		}
	}
	/**
	 *
	 * @param $url
	 * @param $data
	 * @param $callback
	 * @param $param1
	 * @param $param2
	 * @param $param3
	 * @return Zend_Http_Client
	 */
	public static function browserPost($url, $data, $callback,
		$param1 = null, $param2 = null, $param3 = null) {
		self::authorizeHost($url);
		$xhr = phpQuery::ajax(array(
			'type' => 'POST',
			'url' => $url,
			'dataType' => 'html',
			'data' => $data,
		));
		$paramStructure = null;
		if (func_num_args() > 3) {
			$paramStructure = func_get_args();
			$paramStructure = array_slice($paramStructure, 3);
		}
		if ($xhr->getLastResponse()->isSuccessful()) {
			phpQuery::callbackRun($callback,
				array(self::browserReceive($xhr)->WebBrowser()),
				$paramStructure
			);
//			phpQuery::callbackRun($callback, array(
//				self::browserReceive($xhr)//->WebBrowser($callback)
//			));
			return $xhr;
		} else
			return false;
	}
	/**
	 *
	 * @param $ajaxSettings
	 * @param $callback
	 * @param $param1
	 * @param $param2
	 * @param $param3
	 * @return Zend_Http_Client
	 */
	public static function browser($ajaxSettings, $callback,
		$param1 = null, $param2 = null, $param3 = null) {
		self::authorizeHost($ajaxSettings['url']);
		$xhr = phpQuery::ajax(
			self::ajaxSettingsPrepare($ajaxSettings)
		);
		$paramStructure = null;
		if (func_num_args() > 2) {
			$paramStructure = func_get_args();
			$paramStructure = array_slice($paramStructure, 2);
		}
		if ($xhr->getLastResponse()->isSuccessful()) {
			phpQuery::callbackRun($callback,
				array(self::browserReceive($xhr)->WebBrowser()),
				$paramStructure
			);
//			phpQuery::callbackRun($callback, array(
//				self::browserReceive($xhr)//->WebBrowser($callback)
//			));
			return $xhr;
		} else
			return false;
	}
	protected static function authorizeHost($url) {
		$host = parse_url($url, PHP_URL_HOST);
		if ($host)
			phpQuery::ajaxAllowHost($host);
	}
	protected static function ajaxSettingsPrepare($settings) {
		unset($settings['success']);
		unset($settings['error']);
		return $settings;
	}
	/**
	 * @param Zend_Http_Client $xhr
	 */
	public static function browserReceive($xhr) {
		phpQuery::debug("[WebBrowser] Received from ".$xhr->getUri(true));
		// TODO handle meta redirects
		$body = $xhr->getLastResponse()->getBody();

		// XXX error ???
		if (strpos($body, '<!doctype html>') !== false) {
			$body = '<html>'
				.str_replace('<!doctype html>', '', $body)
				.'</html>';
		}
		$pq = phpQuery::newDocument($body);
		$pq->document->xhr = $xhr;
		$pq->document->location = $xhr->getUri(true);
		$refresh = $pq->find('meta[http-equiv=refresh]')
			->add('meta[http-equiv=Refresh]');
		if ($refresh->size()) {
//			print htmlspecialchars(var_export($xhr->getCookieJar()->getAllCookies(), true));
//			print htmlspecialchars(var_export($xhr->getLastResponse()->getHeader('Set-Cookie'), true));
			phpQuery::debug("Meta redirect... '{$refresh->attr('content')}'\n");
			// there is a refresh, so get the new url
			$content = $refresh->attr('content');
			$urlRefresh = substr($content, strpos($content, '=')+1);
			$urlRefresh = trim($urlRefresh, '\'"');
			// XXX not secure ?!
			phpQuery::ajaxAllowURL($urlRefresh);
//			$urlRefresh = urldecode($urlRefresh);
			// make ajax call, passing last $xhr object to preserve important stuff
			$xhr = phpQuery::ajax(array(
				'type' => 'GET',
				'url' => $urlRefresh,
				'dataType' => 'html',
			), $xhr);
			if ($xhr->getLastResponse()->isSuccessful()) {
				// if all is ok, repeat this method...
				return call_user_func_array(
					array('phpQueryPlugin_WebBrowser', 'browserReceive'), array($xhr)
				);
			}
		} else
			return $pq;
	}
	/**
	 * 
	 * @param $e
	 * @param $callback
	 * @return unknown_type
	 */
	public static function hadleClick($e, $callback = null) {
		$node = phpQuery::pq($e->target);
		$type = null;
		if ($node->is('a[href]')) {
			// TODO document.location
			$xhr = isset($node->document->xhr)
				? $node->document->xhr
				: null;
			$xhr = phpQuery::ajax(array(
				'url' => resolve_url($e->data[0], $node->attr('href')),
				'referer' => $node->document->location,
			), $xhr);
			if ((! $callback || !($callback instanceof Callback)) && $e->data[1])
				$callback = $e->data[1];
			if ($xhr->getLastResponse()->isSuccessful() && $callback)
				phpQuery::callbackRun($callback, array(
					self::browserReceive($xhr)
				));
		} else if ($node->is(':submit') && $node->parents('form')->size())
			$node->parents('form')->trigger('submit', array($e));
	}
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $e
	 * @TODO trigger submit for form after form's  submit button has a click event
	 */
	public static function handleSubmit($e, $callback = null) {
		$node = phpQuery::pq($e->target);
		if (!$node->is('form') || !$node->is('[action]'))
			return;
		// TODO document.location
		$xhr = isset($node->document->xhr)
			? $node->document->xhr
			: null;
		$submit = pq($e->relatedTarget)->is(':submit')
			? $e->relatedTarget
				// will this work ?
//			: $node->find(':submit:first')->get(0);
			: $node->find('*:submit:first')->get(0);
		$data = array();
		foreach($node->serializeArray($submit) as $r)
		// XXXt.c maybe $node->not(':submit')->add($sumit) would be better ?
//		foreach($node->serializeArray($submit) as $r)
			$data[ $r['name'] ] = $r['value'];
		$options = array(
			'type' => $node->attr('method')
				? $node->attr('method')
				: 'GET',
			'url' => resolve_url($e->data[0], $node->attr('action')),
			'data' => $data,
			'referer' => $node->document->location,
//			'success' => $e->data[1],
		);
		if ($node->attr('enctype'))
			$options['contentType'] = $node->attr('enctype');
		$xhr = phpQuery::ajax($options, $xhr);
		if ((! $callback || !($callback instanceof Callback)) && $e->data[1])
			$callback = $e->data[1];
		if ($xhr->getLastResponse()->isSuccessful() && $callback)
			phpQuery::callbackRun($callback, array(
				self::browserReceive($xhr)
			));
	}
}
/**
 *
 * @param unknown_type $parsed
 * @return unknown
 * @link http://www.php.net/manual/en/function.parse-url.php
 * @author stevenlewis at hotmail dot com
 */
function glue_url($parsed)
    {
    if (! is_array($parsed)) return false;
    $uri = isset($parsed['scheme']) ? $parsed['scheme'].':'.((strtolower($parsed['scheme']) == 'mailto') ? '':'//'): '';
    $uri .= isset($parsed['user']) ? $parsed['user'].($parsed['pass']? ':'.$parsed['pass']:'').'@':'';
    $uri .= isset($parsed['host']) ? $parsed['host'] : '';
    $uri .= isset($parsed['port']) ? ':'.$parsed['port'] : '';
    if(isset($parsed['path']))
        {
        $uri .= (substr($parsed['path'],0,1) == '/')?$parsed['path']:'/'.$parsed['path'];
        }
    $uri .= isset($parsed['query']) ? '?'.$parsed['query'] : '';
    $uri .= isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';
    return $uri;
    }
/**
 * Enter description here...
 *
 * @param unknown_type $base
 * @param unknown_type $url
 * @return unknown
 * @author adrian-php at sixfingeredman dot net
 */
function resolve_url($base, $url) {
        if (!strlen($base)) return $url;
        // Step 2
        if (!strlen($url)) return $base;
        // Step 3
        if (preg_match('!^[a-z]+:!i', $url)) return $url;
        $base = parse_url($base);
        if ($url{0} == "#") {
                // Step 2 (fragment)
                $base['fragment'] = substr($url, 1);
                return unparse_url($base);
        }
        unset($base['fragment']);
        unset($base['query']);
        if (substr($url, 0, 2) == "//") {
                // Step 4
                return unparse_url(array(
                        'scheme'=>$base['scheme'],
                        'path'=>substr($url,2),
                ));
        } else if ($url{0} == "/") {
                // Step 5
                $base['path'] = $url;
        } else {
                // Step 6
                $path = explode('/', $base['path']);
                $url_path = explode('/', $url);
                // Step 6a: drop file from base
                array_pop($path);
                // Step 6b, 6c, 6e: append url while removing "." and ".." from
                // the directory portion
                $end = array_pop($url_path);
                foreach ($url_path as $segment) {
                        if ($segment == '.') {
                                // skip
                        } else if ($segment == '..' && $path && $path[sizeof($path)-1] != '..') {
                                array_pop($path);
                        } else {
                                $path[] = $segment;
                        }
                }
                // Step 6d, 6f: remove "." and ".." from file portion
                if ($end == '.') {
                        $path[] = '';
                } else if ($end == '..' && $path && $path[sizeof($path)-1] != '..') {
                        $path[sizeof($path)-1] = '';
                } else {
                        $path[] = $end;
                }
                // Step 6h
                $base['path'] = join('/', $path);

        }
        // Step 7
        return glue_url($base);
}
