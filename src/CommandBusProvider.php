<?php
/**
 * @package     Joomla.Framework
 * @subpackage  Service Layer
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Service;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor;
use League\Tactician\Handler\Locator\CallableLocator;
use League\Tactician\Handler\MethodNameInflector\HandleInflector;
use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Plugins\LockingMiddleware;

/**
 * Registers a command bus service provider.
 * 
 * @since   __DEPLOY__
 */
class CommandBusProvider implements ServiceProviderInterface
{
	/**
	 * Registers the command bus service provider.
	 *
	 * @param   Container  $container  A dependency injection container.
	 *
	 * @return  void
	 * @since   __DEPLOY__
	 */
	public function register(Container $container)
	{
		$container->share('commandbus',

			function(Container $c)
			{
		        $handlerMiddleware = new CommandHandlerMiddleware(
		            new ClassNameExtractor(),
					new CallableLocator(
						function($commandName)
						{
							// Break apart the fully-qualified class name.
							// We do this so that the namespace path is not modified.
							$parts = explode('\\', $commandName);

							// Determine the handler class name from the command class name.
							$handlerName = str_replace('Command', 'CommandHandler', array_pop($parts));

							// Construct the fully-qualified class name of the handler.
							$serviceName = implode('\\', $parts) . '\\' . $handlerName;

							return new $serviceName();
						}
					),
		            new HandleInflector()
		        );

				$middleware = [
					new LockingMiddleware(),	// Prevent one command from being executed while another is already running.
					new DomainEventMiddleware(\JEventDispatcher::getInstance()),
//					new \LoggingMiddleware,
					$handlerMiddleware
		        ];
				
		        return new CommandBusBase($middleware);
			},
			true);
	}
}