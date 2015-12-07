<?php
/**
 * @Author: Jaeger <hj.q@qq.com>
 * @Date:   2015-07-15 23:27:52
 * @Last Modified by:   Jaeger
 * @Last Modified time: 2015-11-16 11:01:19
 * @version         1.0
 * 网络操作扩展
 */

class Request extends AQuery
{

    protected function hq(array $args)
    {
        $args = array(
            'http' => isset($args['http'])?$args['http']:$args,
            'callback' => isset($args['callback'])?$args['callback']:'',
            'args' =>  isset($args['args'])?$args['args']:''
            );
        $http = $this->getInstance('Http');
        $http->initialize($args['http']);
        $http->execute();
        if(!empty($args['callback'])){
            $http->result = call_user_func($args['callback'],$http->result,$args['args']);
        }
        return $http;
    }

    public function run(array $args)
    {
        $http = $this->hq($args);
        $ql = $this->getInstance();
        $ql->html = $http->result;
        return $ql;
    }
}