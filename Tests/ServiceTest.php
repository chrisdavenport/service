<?php
namespace Joomla\Service\Tests;

use Joomla\DI\Container;
use Joomla\Service\Service;
use Joomla\Service\ServiceBase;
use Joomla\Service\Command;
use Joomla\Service\CommandBase;
use Joomla\Service\CommandBusProvider;
use Joomla\Service\CommandHandlerBase;
use Joomla\Service\Query;
use Joomla\Service\QueryBase;
use Joomla\Service\QueryHandlerBase;
use Joomla\Service\QueryBusProvider;

require __DIR__ . '/../../../../vendor/autoload.php';

function it($m,$p){echo"\033[3",$p?'2m✔︎':'1m✘'.register_shutdown_function(function(){die(1);})," It $m\033[0m\n";}
function throws($exp,\Closure $cb){try{$cb();}catch(\Exception $e){return $e instanceof $exp;}return false;}

// A concrete service.
final class ServiceSimpleTest extends ServiceBase {}

// A concrete command.
final class CommandSimpleTest extends CommandBase {}

// A concrete query.
final class QuerySimpleTest extends QueryBase
{
	public function __construct($test = '')
	{
		$this->test = $test;

		parent::__construct();
	}
}

// A concrete command handler.
final class CommandHandlerSimpleTest extends CommandHandlerBase
{
	public function handle(CommandSimpleTest $command)
	{
		return true;
	}
}

// A concrete query handler.
final class QueryHandlerSimpleTest extends QueryHandlerBase
{
	public function handle(QuerySimpleTest $query)
	{
		return 'X' . $query->getTest() . 'Y';
	}
}

// Configure the DI container.
$container = (new Container)
	->registerServiceProvider(new CommandBusProvider)
	->registerServiceProvider(new QueryBusProvider)
	;
$query = new QuerySimpleTest('Some content');
$service = new ServiceSimpleTest($container);

it('should pass if the test service is an instance of the Service class', (new ServiceSimpleTest($container)) instanceof Service);
it('should pass if the service has an execute method that takes a Command as a parameter', (new ServiceSimpleTest($container))->execute((new CommandSimpleTest)));
it('should pass if the service has an execute method that takes a Query as a parameter',
	(new ServiceSimpleTest($container))->execute((new QuerySimpleTest('Some content'))) == 'XSome contentY'
);
