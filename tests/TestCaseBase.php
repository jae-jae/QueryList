<?php
/**
 * Created by PhpStorm.
 * User: x
 * Date: 2018/12/9
 * Time: 11:43 PM
 */

namespace Tests;


use PHPUnit\Framework\TestCase;

class TestCaseBase extends TestCase
{
    public function getSnippet($name)
    {
        return file_get_contents(__DIR__.'/assets/'.$name.'.html');
    }
}