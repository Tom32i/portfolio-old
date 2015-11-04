---
date: "2015-10-27 10:00:01"
tags: ["Symfony", "Doctrine", "Event", "Doctrine"]
title: "Clean and powerful event workflow - Part II"
description: "How to write a strong and clean event workflow with Symfony and Doctrine."
---

## Doctrine events

When you work with Doctrine Entities and you need to trigger one or more reaction when the data is changed (create, update, delete): you're likely to rely on Doctrine Events.

Indeed Doctrine provide a convenient way to watch for events occuring on the data. I'm talking about the [LifeCycle Events](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/events.html#lifecycle-events) and the associated [Listeners and Subscribers](http://symfony.com/doc/current/cookbook/doctrine/event_listeners_subscribers.html).

The Symfony documentation show us how to listen for Doctrine events and then "do something with the Product", right in the listener.

This is not a separation of actions and consequences!

Also Doctrine listeners and subscribers are not Symfony listeners and subscribers, it would be better to stick to one unique event system in you app.

And there's the problem of persistence, Doctrine events are too tied to the flush process: you can receive an "UpdateEvent" and later learn that the flush did'nt go well, so the update wasn't persisted to the database after all.

For all these reason, I recommand that you only use Doctrine events as a __source of information__ and rely on Symfony Events to code your domain actions and consequences.

So here's how I design my events workflow to be clean and efficient.

## Create your domain events



``` php
<?php

namespace Acme\EventBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Model event
 */
class ModelEvent extends Event
{
    /**
     * Model
     *
     * @var mixed
     */
    protected $model;

    /**
     * Model identifiers
     *
     * @var array
     */
    private $identifiers;

    /**
     * Constructor
     *
     * @param mixed $model
     * @param array $identifiers
     */
    public function __construct($model, array $identifiers = array())
    {
        $this->model       = $model;
        $this->identifiers = $identifiers;
    }

    /**
     * {@inheritdoc}
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * {@inheritdoc}
     */
    public function getModelClassName()
    {
        return get_class($this->model);
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifiers()
    {
        return $this->identifiers;
    }
}
```

## Agregating Doctrine Events

### Update trick

``` php
<?php

namespace Acme\EventBundle\Event;

/**
 * Model event with changes
 */
class ModelChangeEvent extends ModelEvent
{
    /**
     * Changes made to the model
     *
     * @var array
     */
    private $changes;

    /**
     * Constructor
     *
     * @param mixed $model
     * @param array $identifiers
     * @param array $changes
     */
    public function __construct($model, array $identifiers = [], array $changes = [])
    {
        parent::__construct($model, $identifiers);

        $this->changes = $changes;
    }

    /**
     * Get changes
     *
     * @return array
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * Has the given field changed?
     *
     * @param string $field
     *
     * @return boolean
     */
    public function hasChanged($field)
    {
        return isset($this->changes[$field]);
    }
}
```

### Delete trick

## Consider working after the client is served

When you perform an action directly in a listener (like a Doctrine lisener), this action happens during the processing of the request.
Indeed Symfony will wait for every listeners to be complete before resuming the processing of the Request, and return a Response to the client.

So if you have an event trigering a 1 second process in a 200ms request, your client will wait 1,2 secondes for the response.

In most case, you don't need the result of the process to send the Response to the client!

### Delay the execution of your processes

You need your time-consuming process to run when the Response has been sent.
Just use the `Terminate` event!

- kernel.request: a Request hit the application
    - Request processing is modifing the datas and firing Doctrine events
    - Doctrine listener agregate events
- kernel.response: a Response is there!
- kernel.terminate: the Response was sent
    - Dispatching domain events
    - Domain listeners operating domain processes
