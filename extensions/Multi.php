<?php
/**
 * @Author: Jaeger <hj.q@qq.com>
 * @Date:   2015-11-11 17:52:40
 * @Last Modified by:   Jaeger
 * @Last Modified time: 2015-11-16 09:57:14
 * @version         1.0
 * 多线程扩展
 */
class Multi extends AQuery
{
    private $curl;
    private $args;

    public function run(array $args)
    {
        $default = array(
            'curl' => array(
                'opt' => array(
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_AUTOREFERER => true,
                ),
                'maxThread' => 100,
                'maxTry' => 3
            ),
            'list' => array(),
            'success' => function(){},
            'error' => function(){},
            'start' => true
        );

        $this->args = array_merge($default,$args);

        $this->curl = $this->getInstance('CurlMulti');
        if(isset($this->args['curl'])){
            foreach ($this->args['curl'] as $k => $v) {
                $this->curl->$k = $v;
            }
        }
        $this->add($this->args['list']);

        return $this->args['start']?$this->start():$this;
    }

    public function add($urls,$success = false,$error = false)
    {
        if(!is_array($urls)){
            $urls = array($urls);
        }
        foreach ($urls as $url) {
            $this->curl->add(
                array(
                    'url' => $url,
                    'args' => $this,
                    'opt' => array(
                        CURLOPT_REFERER => $url
                    )
                ),
                $success?$success:$this->args['success'],
                $error?$error:$this->args['error']
            );
        }
        return $this;
    }

    public function start()
    {
        $this->curl->start();
        return $this;
    }
}