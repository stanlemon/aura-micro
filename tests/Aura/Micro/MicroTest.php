<?php
/*
 * @package Aura.Micro
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Aura\Micro;

/**
 * 
 * @package Aura.Micro
 *
 * @license http://opensource.org/licenses/MIT MIT
 * 
 * 
 */
class MicroTest extends \PHPUnit_Framework_TestCase
{
    public function testMap()
    {
        $app = new \Aura\Micro\Micro();
        
        $this->assertTrue($app->getMap() instanceof \Aura\Router\Map);
    }

    public function testAdd()
    {
        $response = new \stdClass();
        $response->output = '';

        $app = new \Aura\Micro\Micro();
        $app->add(\Aura\Micro\Micro::METHOD_GET, "/test", function() use($app, $response){
            $response->output = "This is output";
        });

        $this->assertNotEquals($response->output, "This is output");

        $app->run("/test", array('REQUEST_METHOD' => 'GET'));

        $this->assertEquals($response->output, "This is output");
    }

	public function testParams()
	{
        $response = new \stdClass();
        $response->output = '';

        $app = new \Aura\Micro\Micro();
		$app->set('response', $response);
		$app->set('foo', function(){
			return new \stdClass;
		});
        $app->add(\Aura\Micro\Micro::METHOD_GET, "/hello/{:world}", function($response, $world, $foo, $bar) use($app){
            $response->output = "Hello {$world}!";

			$this->assertTrue($response instanceof \stdClass);
			$this->assertEquals($world, 'world');
			$this->assertTrue($foo instanceof \stdClass);
			$this->assertEquals($bar, null);
        });

        $app->run("/hello/world", array('REQUEST_METHOD' => 'GET'));

        $this->assertEquals($response->output, "Hello world!");
	}

	public function testDoubleSet()
	{
		try {
			$app = new \Aura\Micro\Micro();
			$app->set('foo', new \stdClass);
			$app->set('foo', new \stdClass);
		} catch (\InvalidArgumentException $e) {
			return;
		}

        $this->fail('An expected exception has not been raised.');
	}

	public function testServer() // Less then ideal approach to code coverage...
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI'] = '/test';
		$_SERVER['PHP_SELF'] = '/index.php';
		$_SERVER['QUERY_STRING'] = 'foo=bar';

        $response = new \stdClass();
        $response->output = '';

        $app = new \Aura\Micro\Micro();
        $app->add(\Aura\Micro\Micro::METHOD_GET, "/test", function() use($app, $response){
            $response->output = "This is output";
        });

        $app->run();

        $this->assertEquals($response->output, "This is output");
	}

    public function makeRouteTest($method)
    {
        $response = new \stdClass();
        $response->output = '';

        $app = new \Aura\Micro\Micro();
        $app->$method("/test", function() use($app, $response){
            $response->output = "This is output";
        });

        $this->assertNotEquals($response->output, "This is output");

        $app->run("/test", array('REQUEST_METHOD' => strtoupper($method)));

        $this->assertEquals($response->output, "This is output");
    }

	public function testPutMethod()
	{
		return $this->makeRouteTest('put');
	}

	public function testPostMethod()
	{
		return $this->makeRouteTest('post');
	}

	public function testDeleteMethod()
	{
		return $this->makeRouteTest('delete');
	}

	public function testNotSet()
	{
		$app = new \Aura\Micro\Micro();
		$this->assertEquals($app->fetch('foo'), null);
	}

    public function testBefore()
    {
        return $this->makeCallbackTest("before", true, false);
    }

    public function testAfter()
    {
        return $this->makeCallbackTest("after", true, false);
    }

    public function testFinish()
    {
        return $this->makeCallbackTest("finish", true, true);
    }
    
    public function testError()
    {
        return $this->makeCallbackTest("error", false, true);
    }
    
    protected function makeCallbackTest($callback, $good, $bad) 
    {
        $this->makeCallbackRequest($callback, "/test", $good);
        $this->makeCallbackRequest($callback, "/error", $bad);
    }

    protected function makeCallbackRequest($callback, $path, $equals)
    {
        $expected = strtoupper($callback);

        // We'll use this to track our response
        $response = new \stdClass();
        $response->output = '';

        $app = new \Aura\Micro\Micro();

        // Create our callback to test
        $app->$callback(function() use($app, $response, $expected){
            $response->output = $expected;
        });

        $app->get("/test", function() use($app, $response){ });

        // Make a bad request
        $app->run($path, array('REQUEST_METHOD' => 'GET'));

        if (!$equals) {
            $this->assertNotEquals($response->output, $expected, "Failed '{$callback}' not equals on '{$path}'");
        } else {
            $this->assertEquals($response->output, $expected, "Failed '{$callback}' equals on '{$path}'");
        }
    }
}
