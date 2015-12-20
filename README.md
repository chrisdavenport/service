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

Whilst the Service Layer presents the public API of a component, the nature of the API is somewhat
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
hand, a query causes no changes to model state, but does produce output.  In other words, a
query has no side-effects for which the caller is held responsible.

Carefully designing an extension to separate these concerns can assist with scalability
because it makes it much easier to have separate models for commands and queries that can
be separately optimised.  In the majority of applications there are many more, often orders of
magnitude more, reads than writes.  So the read ("query") models can be optimised to efficiently
deliver data, whereas the write ("command") models can be optimised to ensure data integrity.
This might mean, for example, implementing the write model on a traditional relational database,
whereas the read model uses denormalised views, perhaps on a NoSQL database.

This is not to say that this separation is enforced.  Only that should the developer wish to make
use of CQRS to optimise performance, the scaffolding to do that is readily available.

The separation of commands from queries is embodied in the Service Layer package by having two
separate base classes for the two kinds of message.  These are handled by separate buses that have
slightly different middleware configurations.

## Service Layer as API

The introduction of a service layer directly addresses the problem of defining a component-level API
that can be used across a variety of "channels".  The API is completely defined by the commands
it can handle and the domain events that are raised as a result and by the queries it can handle
and the data returned as a result.

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

Equivalently, on the query side the service layer would consist of a query, a query bus and a query
handler.  Actually, there is no problem combining the two so a controller might contain a mix of
command and query calls.

Queries can be called hierarchically and will work as expected since they return immediately with
the data requested.  However, commands are always executed sequentially even if a command is initiated
from within another command.  This prevents that would inevitably arise if commands were to be
executed hierarchically.

Commands and queries are simple, lightweight, immutable, value objects.  They can and should contain
simple validation checks in their constructors so that only valid commands may be constructed.  Of
course, more complex validation rule checks may need to be implemented deeper in the code, but the
first level of validation can take place directly in the command/query classes themselves.

Command and query objects are routed, via the bus to exactly one handler.  The handler can perform
whatever logic is required of it.

In the case of a command this will most likely include calls to the model to update state.  At any
time during the command execution one or more domain events may be raised to indicate that something
of significance has occurred.  The command handler must return these on exit.  All domain events that
were raised are then published to all registered listeners for those events. 

In the case of a query, the query will not change any state that the caller would be held responsible
for.  No domain events may be raised.  The handler must return the data requested and nothing else.

### A simple example - command
In this example, a simple command is created and submitted to the command bus.  This routes it to
a command handler using the simple convention that the substring "Command" is replaced in a
case-sensitive manner by "CommandHandler" in the command class name.
```php
use Joomla\Service\CommandBase;
use Joomla\Service\CommandBusProvider;
use Joomla\Service\CommandHandlerBase;
use Joomla\Service\ServiceBase;

// A concrete command.
final class MycomponentCommandDosomething extends CommandBase
{
	public function __construct($arg1, $arg2)
	{
		$this->arg1 = $arg1;
		$this->arg2 = $arg2;

		parent::__construct();
	}
}

// A concrete command handler.
final class MycomponentCommandHandlerDosomething extends CommandHandlerBase
{
	public function handle(MycomponentCommandDosomething $command)
	{
		// Do something here.
	}
}

// Configure the DI container.
$container = (new Container)->registerServiceProvider(new CommandBusProvider);

// Create a command.
$command = new MycomponentCommandDosomething($arg1, $arg2);

// Execute the command.
(new ServiceBase($container))->execute(($command));
```
### A simple example - query
In this example, a simple query is created and submitted to the query bus.  This routes it to
a query handler using the simple convention that the substring "Query" is replaced in a
case-sensitive manner by "QueryHandler" in the query class name.

Note that the query bus does not support domain events and any that are raised will be lost.
```php
use Joomla\Service\QueryBase;
use Joomla\Service\QueryBusProvider;
use Joomla\Service\QueryHandlerBase;
use Joomla\Service\ServiceBase;

// A concrete query.
final class MycomponentQuerySomething extends QueryBase
{
	public function __construct($arg1, $arg2)
	{
		$this->arg1 = $arg1;
		$this->arg2 = $arg2;

		parent::__construct();
	}
}

// A concrete query handler.
final class MycomponentQueryHandlerSomething extends QueryHandlerBase
{
	public function handle(MycomponentQuerySomething $query)
	{
		// Retrieve some data into a data transfer object (DTO) here.

		return $dto;
	}
}

// Configure the DI container.
$container = (new Container)->registerServiceProvider(new QueryBusProvider);

// Create a query.
$query = new MycomponentQuerySomething($arg1, $arg2);

// Execute the query.
$result = (new ServiceBase($container))->execute(($query));
```
## Domain Events

The service layer provides integrated support for handling domain events.
A Domain Event is something that happened that domain experts care about and they are
introduced here in the form of immutable, value objects that are essentially simple messages
with little or no behaviour of their own.  Unlike commands, which have a one-to-one
relationship with their handlers, domain events are published to all registered
listeners and several mechanisms exist to make it easy to extend functionality by
registering in extension code.

### Naming domain events

Although unenforceable, it is good practice to name events using the past tense.
Ideally, the terms used should be meaningful to domain experts.
For example, the domain event raised after successfully executing a RegisterCustomer command,
might be called CustomerWasRegistered, or perhaps just CustomerRegistered. 

### Registering a domain event listener

Any number of domain event listeners, including none at all, may be registered for each domain event type.
Different registration methods may be used depending on the context.  The name of the event, where
a name is required, will be the name of the domain event class with an "on" prefix.

Note that developers should not make any assumptions about the order in which domain event listeners
are executed.

#### Call by convention

Typically used within a component, this method constructs the name of a domain event handler class
from the name of the domain event class itself and if the class exists, its event method is called.
The domain event publisher will look for the keyword "Event" (not case-sensitive) in the domain event
class name and if found will check to see if a class exists with "Event" replaced by "EventListener"
and register that class as a listener.

For example, in a typical component there might be a domain event called "MycomponentEventSomethinghappened".
The publisher will look for a class called "MycomponentEventListenerSomethinghappened" and call its
"onMycomponentEventSomethinghappened" method, passing the domain event object as the single parameter.

If the component uses the traditional camel-case autoloader, the domain event and domain event listener
classes will be found in the following paths:
```
/com_mycomponent/event/somethinghappened.php
/com_mycomponent/event/listener/somethinghappened.php
```
Here's an example of a domain event listener:
```php
final class MycomponentEventListenerSomethinghappened
{
	/**
	 * Event listener.
	 *
	 * Note that it must be declared as static otherwise you will get
	 * a strict standards error.
	 */
	public static function onMycomponentEventSomethinghappened($event)
	{
		// Do whatever you want here.
	}
}
```

#### Calling a Joomla plugin

The event publisher will call the event name trigger method in all installed and enabled Joomla plugins
in the "domainevent" plugin group.

For example, a domain event called "Somethinghappened" will cause the "onSomethinghappened" method to be
called for all installed and enabled plugins in the "domainevent" group, passing the domain event object
as the single parameter.
```php
class PlgDomainEventSomethinghappened extends JPlugin
{
	/**
	 * Event listener triggered on a Somethinghappened event.
	 *
	 * @param   Event      $event      A domain event.
	 */
	public function onSomethinghappened(Event $event)
	{
		// Do whatever you want here.
	}
}
```
#### Registering a callback

Any PHP callable may be registered as a domain event listener.  The function or method called must take
a single argument which will be the domain event object.  Any kind of PHP callable may be used.  For full
details see http://php.net/manual/en/language.types.callable.php.

For example, a class called "MyClass" with a method called "MyMethod" may be registered as a listener for
the domain event "SomethingHappened" using the following code, before passing control to the service layer. 
```php
\JEventDispatcher::getInstance()
	->register('onSomethingHappened', array('MyClass', 'MyMethod));
```

#### Registering a closure

An anonymous function, or closure, may be registered as a domain event listener.  The closure must take
a single argument which will be passed the domain event object.

For example, the following code will register the closure shown as a listener for the "SomethingHappened"
domain event:
```php
\JEventDispatcher::getInstance()
	->register('onSomethingHappened', function($event) { echo 'Do something here'; });
```

### Raising a domain event

Command handlers are expected to return an array of domain events that were raised.  The command bus will
then take care of publishing those events to all registered listeners.  As a convenience, a couple of
methods are available in the CommandHandlerBase class that make it easy to accumulate domain events and
return them.

To raise a domain event, simply instantiate a domain event object and pass it to the command handler's
raiseEvent method.  For example, the following code raises a "Somethinghappened" event, which takes a
couple of arguments, inside a command handler:
```php
$this->raiseEvent(new Somethinghappened($arg1, $arg2));
```
A domain event listener may also raise further domain events by returning them in an array.  In the
following example the listener method raises a domain event and returns it.
```php
final class MycomponentEventListenerSomethinghappened
{
	/**
	 * Event listener.
	 *
	 * Note that it must be declared as static otherwise you will get
	 * a strict standards error.
	 */
	public static function onMycomponentEventSomethinghappened($event)
	{
		// Do whatever you want here.

		return array(
			new MycomponentEventSomethingElseHappened($arg1, $arg2)
		);
	}
}
```
The order of publication of events should not be relied upon.

### Releasing domain events

Once a command handler has finished executing, control passes back to the command bus.  There needs to be
some mechanism for passing any domain events raised in the command handler back to the command bus where
they can be published.  This is done with the releaseEvents method.  For example, the following code shows
a simple command handler class which raises a couple of domain events then returns then back to the
command bus.
```php
final class DoSomething extends CommandHandlerBase
{
	/**
	 * Command handler.
	 * 
	 * @param   DoSomething  $command  A command.
	 * 
	 * @return  array of DomainEvent objects.
	 * @throws  RuntimeException
	 */
	public function handle(DoSomething $command)
	{
		// Some logic goes here.
		
		$this->raiseEvent(new SomethingHappened($arg1, $arg2));

		// Some more logic goes here.
		
		$this->raiseEvent(new SomethingElseHappened($arg3));

		// All done.
		return $this->releaseEvents();
	}
}
```
Since many command handlers often end by raising an event, the releaseEvents method also takes an
optional event object as an argument.  The code above can be shortened a little as follows:
```php
final class DoSomething extends CommandHandlerBase
{
	/**
	 * Command handler.
	 * 
	 * @param   DoSomething  $command  A command.
	 * 
	 * @return  array of DomainEvent objects.
	 * @throws  RuntimeException
	 */
	public function handle(DoSomething $command)
	{
		// Some logic goes here.
		
		$this->raiseEvent(new SomethingHappened($arg1, $arg2));

		// Some more logic goes here.

		// All done.
		return $this->releaseEvents(
			new SomethingElseHappened($arg3)
		);
	}
}
```
## Advanced topics

### Firing another command or query from within a command or query handler

Sometimes it is useful to fire off a query from within a command or query handler.
This is made possible by the fact that the container, which contains a reference to
the singleton command bus, is available as a protected property in all command and
query handler classes.  The query executes and returns immediately.

Sometimes it is tempting to fire a command from within another command and this is
supported as illustrated in the example below.  However, caution should be exercised
as it could potentially break the "golden rule" that only one aggregate should be
modified per request.  Do this only if you are aware of the consequences.

For example,
```php
final class CommandHandlerDoSomething extends CommandHandlerBase
{
	public function handle(CommandDoSomething $command)
	{
		// Get the command bus.
		$service = new ServiceBase($this->container);

		// Do something.

		// Fire off a new command.  This does not execute immediately.
		$service->execute(new CommandDoSomethingMore());

		// Fire off a new query.
		$something = $service->execute(new QueryForSomething());

		// Do some more stuff.
	}
}
```
Bear in mind that, unlike query execution, execution of the command will only start
after the current command and all its raised domain events have finished executing.

### Firing a command or query from within a domain event listener

Sometimes it is useful to fire off a query from within a domain event listener.
This is made possible by the fact that the second argument to the event handler method is
the dependency injection container, which contains a reference to the singleton command bus.
The query executes and returns immediately.

Sometimes it is tempting to fire another command from within a domain event listener and
this is supported as illustrated in the example below.  However, caution should be exercised
as it could potentially break the "golden rule" that only one aggregate should be
modified per request.  Do this only if you are aware of the consequences.
```php
final class EventListenerSomethinghappened
{
	/**
	 * Event listener.
	 *
	 * Note that it must be declared as static otherwise you will get
	 * a strict standards error.
	 *
	 * @param   Event      $event      A domain event.
	 * @param   Container  $container  DI container.
	 * 
	 * @return  array of domain events or null
	 */
	public static function onEventSomethinghappened(Event $event, Container $container)
	{
		// Do whatever you want here.

		// Get the command bus.
		$service = new ServiceBase($container);

		// Execute another command.  This does not execute immediately.
		$service->execute((new DoSomethingElse($event->data)));

		// Fire off a new query.
		$something = $service->execute(new QueryForSomething());
	}
}
```
Note that the new command is queued on the command bus and does not begin execution
until after the current command and all its raised domain events have finished
executing.

### Adding custom middleware to the command and query buses

Part of the power of using a command bus is that it can be wrapped with custom middleware
to take care of a wide variety of tasks that would otherwise be quite difficult.

However, at the present time customising the middleware can only be done by registering
your own command and/or query buses with the DI container.
