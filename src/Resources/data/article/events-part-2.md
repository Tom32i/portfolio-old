---
date: "2015-11-12 10:00:01"
tags: ["Symfony", "Event", "Kernel"]
title: "Symfony events II - Going async"
description: "Let's get asynchronous with the DelayedEventDispatcher!"
---

Although we just set up [a domain event workflow](../events-part-1) with the Symfony Event Dispatcher and kept an healthy separation of concerns, our work is not done yet.

There's still a small problem with our code as it is:

## Events in Symfony are synchronous

That means when you dispatch an event, the code of the listener is executed right there, not later.

So if an event is fired during the Request process, any listener is also executed during the processing of the Request, before any Response can be sent to the client.

> If you have an event triggering a 1 second process in a 200ms request, your client will wait 1,2 seconds for the response.

Worst, the triggered process could fail and throw an exception, leaving your client with a 500 error.

_In fact, in most cases, you don't need the result of the process to send the Response to the client._

## Delay the execution of your processes

You need the _consequences_ of domain actions to run __after__ the Response has been sent.

One convenient solution is to stack events in a queue instead of dispatching them directly. Then wait for the Response to be sent and dispatch every event waiting in the queue.

### Piling events in a queue

Let's create an Event Dispatcher that waits for the Kernel event _terminate_ to dispatch any event:

```php
<?php

namespace EventBundle\Event\Dispatcher;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Dispatch events on Kernel Terminate
 */
class DelayedEventDispatcher extends EventDispatcher implements EventSubscriberInterface
{
    /**
     * Queued events
     *
     * @var array
     */
    private $queue = [];

    /**
     * Is the dispatcher ready to dispatch events?
     *
     * @var boolean
     */
    private $ready = false;

    /**
     * {@inheritdoc}
     */
    public function dispatch($eventName, Event $event = null)
    {
        if (!$this->ready) {
            $this->queue[] = ['name' => $name, 'event' => $event];

            return $event;
        }

        return parent::dispatch($eventName, $event);
    }

    /**
     * Set ready
     */
    public function setReady()
    {
        if (!$this->ready) {
            $this->ready = true;

            foreach ($this->queue as $item) {
                $this->dispatch($item['name'], $item['event']);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [KernelEvents::TERMINATE => 'setReady'];
    }
}

```

Declare the delayed event dispatcher service:

```yaml
services:
    # Delayed Event Dispatcher
    delayed_event_dispatcher:
        class: "EventBundle\Event\Dispatcher\DelayedEventDispatcher"
        tags:
            - { name: "kernel.event_subscriber" }

```

Now all you need to do is dispatch your domain events through this `DelayedDispatcher`!

Since this dispatcher only fires events in _kernel.terminate_, your listeners and subscribers will run after the client is served.

__Note:__ If any listener triggers an other event during the _kernel.terminate_ phase, the new event will be dispatched instantly because the `DelayedDispatcher` is now in _ready_ state.

## Custom event dispatcher and tags:

In Symfony, you can use tags to [register Kernel listeners and subscribers](http://symfony.com/doc/current/cookbook/event_dispatcher/event_listener.html#creating-an-event-subscriber).

To get you own set of tags for your domain event dispatcher, use the `RegisterListenersPass` and [register a new compiler pass](http://symfony.com/doc/current/cookbook/service_container/compiler_passes.html):

```php
<?php

namespace EventBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;

/**
 * Event bundle
 */
class EventBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterListenersPass(
            'delayed_event_dispatcher',
            'delayed.event_listener',
            'delayed.event_subscriber'
        ));
    }
}

```

For this to work, you need to make your EventDispatcher a `ContainerAwareEventDispatcher`:

Change its parent class:
```php
<?php

namespace EventBundle\Event\Dispatcher;

use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
// ...

/**
 * Dispatch events on Kernel Terminate
 */
class DelayedEventDispatcher extends ContainerAwareEventDispatcher implements EventSubscriberInterface
{
// ...
}
```

Finnaly, modify its declaration to take the container as a parameter:
```yaml
services:
    # Delayed dispatcher
    delayed_event_dispatcher:
        class: EventBundle\Event\Dispatcher\DelayedEventDispatcher
        arguments:
            - "@service_container"
        # ...
```

This way you can declare listeners and subscribers with just a tag:

```yaml
services:
    # Event Logger
    acme.my_subscriber:
        class: Acme\Event\Subscriber\MyEventSubscriber
        tags:
            - { name: delayed_event_subscriber }
```

## How about Doctrine events?

Doctrine comes with its own event system, how do we deal with these?
Check out [Symfony events III - Doctrine](../events-part-3).
