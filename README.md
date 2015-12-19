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

#### Calling a Joomla plugin

The event publisher will call the event name trigger method in all installed and enabled Joomla plugins
in the "domainevent" plugin group.

For example, a domain event called "Somethinghappened" will cause the "onSomethinghappened" method to be
called for all installed and enabled plugins in the "domainevent" group, passing the domain event object
as the single parameter.

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

TO BE CONTINUED
