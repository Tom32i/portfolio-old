---
date: "2015-10-27 10:00:01"
tags: ["Symfony", "Doctrine", "Event", "Doctrine"]
title: "Symfony event workflow - Part II"
description: "How to write a strong and clean event workflow with Symfony and Doctrine."
---

Changes in the data (create, update, delete) are the main cause of events in your app: a new user, an order status has changed.

When you use Doctrine, you're likely to rely on Doctrine Events to watch these changes.

How does Doctrine events fits in [our clean event workflow](../events-part-2)?

## Doctrine events

Indeed Doctrine provide a convenient way to watch for events occuring on the data. I'm talking about the [LifeCycle Events](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/events.html#lifecycle-events) and the associated [Listeners and Subscribers](http://symfony.com/doc/current/cookbook/doctrine/event_listeners_subscribers.html).

The classic way to use Doctrine Events is described in the Symfony documentation: how to listen for Doctrine events and then "do something with the Product", right in the listener.

There is a few problemes with this approach:

1. Actions and consequences ar not separated
2. Doctrine listeners and subscribers are not Symfony listeners and subscribers, it would be better to stick to one unique event system in you app.
3. Doctrine events are too tied to the persistence process: you can receive an "UpdateEvent" and later learn that the flush did'nt go well, so the update wasn't persisted to the database after all.

For all these reason, I recommand that you only use Doctrine events as a __source of information__ and rely on Symfony Events to code your domain actions and consequences.

So here's how I mix Doctrine with my existing domain event workflow:

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

To catch Doctrine events, we're gonna create a Subscriber. The role of this subscriber is to produce Domain event with data from Doctrine events and feed them to a Symfony dispatcher:

```php
<?php

namespace Acme\EventBundle\Subscriber;

use Acme\EventBundle\Event\CreatedEvent;
use Acme\EventBundle\Event\DeletedEvent;
use Acme\EventBundle\Event\UpdatedEvent;
use Acme\EventBundle\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Doctrine subscriber
 */
class DoctrineSubscriber implements EventSubscriber
{
    /**
     *  Domain Event Dispatcher
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
        $event = new CreatedEvent($args->getEntity());

        $this->dispatcher->dispatch(Events::CREATED, $event);
    }

    /**
     * Post update event handler
     *
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $event = new UpdatedEvent($args->getEntity());

        $this->dispatcher->dispatch(Events::UPDATED, $event);
    }

    /**
     * Post remove event handler
     *
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        $event = new DeletedEvent($args->getEntity());

        $this->dispatcher->dispatch(Events::DELETED, $event);
    }
}
```

And voila! We just used Doctrine to produce real Domain events in Symfony event system.

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
