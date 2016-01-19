<?php
namespace Joomla\Service\Tests;

use Joomla\Service\Query;
use Joomla\Service\QueryBase;
use Joomla\Service\Immutable;

require __DIR__ . '/../../../../vendor/autoload.php';

function it($m,$p){echo"\033[3",$p?'2m✔︎':'1m✘'.register_shutdown_function(function(){die(1);})," It $m\033[0m\n";}
function throws($exp,\Closure $cb){try{$cb();}catch(\Exception $e){return $e instanceof $exp;}return false;}

final class QuerySimpleTest extends QueryBase
{
	public function __construct($test = null)
	{
		$this->test = $test;

		parent::__construct();
	}
}

final class QueryComplexTest extends QueryBase
{
	protected $arg1 = null;
	protected $arg2 = null;

	public function __construct($arg1 = null, $arg2 = null)
	{
		parent::__construct();

		$this->validate($arg1, $arg2);

		$this->arg1 = $arg1;
		$this->arg2 = $arg2;
	}

	private function validate($arg1, $arg2)
	{
		if (is_null($arg1))
		{
			throw new \RuntimeException('Argument 1 cannot be null');
		}
	}
}

it('should pass if the test query implements the Query interface', (new QuerySimpleTest) instanceof Query);
it('should pass if the test query is an Immutable object', (new QuerySimpleTest) instanceof Immutable);
it('should pass when the constructor argument can be retrieved by a getter method.', (new QuerySimpleTest('testing'))->getTest() == 'testing');
it('should pass when the constructor argument can be retrieved as an object property.', (new QuerySimpleTest('testing'))->test == 'testing');
it('should pass if the getName method returns the name of the test query', (new QuerySimpleTest)->getName() == 'QuerySimpleTest');
it('should pass if the name property contains the name of the test query', (new QuerySimpleTest)->name == 'QuerySimpleTest');
it('should pass if the getRequestedOn method does not throw an exception', (new QuerySimpleTest)->getRequestedOn());
it('should pass if accessing the requestedOn property does not throw an exception', (new QuerySimpleTest)->requestedOn);
it('should throw an \InvalidArgumentException when trying to change the requestedOn time.',
	throws('\InvalidArgumentException', function() {
		$query = new QuerySimpleTest;
		$query->requestedon = 'something';
	})
);
it('should throw a \RuntimeException when trying to instantiate an invalid query',
	throws('\RuntimeException', function() {
		$invalidQuery = new QueryComplexTest;
	})
);
