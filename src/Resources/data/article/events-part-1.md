---
date: "2015-10-27 10:00:00"
tags: ["Symfony", "Event", "Kernel"]
title: "Symfony event workflow - Part I"
description: "How to write a strong and clean event workflow with Symfony and Doctrine."
---

## About actions and consequences

It's monday and your client tells you:

> When a new user register, then the app should send a notification to the administrator.

The most straight forward way to implement that in Symfony is to write code that _create an admin notification_ in the controller that _successfully registered a new user_.

- But what happens when the user is created from elsewhere, like a CRON import task?
- What if you client needs to add other actions to perform on user registration?
- Why does the user get an error if the code that creates the admin notification fails and throws an exception?

All these problem appear when you didn't properly separated __actions__ and __consequences__ in your application.

# Events to the rescue

The best way to organize actions that triggers consequences in you app are __events__ and __listeners__:

- __Define a domain action:__ Create an Event object and name it
- __Define a domain consequence:__ Create a Listener for that Event.
- __Notifing your app that an action occured:__ Dispatch the corresponding event.

## Events in Symfony

Fortunately Symfony comes with [an event system](http://symfony.com/doc/current/components/event_dispatcher/introduction.html).

Events are used in the heart of Symfony: the HTTP Kernel itself is organised around Kernel Events such as _kernel.request_,  _kernel.response_ and  _kernel.terminate_.

## Create your domain events

Your domain events are meant to transport any relevant information about what happened. They can be anything, even an empty class. The only requirement is to extends `Symfony\Component\EventDispatcher\Event`.

[See full documentation](http://symfony.com/doc/current/components/event_dispatcher/introduction.html#creating-an-event-object)

## Setup your workflow

Again, Symfony's documentation give you everything you need to setup your event workflow:

- [Create a Dispactcher](http://symfony.com/doc/current/components/event_dispatcher/introduction.html#the-dispatcher)
- [Define your Subscribers](http://symfony.com/doc/current/components/event_dispatcher/introduction.html#using-event-subscribers)

# Separating concerns

Now that you followed the doc, you have a working event workflow.
But we did't yet properly separated concerns.

## Consider working after the client is served

Events in Symfony are __synchronous__, that means when you perform an action directly in a listener, the listener code is executed right when the event is fired.

So if an event is fired during the Request process, the corresponding action also happens during the processing of the request.

Indeed Symfony will wait for every listeners to be complete before resuming the processing of the Request, and return a Response to the client.

So if you have an event trigering a 1 second process in a 200ms request, your client will wait 1,2 secondes for the response. Worst, the trigerred process could fail and throw an exception, leaving your client with a 500 error.

In most case, you don't need the result of the process to send the Response to the client!

## Delay the execution of your processes

You need your _consequence_ process to run __after__ the Response has been sent.

One convenient solution is to pile events in a queue instead of dispatching them directly. Then wait for the Response to be sent and dispach every event waiting in the queue.

### Piling events in a queue

Let's create an Event Dispatcher that wait for the Kernel event _terminate_ to dispatch any event:

```php
<?php

namespace Acme\EventBundle\Dispatcher;

use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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

Declare the delayed event dispatcher as a _service_ and a _subscriber_.

```yaml
services:
    # Delayed Event Dispatcher
    acme_event_bundle.delayed_dispatcher:
        class:  "Acme\EventBundle\Dispatcher\DelayedEventDispatcher"
        parent: "event_dispatcher"
        tags:
            - { name: "kernel.event_subscriber" }
```

Now all you need to do is to dispatch your domain events through this DelayedDispatcher!

Since this dispatcher only dispatches events in _kernel.terminate_, your listeners and subscribers will run processes after the client is served.

## How about Doctrine events?

Doctrine comes with its own event system, how can we integrate it in this workflow?
We'll see that in my next post "Symfony event workflow - Part II", stay tuned.
