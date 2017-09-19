<?php
/**
 * Created by PhpStorm.
 * User: Jaeger <JaegerCode@gmail.com>
 * Date: 2017/9/19
 */

namespace QL\Dom;

use phpQueryObject;

class Dom
{

    protected $document;

    /**
     * Dom constructor.
     */
    public function __construct(phpQueryObject $document)
    {
        $this->document = $document;
    }

    public function find($selector)
    {
        $elements =  $this->document->find($selector);
        return new Elements($elements);
    }
}