<?php

namespace Framework\Event;

use Exception;
use Framework\Container\Container;
use Framework\Event\Interfaces\BaseEventInterface;
use ReflectionClass;

class Event
{
    /**
     * @var BaseEventInterface[]
     */
    private static array $listeners = [];

    /**
     * This will add an new event.
     *
     * @param string                                                     $name
     * @param BaseEventInterface|array<BaseEventInterface|string>|string $listener
     *
     * @return void
     */
    public static function add(string $name, BaseEventInterface|array|string $listener): void
    {
        // when is a list of listeners
        if (is_array($listener)) {

            // loop through all the listeners
            foreach ($listener as $item) {
                self::add($name, $item);
            }

            return;
        }

        // validate event class
        self::validateEventClass(is_string($listener) ? $listener : $listener::class);

        // append to events
        self::$listeners[$name] = array_merge(
            self::get($name) ?: [],
            [$listener instanceof BaseEventInterface ? $listener : new $listener()]
        );
    }

    /**
     * This method will apply the default listeners and starts listening for events.
     *
     * @param array<string> $defaultListeners
     *
     * @return void
     */
    public static function listen(array $defaultListeners = []): void
    {
        foreach ($defaultListeners as $name => $listener) {
            self::add($name, $listener);
        }
    }

    /**
     * This method will remove an event.
     *
     * @param string $name
     *
     * @return void
     */
    public static function remove(string $name): void
    {
        unset(self::$listeners[$name]);
    }

    /**
     * This method will notify a given listener.
     *
     * @param string $name
     * @param mixed  $data
     *
     * @return void
     */
    public static function notify(string $name, mixed $data = null)
    {
        // filter out all listeners
        $listeners = collection(self::get($name) ?: [])->filter(fn ($listener) => $listener instanceof BaseEventInterface);

        // check if there where listeners found
        if (empty($listeners)) {
            return;
        }

        // loop through all the listeners
        foreach ($listeners as $listener) {
            // call method with dependencies injection
            Container::handleClassMethod($listener, 'handle', ['event' => $listener, 'data' => $data]);
        }
    }

    /**
     * This method will get all listeners for a event.
     *
     * @param string $name
     *
     * @return array<BaseEventInterface>|null
     */
    public static function get(string $name): ?array
    {
        return self::$listeners[$name] ?? null;
    }

    /**
     * This method will get all events.
     *
     * @return array<array<BaseEventInterface>>
     */
    public static function all()
    {
        return self::$listeners;
    }

    /**
     * This method will validate the given event string class path/name.
     *
     * @param string $class
     *
     * @throws \ReflectionException
     *
     * @return void
     */
    private static function validateEventClass(string $class)
    {
        // make new reflection class
        $reflection = new ReflectionClass($class);

        // check if not implements Event
        if (!$reflection->implementsInterface(BaseEventInterface::class)) {
            throw new Exception("The class `{$class}` must implements `Event` interface!");
        }

        // check if class has handle method and is not private/protected
        if (!$reflection->hasMethod('handle')) {
            throw new Exception("The method `handle` inside the class `{$class}` must exists!");
        }
    }
}
