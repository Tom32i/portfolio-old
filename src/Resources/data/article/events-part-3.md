---
date: "2015-11-12 10:00:02"
tags: ["Symfony", "Doctrine", "Event", "Doctrine"]
title: "Symfony events III - Doctrine"
description: "Adding Doctrine events to the equation."
---

_We already talked about [setting up an event workflow](../events-part-1) to organise our code and how [making it asynchronous] (../events-part-2) is a good practice._

While defining your domain events, you may have noticed that events often reflect a change in the data.

The action of an user, creating, updating and deleting content in you app will consist in an event: a new user has registered, an order status has changed, ect.

In the context of Symfony, it's likely that you'll rely on Doctrine Events to watch for these changes.

> How can we combine them with our existing Event workflow?

## Doctrine events

Indeed Doctrine provide a convenient way to watch for events occuring on the data.

I'm talking about the [LifeCycle Events](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/events.html#lifecycle-events) and the associated [Listeners and Subscribers](http://symfony.com/doc/current/cookbook/doctrine/event_listeners_subscribers.html).

The classic way to use Doctrine Events, as described in the Symfony documentation: listen for Doctrine events and then "do something with the entity", right there, in the listener.

There is a few problemes with this approach:

1. Actions and consequences are coupled again.
2. We rely on two different event systems.
3. Doctrine events are too tangled with persistence concerns.

For all these reason, I recommand that you only use Doctrine events as a __source of information__ and rely on Symfony Events to link your domain actions and consequences.

So here's how I suggest to extract information from doctrine events:

## Create your domain events

### Naming events

Let's define an event for the three basic operation an data:

```php
<?php

namespace EventBundle;

/**
 * Model event direcotry
 */
class ModelEvents
{
    /**
     * A new model has been created
     */
    const CREATED = 'created';

    /**
     * An existing model has been changed
     */
    const UPDATED = 'updated';

    /**
     * An existing model has been deleted
     */
    const DELETED = 'deleted';
}
```

### The event class

Now we create a class to embody these three events:

``` php
<?php

namespace EventBundle\Event;

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
     * Constructor
     *
     * @param mixed $model
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritdoc}
     */
    public function getModel()
    {
        return $this->model;
    }
}
```

## Agregating Doctrine Events

To catch Doctrine events, we're gonna create a Subscriber. The role of this subscriber is to produce Domain event with data from Doctrine events and feed them to a Symfony dispatcher:

```php
<?php

namespace EventBundle\Event\Subscriber;

use EventBundle\Event\ModelEvent;
use EventBundle\ModelEvents;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Doctrine subscriber
 */
class DoctrineSubscriber implements EventSubscriber
{
    /**
     *  Event Dispatcher
     *
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * Constructor
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            'postPersist',
            'postUpdate',
            'postRemove',
        ];
    }

    /**
     * Post persist event handler
     *
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $event = new ModelEvent($args->getEntity());

        $this->dispatcher->dispatch(ModelEvents::CREATED, $event);
    }

    /**
     * Post update event handler
     *
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $event = new ModelEvent($args->getEntity());

        $this->dispatcher->dispatch(ModelEvents::UPDATED, $event);
    }

    /**
     * Post remove event handler
     *
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        $event = new ModelEvent($args->getEntity());

        $this->dispatcher->dispatch(ModelEvents::DELETED, $event);
    }
}
```

Declare the Doctrine subscriber:

```yaml
services:
    # Doctrine Event Subscriber
    doctrine_event_subscriber:
        class: "EventBundle\Event\Subscriber\DoctrineSubscriber"
        arguments:
            - "@delayed_event_dispatcher"
        tags:
            - { name: "doctrine.event_subscriber", connection: "default" }
```

And voila! We just used Doctrine to produce real Domain events dispatched in Symfony event system.

### Update trick

The problem:

- The `preUpdate` event gives useful information, the list of changes in the entity, but is fired __before__ database operation. So you can't be sure yet that the persistence went through.
- The `postUpdate` assures you that persistence is done but don't have the list of changes.

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

Some time ago, I needed to watch for deleted entities in my app.

I naturally used `postRemove` event, but when I tried to get the identifier of my entity with the `getId` method: the result was `null`.

Indeed Doctrine clear any identifying attribute in your entity after it removed it. It's convenient because you can't re-persist the entity accidentally, but I _needed_ to identify deleted entities in my app!

Fortunately, in the `preRemove` event, the identifiers are available.


### Post flush trick

