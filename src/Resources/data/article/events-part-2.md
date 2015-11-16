---
date: "2015-11-12 10:00:01"
tags: ["Symfony", "Event", "Kernel"]
title: "Symfony events II - Going async"
description: "Let's get asynchronous with the DelayedEventDispatcher!"
---

_Not familar with Symfony Event Dispatcher yet? Start with [Symfony events I - The basics](../events-part-1)_

## Events in Symfony are synchronous

That means when you dispatch an event, the code of the listener is executed right there, not later.

So if an event is fired during the Request process, any listener is also executed during the processing of the request, before any Response can be sent to the client.

> If you have an event trigering a 1 second process in a 200ms request, your client will wait 1,2 seconds for the response.

Worst, the trigerred process could fail and throw an exception, leaving your client with a 500 error.

In fact, in most case, you don't need the result of the process to send the Response to the client.

## Delay the execution of your processes

You need the _consequences_ of domain actions to run __after__ the Response has been sent.

One convenient solution is to pile events in a queue instead of dispatching them directly. Then wait for the Response to be sent and dispach every event waiting in the queue.

### Piling events in a queue

Let's create an Event Dispatcher that wait for the Kernel event _terminate_ to dispatch any event:

```php
<?php

namespace Acme\EventBundle\Dispatcher;

use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Dispatch events on Kernel Terminate
 */
class DelayedEventDispatcher extends ContainerAwareEventDispatcher implements EventDispatcherInterface, EventSubscriberInterface
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
    public static function getSubscribedEvents()
    {
        return [KernelEvents::TERMINATE => 'setReady'];
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
    public function dispatch($eventName, Event $event = null)
    {
        if (!$this->ready) {
            $this->queue[] = ['name' => $name, 'event' => $event];

            return $event;
        }

        return parent::dispatch($eventName, $event);
    }
}
```

Declare the delayed event dispatcher service:

```yaml
services:
    # Delayed Event Dispatcher
    acme_event_bundle.delayed_dispatcher:
        class:  "Acme\EventBundle\Dispatcher\DelayedEventDispatcher"
        parent: "event_dispatcher"
        tags:
            - { name: "kernel.event_subscriber" }
```

Now all you need to do is dispatch your domain events through this `DelayedDispatcher`!

Since this dispatcher only fires events in _kernel.terminate_, your listeners and subscribers will run after the client is served.

__Note:__ If any listener triggers an other event during the _kernel.terminate_ phase, the new event will be dispatched instantly because the `DelayedDispatcher` is now in _ready_ state.

## How about Doctrine events?

Doctrine comes with its own event system, how do we deal with these?
Check out [Symfony events III - Doctrine](../events-part-3).
