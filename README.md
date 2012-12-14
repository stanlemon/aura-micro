# Aura Micro Framework

This is a simple Micro Framework implementation using Aura.Router.  It is designed to model the API exposed by Silex\Application.  It does not fully match the Silex API and there is no concept of service providers.

## Example usage

### Basic micro-framework implementation

	<?php

	$app = new Aura\Micro\Micro();

	$app->before(function(){
		print "Running before" . PHP_EOL;
	});

	$app->after(function(){
		print "Running after" . PHP_EOL;;
	});

	$app->finish(function(){
		print "Running finish" . PHP_EOL;
	});

	$app->error(function(){
		print "Error" . PHP_EOL;
	});

	$app->get("/test", function(){
		print "Testing" . PHP_EOL;
	});

	$app->get("/hello/{:world}", function($world) use($app){
		print "Hello {$world}" . PHP_EOL;
	});

	$app->run();

