<?php
namespace Joomla\Service\Tests;

use Joomla\Service\Command;
use Joomla\Service\CommandBase;
use Joomla\Service\Immutable;

require __DIR__ . '/../vendor/autoload.php';

function it($m,$p){echo"\033[3",$p?'2m✔︎':'1m✘'.register_shutdown_function(function(){die(1);})," It $m\033[0m\n";}
function throws($exp,\Closure $cb){try{$cb();}catch(\Exception $e){return $e instanceof $exp;}return false;}

final class CommandSimpleTest extends CommandBase
{
	public function __construct($test = null)
	{
		$this->test = $test;

		parent::__construct();
	}
}

final class CommandComplexTest extends CommandBase
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

it('should pass if the test command implements the Command interface', (new CommandSimpleTest) instanceof Command);
it('should pass if the test command is an Immutable object', (new CommandSimpleTest) instanceof Immutable);
it('should pass when the constructor argument can be retrieved by a getter method.', (new CommandSimpleTest('testing'))->getTest() == 'testing');
it('should pass when the constructor argument can be retrieved as an object property.', (new CommandSimpleTest('testing'))->test == 'testing');
it('should pass if the getName method returns the name of the test command', (new CommandSimpleTest)->getName() == 'CommandSimpleTest');
it('should pass if the name property contains the name of the test command', (new CommandSimpleTest)->name == 'CommandSimpleTest');
it('should pass if the getRequestedOn method does not throw an exception', (new CommandSimpleTest)->getRequestedOn());
it('should pass if accessing the requestedOn property does not throw an exception', (new CommandSimpleTest)->requestedOn);
it('should throw a \RuntimeException when trying to change the requestedOn time.',
	throws('\RuntimeException', function() {
		$command = new CommandSimpleTest;
		$command->requestedon = 'something';
	})
);
it('should throw a \RuntimeException when trying to instantiate an invalid command',
	throws('\RuntimeException', function() {
		$invalidCommand = new CommandComplexTest;
	})
);
