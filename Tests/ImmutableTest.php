<?php
namespace Joomla\Service\Tests;

use Joomla\Service\Immutable;

require __DIR__ . '/../../../../vendor/autoload.php';

function it($m,$p){echo"\033[3",$p?'2m✔︎':'1m✘'.register_shutdown_function(function(){die(1);})," It $m\033[0m\n";}
function throws($exp,\Closure $cb){try{$cb();}catch(\Exception $e){return $e instanceof $exp;}return false;}

final class ImmutableTest extends Immutable
{
	public function __construct($test = null)
	{
		$this->test = $test;

		parent::__construct();
	}
}

it('should throw a \RuntimeException when trying to get a non-existant property',
	throws('\RuntimeException', function() {
		$something = (new ImmutableTest)->doesNotExist;
	})
);
it('should throw a \RuntimeException when trying to create a new property',
	throws('\RuntimeException', function() {
		$testObject = new ImmutableTest;
		$testObject->test = 'something';
	})
);
it('should pass when the constructor argument can be retrieved by a getter method.', (new ImmutableTest('testing'))->getTest() == 'testing');
it('should pass when the constructor argument can be retrieved as an object property.', (new ImmutableTest('testing'))->test == 'testing');
it('should pass when property names are not case-sensitive.', (new ImmutableTest('testing'))->TeSt == 'testing');
