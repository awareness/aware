<?php

use \Mockery as m;

class ModelTest extends PHPUnit_Framework_TestCase 
{
    function tearDown()
    {
        m::close();
    }

    function testErrorsMethod()
    {
        $model = m::mock('\Awareness\Aware[]');
        $this->assertInstanceOf('\Illuminate\Support\MessageBag', $model->getErrors());
    }
}
