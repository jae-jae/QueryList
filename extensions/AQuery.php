<?php
/**
 * @Author: Jaeger <hj.q@qq.com>
 * @Date:   2015-11-11 17:52:40
 * @Last Modified by:   Jaeger
 * @Last Modified time: 2015-11-16 09:57:56
 * @version         1.0
 * 扩展基类
 */
abstract class AQuery
{
     abstract function run(array $args);

    public function getInstance($className = 'QueryList', $params = null)
    {
        return QueryList::getInstance($className,$params);
    }

}