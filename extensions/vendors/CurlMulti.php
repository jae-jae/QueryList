<?php
/**
 * Chrome	Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.47 Safari/536.11
 * IE6		Mozilla/5.0 (Windows NT 6.1; rv:9.0.1) Gecko/20100101 Firefox/9.0.1
 * FF		Mozilla/5.0 (Windows NT 6.1; WOW64; rv:24.0) Gecko/20100101 Firefox/24.0
 * 
 * more useragent:http://www.useragentstring.com/
 *
 * @author admin@phpdr.net
 *        
 */
class CurlMulti {
	// url
	const TASK_ITEM_URL = 0x01;
	// file
	const TASK_ITEM_FILE = 0x02;
	// arguments
	const TASK_ITEM_ARGS = 0x03;
	// operation, task level
	const TASK_ITEM_OPT = 0x04;
	// control options
	const TASK_ITEM_CTL = 0x05;
	// file pointer
	const TASK_FP = 0x06;
	// success callback
	const TASK_PROCESS = 0x07;
	// curl fail callback
	const TASK_FAIL = 0x08;
	// tryed times
	const TASK_TRYED = 0x09;
	// handler
	const TASK_CH = 0x0A;
	
	// global max thread num
	public $maxThread = 10;
	// Max thread by task type.Task type is specified in $item['ctl'] in add().If task has no type,$this->maxThreadNoType is maxThread-sum(maxThreadType).If less than 0 $this->maxThreadNoType is set to 0.
	public $maxThreadType = array ();
	// retry time(s) when task failed
	public $maxTry = 3;
	// operation, class level curl opt
	public $opt = array ();
	// cache options,dirLevel values is less than 3
	public $cache = array (
			'enable' => false,
			'enableDownload' => false,
			'compress' => false,
			'dir' => null,
			'expire' => 86400,
			'dirLevel' => 1 
	);
	// stack or queue
	public $taskPoolType = 'stack';
	// eliminate duplicate for taskpool, will delete previous task and add new one
	public $taskOverride = false;
	// task callback,add() should be called in callback, $cbTask[0] is callback, $cbTask[1] is param.
	public $cbTask = null;
	// status callback
	public $cbInfo = null;
	// user callback
	public $cbUser = null;
	// common fail callback, called if no one specified
	public $cbFail = null;
	
	// is the loop running
	protected $isRunning = false;
	// max thread num no type
	protected $maxThreadNoType = null;
	// all added task was saved here first
	protected $taskPool = array ();
	// taskPool with high priority
	protected $taskPoolAhead = array ();
	// running task(s)
	protected $taskRunning = array ();
	// failed task need to retry
	protected $taskFail = array ();
	
	// handle of multi-thread curl
	private $mh = null;
	// user error
	private $userError = null;
	// if __construct called
	private $isConstructCalled = false;
	// running info
	private $info = array (
			'all' => array (
					// process start time
					'startTime' => null,
					// download start time
					'startTimeDownload' => null,
					// the real multi-thread num
					'activeNum' => null,
					// finished task in the queue
					'queueNum' => null,
					// byte
					'downloadSize' => 0,
					// finished task number,include failed task and cache
					'finishNum' => 0,
					// The number of cache used
					'cacheNum' => 0,
					// completely failed task number
					'failNum' => 0,
					// task num has added
					'taskNum' => 0,
					// task running num by type,
					'taskRunningNumType' => array (),
					// task ruuning num no type
					'taskRunningNumNoType' => 0,
					// $this->taskPool size
					'taskPoolNum' => 0,
					// $this->taskRunning size
					'taskRunningNum' => 0,
					// $this->taskFail size
					'taskFailNum' => 0,
					// finish percent
					'finishPercent' => 0,
					// time cost
					'timeSpent' => 0,
					// download time cost
					'timeSpentDownload' => 0,
					// curl task speed
					'taskSpeedNoCache' => 0,
					// network speed, bytes
					'downloadSpeed' => 0 
			),
			'running' => array () 
	);
	function __construct() {
		$this->isConstructCalled = true;
		if (version_compare ( PHP_VERSION, '5.1.0' ) < 0) {
			throw new CurlMulti_Exception ( 'PHP 5.1.0+ is needed' );
		}
	}
	
	/**
	 * add a task to taskPool
	 *
	 * @param array $item
	 *        	array('url'=>'',['file'=>'',['opt'=>array(),['args'=>array(),['ctl'=>array('type'=>'','ahead'=>false,'cache'=>array('enable'=>bool,'expire'=>0),'close'=>true))]]]])
	 * @param mixed $process
	 *        	success callback,for callback first param array('info'=>,'content'=>), second param $item[args]
	 * @param mixed $fail
	 *        	curl fail callback,for callback first param array('error'=>array(0=>code,1=>msg),'info'=>array),second param $item[args];
	 * @throws CurlMulti_Exception
	 * @return \frame\lib\CurlMulti
	 */
	function add(array $item, $process = null, $fail = null) {
		// check
		if (! is_array ( $item )) {
			user_error ( 'item must be array, item is ' . gettype ( $item ), E_USER_WARNING );
		} else {
			$item ['url'] = trim ( $item ['url'] );
			if (empty ( $item ['url'] )) {
				user_error ( "url can't be empty, url=$item[url]", E_USER_WARNING );
			} else {
				// replace space with + to avoid some curl problems
				$item ['url'] = str_replace ( ' ', '+', $item ['url'] );
				// fix
				if (empty ( $item ['file'] ))
					$item ['file'] = null;
				if (empty ( $item ['opt'] ))
					$item ['opt'] = array ();
				if (empty ( $item ['args'] ))
					$item ['args'] = array ();
				if (empty ( $item ['ctl'] )) {
					$item ['ctl'] = array ();
				}
				if (! isset ( $item ['ctl'] ['cache'] ) || ! isset ( $item ['ctl'] ['cache'] ['enable'] )) {
					$item ['ctl'] ['cache'] = array (
							'enable' => false,
							'expire' => 0 
					);
				}
				if (! isset ( $item ['ctl'] ['ahead'] )) {
					$item ['ctl'] ['ahead'] = false;
				}
				if (empty ( $process )) {
					$process = null;
				}
				if (empty ( $fail )) {
					$fail = null;
				}
				$task = array ();
				$task [self::TASK_ITEM_URL] = $item ['url'];
				$task [self::TASK_ITEM_FILE] = $item ['file'];
				$task [self::TASK_ITEM_ARGS] = array (
						$item ['args'] 
				);
				$task [self::TASK_ITEM_OPT] = $item ['opt'];
				$task [self::TASK_ITEM_CTL] = $item ['ctl'];
				$task [self::TASK_PROCESS] = $process;
				$task [self::TASK_FAIL] = $fail;
				$task [self::TASK_TRYED] = 0;
				$task [self::TASK_CH] = null;
				$this->addTaskPool ( $task );
				$this->info ['all'] ['taskNum'] ++;
			}
		}
		return $this;
	}
	
	/**
	 * add task to taskPool
	 *
	 * @param unknown $task        	
	 */
	private function addTaskPool($task) {
		// uniq
		if ($this->taskOverride) {
			foreach ( array (
					'taskPoolAhead',
					'taskPool' 
			) as $v ) {
				foreach ( $this->$v as $k1 => $v1 ) {
					if ($v1 [self::TASK_ITEM_URL] == $task [self::TASK_ITEM_URL]) {
						$t = &$this->$v;
						unset ( $t [$k1] );
					}
				}
			}
		}
		// add
		if (true == $task [self::TASK_ITEM_CTL] ['ahead']) {
			$this->taskPoolAhead [] = $task;
		} else {
			if ($this->taskPoolType == 'queue') {
				$this->taskPool [] = $task;
			} elseif ($this->taskPoolType == 'stack') {
				array_unshift ( $this->taskPool, $task );
			} else {
				throw new CurlMulti_Exception ( 'taskPoolType not found, taskPoolType=' . $this->taskPoolType );
			}
		}
	}
	
	/**
	 * Perform the actual task(s).
	 */
	function start() {
		if ($this->isRunning) {
			throw new CurlMulti_Exception ( __CLASS__ . ' is running !' );
		}
		if (false === $this->isConstructCalled) {
			throw new CurlMulti_Exception ( __CLASS__ . ' __construct is not called' );
		}
		$this->mh = curl_multi_init ();
		$this->info ['all'] ['startTime'] = time ();
		$this->info ['all'] ['timeStartDownload'] = null;
		$this->info ['all'] ['downloadSize'] = 0;
		$this->info ['all'] ['finishNum'] = 0;
		$this->info ['all'] ['cacheNum'] = 0;
		$this->info ['all'] ['failNum'] = 0;
		$this->info ['all'] ['taskNum'] = 0;
		$this->info ['all'] ['taskRunningNumNoType'] = 0;
		$this->setThreadData ();
		$this->isRunning = true;
		$this->addTask ();
		do {
			$this->exec ();
			curl_multi_select ( $this->mh );
			$this->callCbInfo ();
			if (isset ( $this->cbUser )) {
				call_user_func ( $this->cbUser );
			}
			while ( false != ($curlInfo = curl_multi_info_read ( $this->mh, $this->info ['all'] ['queueNum'] )) ) {
				$ch = $curlInfo ['handle'];
				$task = $this->taskRunning [( int ) $ch];
				$info = curl_getinfo ( $ch );
				$this->info ['all'] ['downloadSize'] += $info ['size_download'];
				if (isset ( $task [self::TASK_FP] )) {
					fclose ( $task [self::TASK_FP] );
				}
				if ($curlInfo ['result'] == CURLE_OK) {
					$param = array ();
					$param ['info'] = $info;
					$param ['ext'] = array (
							'ch' => $ch 
					);
					if (! isset ( $task [self::TASK_ITEM_FILE] )) {
						$param ['content'] = curl_multi_getcontent ( $ch );
					}
				}
				curl_multi_remove_handle ( $this->mh, $ch );
				// must close first,other wise download may be not commpleted in process callback
				if (! array_key_exists ( 'close', $task [self::TASK_ITEM_CTL] ) || $task [self::TASK_ITEM_CTL] ['close'] == true) {
					curl_close ( $ch );
				}
				if ($curlInfo ['result'] == CURLE_OK) {
					$this->process ( $task, $param, false );
				}
				// error handle
				$callFail = false;
				if ($curlInfo ['result'] !== CURLE_OK || isset ( $this->userError )) {
					if ($task [self::TASK_TRYED] >= $this->maxTry) {
						// user error
						if (isset ( $this->userError )) {
							$err = array (
									'error' => $this->userError 
							);
						} else {
							$err = array (
									'error' => array (
											$curlInfo ['result'],
											curl_error ( $ch ) 
									) 
							);
						}
						$err ['info'] = $info;
						if (isset ( $task [self::TASK_FAIL] ) || isset ( $this->cbFail )) {
							array_unshift ( $task [self::TASK_ITEM_ARGS], $err );
							$callFail = true;
						} else {
							echo "\nError " . implode ( ', ', $err ['error'] ) . ", url=$info[url]\n";
						}
						$this->info ['all'] ['failNum'] ++;
					} else {
						$task [self::TASK_TRYED] ++;
						$task [self::TASK_ITEM_CTL] ['useCache'] = false;
						$this->taskFail [] = $task;
						$this->info ['all'] ['taskNum'] ++;
					}
					if (isset ( $this->userError )) {
						unset ( $this->userError );
					}
				}
				if ($callFail) {
					if (isset ( $task [self::TASK_FAIL] )) {
						call_user_func_array ( $task [self::TASK_FAIL], $task [self::TASK_ITEM_ARGS] );
					} elseif (isset ( $this->cbFail )) {
						call_user_func_array ( $this->cbFail, $task [self::TASK_ITEM_ARGS] );
					}
				}
				unset ( $this->taskRunning [( int ) $ch] );
				if (array_key_exists ( 'type', $task [self::TASK_ITEM_CTL] )) {
					$this->info ['all'] ['taskRunningNumType'] [$task [self::TASK_ITEM_CTL] ['type']] --;
				} else {
					$this->info ['all'] ['taskRunningNumNoType'] --;
				}
				$this->addTask ();
				$this->info ['all'] ['finishNum'] ++;
				// if $this->info['all']['queueNum'] grow very fast there will be no efficiency lost,because outer $this->exec() won't be executed.
				$this->exec ();
				$this->callCbInfo ();
				if (isset ( $this->cbUser )) {
					call_user_func ( $this->cbUser );
				}
			}
		} while ( $this->info ['all'] ['activeNum'] || $this->info ['all'] ['queueNum'] || ! empty ( $this->taskFail ) || ! empty ( $this->taskRunning ) || ! empty ( $this->taskPool ) );
		$this->callCbInfo ( true );
		curl_multi_close ( $this->mh );
		unset ( $this->mh );
		$this->isRunning = false;
	}
	
	/**
	 * call $this->cbInfo
	 */
	private function callCbInfo($force = false) {
		static $lastTime;
		if (! isset ( $lastTime )) {
			$lastTime = time ();
		}
		$now = time ();
		if (($force || $now - $lastTime > 0) && isset ( $this->cbInfo )) {
			$lastTime = $now;
			$this->info ['all'] ['taskPoolNum'] = count ( $this->taskPool );
			$this->info ['all'] ['taskRunningNum'] = count ( $this->taskRunning );
			$this->info ['all'] ['taskFailNum'] = count ( $this->taskFail );
			if ($this->info ['all'] ['taskNum'] > 0) {
				$this->info ['all'] ['finishPercent'] = round ( $this->info ['all'] ['finishNum'] / $this->info ['all'] ['taskNum'], 4 );
			}
			$this->info ['all'] ['timeSpent'] = time () - $this->info ['all'] ['startTime'];
			if (isset ( $this->info ['all'] ['timeStartDownload'] )) {
				$this->info ['all'] ['timeSpentDownload'] = time () - $this->info ['all'] ['timeStartDownload'];
			}
			if ($this->info ['all'] ['timeSpentDownload'] > 0) {
				$this->info ['all'] ['taskSpeedNoCache'] = round ( ($this->info ['all'] ['finishNum'] - $this->info ['all'] ['cacheNum']) / $this->info ['all'] ['timeSpentDownload'], 2 );
				$this->info ['all'] ['downloadSpeed'] = round ( $this->info ['all'] ['downloadSize'] / $this->info ['all'] ['timeSpentDownload'], 2 );
			}
			// running
			$this->info ['running'] = array ();
			foreach ( $this->taskRunning as $k => $v ) {
				$this->info ['running'] [$k] = curl_getinfo ( $v [self::TASK_CH] );
			}
			call_user_func_array ( $this->cbInfo, array (
					$this->info 
			) );
		}
	}
	
	/**
	 * set $this->maxThreadNoType, $this->info['all']['taskRunningNumType'], $this->info['all']['taskRunningNumNoType'] etc
	 */
	private function setThreadData() {
		$this->maxThreadNoType = $this->maxThread - array_sum ( $this->maxThreadType );
		if ($this->maxThreadNoType < 0) {
			$this->maxThreadNoType = 0;
		}
		// unset none exitst type num
		foreach ( $this->info ['all'] ['taskRunningNumType'] as $k => $v ) {
			if ($v == 0 && ! array_key_exists ( $k, $this->maxThreadType )) {
				unset ( $this->info ['all'] ['taskRunningNumType'] [$k] );
			}
		}
		// init type num
		foreach ( $this->maxThreadType as $k => $v ) {
			if ($v == 0) {
				user_error ( 'maxThreadType[' . $k . '] is 0, task of this type will never be added!', E_USER_WARNING );
			}
			if (! array_key_exists ( $k, $this->info ['all'] ['taskRunningNumType'] )) {
				$this->info ['all'] ['taskRunningNumType'] [$k] = 0;
			}
		}
	}
	
	/**
	 * curl_multi_exec()
	 */
	private function exec() {
		while ( curl_multi_exec ( $this->mh, $this->info ['all'] ['activeNum'] ) === CURLM_CALL_MULTI_PERFORM ) {
		}
	}
	
	/**
	 * add a task to curl, keep $this->maxThread concurrent automatically
	 */
	private function addTask() {
		$c = $this->maxThread - count ( $this->taskRunning );
		while ( $c > 0 ) {
			$task = array ();
			// search failed first
			if (! empty ( $this->taskFail )) {
				$task = array_pop ( $this->taskFail );
			} else {
				// cbTask
				if (0 < ($this->maxThread - count ( $this->taskPool )) and ! empty ( $this->cbTask )) {
					if (! isset ( $this->cbTask [1] )) {
						$this->cbTask [1] = array ();
					}
					call_user_func_array ( $this->cbTask [0], array (
							$this->cbTask [1] 
					) );
				}
				if (! empty ( $this->taskPoolAhead )) {
					$task = array_pop ( $this->taskPoolAhead );
				} elseif (! empty ( $this->taskPool )) {
					if ($this->taskPoolType == 'stack') {
						$task = array_pop ( $this->taskPool );
					} elseif ($this->taskPoolType == 'queue') {
						$task = array_shift ( $this->taskPool );
					} else {
						throw new CurlMulti_Exception ( 'taskPoolType not found, taskPoolType=' . $this->taskPoolType );
					}
				}
			}
			$noAdd = false;
			$cache = null;
			if (! empty ( $task )) {
				if (true == $task [self::TASK_ITEM_CTL] ['cache'] ['enable'] || $this->cache ['enable']) {
					$cache = $this->cache ( $task );
					if (null !== $cache) {
						if (isset ( $task [self::TASK_ITEM_FILE] )) {
							file_put_contents ( $task [self::TASK_ITEM_FILE], $cache ['content'], LOCK_EX );
							unset ( $cache ['content'] );
						}
						$this->process ( $task, $cache, true );
						$this->info ['all'] ['cacheNum'] ++;
						$this->info ['all'] ['finishNum'] ++;
						$this->callCbInfo ();
					}
				}
				if (! $cache) {
					$this->setThreadData ();
					if (array_key_exists ( 'type', $task [self::TASK_ITEM_CTL] ) && ! array_key_exists ( $task [self::TASK_ITEM_CTL] ['type'], $this->maxThreadType )) {
						user_error ( 'task was set to notype because type was not set in $this->maxThreadType, type=' . $task [self::TASK_ITEM_CTL] ['type'], E_USER_WARNING );
						unset ( $task [self::TASK_ITEM_CTL] ['type'] );
					}
					if (array_key_exists ( 'type', $task [self::TASK_ITEM_CTL] )) {
						$maxThread = $this->maxThreadType [$task [self::TASK_ITEM_CTL] ['type']];
						$isNoType = false;
					} else {
						$maxThread = $this->maxThreadNoType;
						$isNoType = true;
					}
					if ($isNoType && $maxThread == 0) {
						user_error ( 'task was disgarded because maxThreadNoType=0, url=' . $task [self::TASK_ITEM_URL], E_USER_WARNING );
					}
					if (($isNoType && $this->info ['all'] ['taskRunningNumNoType'] < $maxThread) || (! $isNoType && $this->info ['all'] ['taskRunningNumType'] [$task [self::TASK_ITEM_CTL] ['type']] < $maxThread)) {
						$task [self::TASK_CH] = $this->curlInit ( $task [self::TASK_ITEM_URL] );
						// is a download task?
						if (isset ( $task [self::TASK_ITEM_FILE] )) {
							// curl can create the last level directory
							$dir = dirname ( $task [self::TASK_ITEM_FILE] );
							if (! file_exists ( $dir ))
								mkdir ( $dir, 0777 );
							$task [self::TASK_FP] = fopen ( $task [self::TASK_ITEM_FILE], 'w' );
							curl_setopt ( $task [self::TASK_CH], CURLOPT_FILE, $task [self::TASK_FP] );
						}
						// single task curl option
						if (isset ( $task [self::TASK_ITEM_OPT] )) {
							foreach ( $task [self::TASK_ITEM_OPT] as $k => $v ) {
								curl_setopt ( $task [self::TASK_CH], $k, $v );
							}
						}
						$this->taskRunning [( int ) $task [self::TASK_CH]] = $task;
						if (! isset ( $this->info ['all'] ['timeStartDownload'] )) {
							$this->info ['all'] ['timeStartDownload'] = time ();
						}
						if ($isNoType) {
							$this->info ['all'] ['taskRunningNumNoType'] ++;
						} else {
							$this->info ['all'] ['taskRunningNumType'] [$task [self::TASK_ITEM_CTL] ['type']] ++;
						}
						curl_multi_add_handle ( $this->mh, $task [self::TASK_CH] );
					} else {
						// rotate task to pool
						if ($task [self::TASK_TRYED] > 0) {
							array_unshift ( $this->taskFail, $task );
						} else {
							array_unshift ( $this->taskPool, $task );
						}
						$noAdd = true;
					}
				}
			}
			if (! $cache || $noAdd) {
				$c --;
			}
		}
	}
	
	/**
	 * do process
	 *
	 * @param unknown $task        	
	 * @param unknown $r        	
	 * @param unknown $isCache        	
	 */
	private function process($task, $r, $isCache) {
		array_unshift ( $task [self::TASK_ITEM_ARGS], $r );
		if (isset ( $task [self::TASK_PROCESS] )) {
			$userRes = call_user_func_array ( $task [self::TASK_PROCESS], $task [self::TASK_ITEM_ARGS] );
		}
		if (! isset ( $userRes )) {
			$userRes = true;
		}
		array_shift ( $task [self::TASK_ITEM_ARGS] );
		// backoff
		if (false === $userRes) {
			if (false == $this->cache ['enable'] && false == $task [self::TASK_ITEM_CTL] ['cache'] ['enable']) {
				$task [self::TASK_ITEM_CTL] ['cache'] = array (
						'enable' => true,
						'expire' => 3600 
				);
			}
			$this->addTaskPool ( $task );
		}
		// write cache
		if (false == $isCache && false == isset ( $this->userError ) && (true == $task [self::TASK_ITEM_CTL] ['cache'] ['enable']) || $this->cache ['enable']) {
			$this->cache ( $task, $r );
		}
	}
	
	/**
	 * set or get file cache
	 *
	 * @param string $url        	
	 * @param mixed $content
	 *        	array('info','content')
	 * @return return array|null|boolean
	 */
	private function cache($task, $content = null) {
		if (! isset ( $this->cache ['dir'] ))
			throw new CurlMulti_Exception ( 'Cache dir is not defined' );
		$url = $task [self::TASK_ITEM_URL];
		$key = md5 ( $url );
		$isDownload = isset ( $task [self::TASK_ITEM_FILE] );
		$file = rtrim ( $this->cache ['dir'], '/' ) . '/';
		if (isset ( $this->cache ['dirLevel'] ) && $this->cache ['dirLevel'] != 0) {
			if ($this->cache ['dirLevel'] == 1) {
				$file .= substr ( $key, 0, 3 ) . '/' . substr ( $key, 3 );
			} elseif ($this->cache ['dirLevel'] == 2) {
				$file .= substr ( $key, 0, 3 ) . '/' . substr ( $key, 3, 3 ) . '/' . substr ( $key, 6 );
			} else {
				throw new CurlMulti_Exception ( 'cache dirLevel is invalid, dirLevel=' . $this->cache ['dirLevel'] );
			}
		} else {
			$file .= $key;
		}
		$r = null;
		if (! isset ( $content )) {
			if (file_exists ( $file )) {
				if (true == $task [self::TASK_ITEM_CTL] ['cache'] ['enable']) {
					$expire = $task [self::TASK_ITEM_CTL] ['cache'] ['expire'];
				} else {
					$expire = $this->cache ['expire'];
				}
				if (time () - filemtime ( $file ) < $expire) {
					$r = file_get_contents ( $file );
					if ($this->cache ['compress']) {
						$r = gzuncompress ( $r );
					}
					$r = unserialize ( $r );
					if ($isDownload) {
						$r ['content'] = base64_decode ( $r ['content'] );
					}
				}
			}
		} else {
			$r = false;
			// check main cache directory
			if (! is_dir ( $this->cache ['dir'] )) {
				throw new CurlMulti_Exception ( "Cache dir doesn't exists" );
			} else {
				$dir = dirname ( $file );
				// level 1 subdir
				if (isset ( $this->cache ['dirLevel'] ) && $this->cache ['dirLevel'] > 1) {
					$dir1 = dirname ( $dir );
					if (! is_dir ( $dir1 ) && ! mkdir ( $dir1 )) {
						throw new CurlMulti_Exception ( 'Create dir failed, dir=' . $dir1 );
					}
				}
				if (! is_dir ( $dir ) && ! mkdir ( $dir )) {
					throw new CurlMulti_Exception ( 'Create dir failed, dir=' . $dir );
				}
				if ($isDownload) {
					$content ['content'] = base64_encode ( file_get_contents ( $task [self::TASK_ITEM_FILE] ) );
				}
				$content = serialize ( $content );
				if ($this->cache ['compress']) {
					$content = gzcompress ( $content );
				}
				if (file_put_contents ( $file, $content, LOCK_EX )) {
					$r = true;
				} else {
					throw new CurlMulti_Exception ( 'Write cache file failed' );
				}
			}
		}
		return $r;
	}
	
	/**
	 * user error for current callback
	 * not curl error
	 * must be called in process callback
	 *
	 * @param unknown $msg        	
	 */
	function error($msg) {
		$this->userError = array (
				CURLE_OK,
				$msg 
		);
	}
	
	/**
	 * return a default $ch initialized with global opt
	 *
	 * @param unknown $url        	
	 * @return resource
	 */
	function getch($url = null) {
		return $this->curlInit ( $url );
	}
	
	/**
	 * get curl handle
	 *
	 * @param string $url        	
	 * @return resource
	 */
	private function curlInit($url = null) {
		$ch = curl_init ();
		$opt = array ();
		if (isset ( $url )) {
			$opt [CURLOPT_URL] = $url;
		}
		$opt [CURLOPT_HEADER] = false;
		$opt [CURLOPT_CONNECTTIMEOUT] = 10;
		$opt [CURLOPT_TIMEOUT] = 30;
		$opt [CURLOPT_AUTOREFERER] = true;
		$opt [CURLOPT_USERAGENT] = 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.47 Safari/536.11';
		$opt [CURLOPT_RETURNTRANSFER] = true;
		$opt [CURLOPT_FOLLOWLOCATION] = true;
		$opt [CURLOPT_MAXREDIRS] = 10;
		// user defined opt
		if (! empty ( $this->opt )) {
			foreach ( $this->opt as $k => $v ) {
				$opt [$k] = $v;
			}
		}
		curl_setopt_array ( $ch, $opt );
		return $ch;
	}
}

class CurlMulti_Exception extends Exception {
}