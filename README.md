# The Service Layer Package

The Service Layer package for Joomla provides a basis on which services can be built that enhance
the regular Model-View-Controller (MVC) structure used in components.

The Service Layer provides a clear API for the component, which is something much needed for the
construction of an external REST API.  Ideally, the Service Layer API is the only route through
which outside code accesses the component.  In other words, the Service Layer defines the
component's public boundary and the only operations publicly available are those that are made
available by the Service Layer.

The following problems are addressed:
* multi-channel
* asynchronous operation
* distributed operation

Whilst the Service Layer presents the public API of a compoennt, the nature of the API is somewhat
different from other APIs that you might be used to working with.
* All inputs and outputs are in the form of message objects.
* Requests are made in the form of immutable message objects.
* Separation of read-only requests from write-only requests.
* Replies to read requests are in the form of data transfer objects (document messages).
* Replies to write requests are in the form of published message objects.

## Command Query Responsibility Segregation (CQRS)

The service layer inherently supports the idea of Command Query Responsibility Segregation (CQRS)
where "commands" are treated slightly differently from "queries".  A command is some action that
causes the model state to be changed in some way, but produces no output as a result.  On the other
hand, a query causes no changes to model state, but does produce output.

Carefully designing an extension to separate these concerns can assist with scalability
because it makes it much easier to have separate models for commands and queries that can
be separately optimised.  In the majority of applications there are many more, often orders of
magnitude more, reads than writes.  So the read ("query") models can be optimised to efficiently
deliver data, whereas the write ("command") models can be optimised to ensure data integrity.
This might mean, for example, implementing the write model on a traditional relational database,
whereas the read model uses denormalised views, perhaps on a NoSQL database.

This is not to say that this separation is enforced.  Only that should the developer wish to make
use of CQRS to optimise performance, the scaffolding to do that is readily available.

## Service Layer as API

The introduction of a service layer directly addresses the problem of defining a component-level API
that can be used across a variety of "channels".

## Installation

1. Install Joomla in the usual way.
2. Merge the statements below into the composer.json file.
3. composer update

```json
{
	"require": {
		"chrisdavenport/service": "dev-master"
	},
	"repositories": [
		{
			"type": "vcs",
			"url": "https://github.com/chrisdavenport/service.git"
		}
	]
}
```

## Using the Service Layer

A simple service layer consists of just three elements.  A command, a command bus and a command handler.
A common example would be a controller that needs to call a model.  Rather than calling the model
directly, the controller instead creates a command object and passes it to the command bus.  The
command bus dispatches the command to the command handler, which then makes calls to the model.

### A simple example  

```php
use Joomla\Service\CommandBase;
use Joomla\Service\CommandBusProvider;
use Joomla\Service\CommandHandlerBase;
use Joomla\Service\ServiceBase;

// A concrete command.
final class MycomponentCommandTest extends CommandBase
{
	public function __construct($arg1, $arg2)
	{
		$this->arg1 = $arg1;
		$this->arg2 = $arg2;

		parent::__construct();
	}
}

// A concrete command handler.
final class MycomponentCommandHandlerTest extends CommandHandlerBase
{
	public function handle(MycomponentCommandTest $command)
	{
		// Do something here.
	}
}

// Configure the DI container.
$container = (new Container)->registerServiceProvider(new CommandBusProvider);

// Create a command.
$command = new MycomponentCommandTest($arg1, $arg2);

// Execute the command.
(new ServiceBase($container))->execute(($command));
```

TO BE CONTINUED
