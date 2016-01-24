<?php
namespace Joomla\Service\Tests;

use Joomla\DI\Container;

use Joomla\Service\Command;
use Joomla\Service\CommandBus;
use Joomla\Service\CommandBusBuilder;
use Joomla\Service\CommandHandler;
use Joomla\Service\DomainEventMiddleware;
use Joomla\Service\Query;
use Joomla\Service\QueryHandler;

use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor;
use League\Tactician\Handler\CommandNameExtractor\CommandNameExtractor;
use League\Tactician\Handler\Locator\HandlerLocator;
use League\Tactician\Handler\Locator\CallableLocator;
use League\Tactician\Handler\MethodNameInflector\MethodNameInflector;
use League\Tactician\Handler\MethodNameInflector\HandleInflector;
use League\Tactician\Middleware;
use League\Tactician\Plugins\LockingMiddleware;

require __DIR__ . '/../../../../vendor/autoload.php';

function it($m,$p){echo"\033[3",$p?'2m✔︎':'1m✘'.register_shutdown_function(function(){die(1);})," It $m\033[0m\n";}
function throws($exp,\Closure $cb){try{$cb();}catch(\Exception $e){return $e instanceof $exp;}return false;}

// A mock event dispatcher.
final class MockEventDispatcher {}

// A concrete command.
final class CommandSimpleTest extends Command {}

// A concrete command handler.
final class CommandHandlerSimpleTest extends CommandHandler
{
	public function handle(CommandSimpleTest $command)
	{
		return [];
	}
}

// A concrete query.
final class QuerySimpleTest extends Query
{
	public function __construct($test = '')
	{
		$this->test = $test;

		parent::__construct();
	}
}

// A concrete query handler.
final class QueryHandlerSimpleTest extends QueryHandler
{
	public function handle(QuerySimpleTest $query)
	{
		return 'X' . $query->getTest() . 'Y';
	}
}

$dispatcher = new MockEventDispatcher;
$commandBus = (new CommandBusBuilder($dispatcher))->getCommandBus();

it('should pass if the test command bus has the CommandBus interface', $commandBus instanceof CommandBus);
it('should pass if the command bus has a handle method that takes a Command as a parameter', $commandBus->handle((new CommandSimpleTest)));
it('should pass if the command bus has a handle method that takes a Query as a parameter',
	$commandBus->handle((new QuerySimpleTest('Some content'))) == 'XSome contentY'
);

class Logger
{
	public function log($info)
	{
		echo 'LOG: ' . $info . "\n";
	}
}

class LoggingMiddleware implements Middleware
{
	private $logger = null;

	public function __construct(Logger $logger)
	{
		$this->logger = $logger;
	}

	public function execute($message, callable $next)
	{
		$commandClass = get_class($message);

		$this->logger->log('Starting ' . $commandClass);
		$returnValue = $next($message);
		$this->logger->log('Ending ' . $commandClass);

		return $returnValue;
	}
}

use Joomla\Service\CommandLockingMiddleware;

$dispatcher = new MockEventDispatcher;
$commandBusBuilder = new CommandBusBuilder($dispatcher);

$commandBus = $commandBusBuilder
	->setMiddleware(
		array_merge(
			[new LoggingMiddleware(new Logger)],
			$commandBusBuilder->getMiddleware()
		)
	)
	->getCommandBus()
	;

it('should pass if the test command bus has the CommandBus interface', $commandBus instanceof CommandBus);
it('should pass if the command bus has a handle method that takes a Command as a parameter', $commandBus->handle(new CommandSimpleTest));
it('should pass if the command bus has a handle method that takes a Query as a parameter',
	$commandBus->handle((new QuerySimpleTest('Some content'))) == 'XSome contentY'
);
