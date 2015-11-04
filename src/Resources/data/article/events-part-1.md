---
date: "2015-10-27 10:00:00"
tags: ["Symfony", "Event", "Kernel"]
title: "Clean and powerful event workflow - Part I"
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

The best way to organize actions that triggers consequences in you app are __events__.

It allows you to separate actions and consequences:

- __Define a domain action:__ Create an Event object and name it
- __Define a domain consequence:__ Create a Listener for that Event.
- __Notifing your app that an action occured:__ Dispatch the corresponding event.

## Events in Symfony

Fortunately Symfony provides [an event system](http://symfony.com/doc/current/components/event_dispatcher/introduction.html).

Events are used in the heart of Symfony: the HTTP Kernel is organised around Kernel Events such as _kernel.request_,  _kernel.response_ and  _kernel.terminate_.

## Create your domain events

Your domain events are meant to transport any relevant information about what happened. They can be anything, event an empty class. The only requirement is to implement `Symfony\Component\EventDispatcher\Event`.

## Setup your workflow

Declare dispatchers and listeners

## Consider working after the client is served

Events in Symfony are synchronous, that means When you perform an action directly in a listener, the listener code is executed right when the event is fired.

So if an event is fired during the Request process, the corresponding action also happens during the processing of the request.

Indeed Symfony will wait for every listeners to be complete before resuming the processing of the Request, and return a Response to the client.

So if you have an event trigering a 1 second process in a 200ms request, your client will wait 1,2 secondes for the response.

In most case, you don't need the result of the process to send the Response to the client!

### Delay the execution of your processes

You need your time-consuming process to run when the Response has been sent.

> Just use the `Terminate` event!

- kernel.request: a Request hit the application
    - Request processing is modifing the datas and firing Doctrine events
    - Doctrine listener agregate events
- kernel.response: a Response is there!
- kernel.terminate: the Response was sent
    - Dispatching domain events
    - Domain listeners operating domain processes

### Piling events in a queue

Let's create an Event Dispatcher that wait for the Kernel event _terminate_ to dispatch any event:

```php
<?php

use Acme\EventBundle\Dispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Dispatch events on Kernel Terminate
 */
class DelayedEventDispatcher extends ContainerAwareEventDispatcher implements EventSubscriberInterface
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
    # Event Dispatcher
    acme_event_bundle.dispatcher:
        class:  "Acme\EventBundle\Dispatcher\EventDispatcher"
        parent: "event_dispatcher"
        tags:
            - { name: "kernel.event_subscriber" }
```
