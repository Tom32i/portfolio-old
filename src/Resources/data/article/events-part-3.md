---
date: "2015-11-12 10:00:02"
tags: ["Symfony", "Doctrine", "Event", "Doctrine"]
title: "Symfony events III - Doctrine"
description: "Adding Doctrine events to the equation."
---

_We already talked about [setting up an event workflow](../events-part-1) to organise our code and how [making it asynchronous](../events-part-2) is a good practice._

While defining your domain events, you may have noticed that events often reflect a change in the data.

The action of a user, creating, updating and deleting content in your app will consist in an event: a new user has registered, an order status has changed, etc.

In the context of Symfony, you are likely to rely on Doctrine Events to watch for these changes.

> How can we combine them with our existing Event workflow?

## Doctrine events

Indeed Doctrine provides a convenient way to watch for events occurring on the data.

I'm talking about the [LifeCycle Events](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/events.html#lifecycle-events) and the associated [Listeners and Subscribers](http://symfony.com/doc/current/cookbook/doctrine/event_listeners_subscribers.html).

The classic way to use Doctrine Events, as described in the Symfony documentation: listen for Doctrine events and then "do something with the entity", right there, in the listener.

There is a few problems with this approach:

1. Actions and consequences are coupled again.
2. We rely on two different event systems.
3. Doctrine events are too tangled with persistence concerns.

For all these reason, I recommend that you only use Doctrine events as a __source of information__ and rely on Symfony Events to link your domain actions and consequences.

So here's how I suggest to extract information from doctrine events:

## Create your domain events

Let's create 3 generic events that reflects changes on the data:

- Created
- Updated
- Deleted

### Naming events

Let's define an event for the three basic operation on data:

```php
<?php

namespace EventBundle;

/**
 * Model event directory
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

## Aggregating Doctrine Events

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

- The `preUpdate` event provides useful information, the list of changes in the entity, but is fired __before__ database operation. So you can't be sure yet that the persistence went through.
- The `postUpdate` assures you that persistence is done but does not hold the list of changes.

Let's create a new Event class to carry this new piece of information:

``` php
<?php

namespace Acme\EventBundle\Event;

/**
 * Model event with changes
 */
class ModelChangedEvent extends ModelEvent
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

Now we complete our Doctrine subscriber:

```php
<?php

// ...

class DoctrineSubscriber implements EventSubscriber
{
    // ...
    /**
     *  Entities
     *
     * @var array
     */
    private $entities;

    /**
     *  Changes
     *
     * @var array
     */
    private $changes;

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            // ...
            'preUpdate',
        ];
    }

    /**
     * Pre update event handler
     *
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $this->setChangeSet($args->getEntity(), $args->getEntityChangeSet());
    }

    /**
     * Post update event handler
     *
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $event  = new ModelChangedEvent($entity, $this->getChangeSet($entity));

        $this->dispatcher->dispatch(ModelEvents::UPDATED, $event);
    }

    /**
     * Set change set for the given entity
     *
     * @param mixed $entity
     * @param array $changeSet
     */
    private function setChangeSet($entity, array $changeSet)
    {
        $index = $this->indexEntity($entity);
        $this->changeSets[$index] = $changeSet;
    }

    /**
     * Get change set for the given entity
     *
     * @param mixed $entity
     *
     * @return array
     */
    private function getChangeSet($entity)
    {
        if (false !== $index = $this->getEntityIndex($entity)) {
            return  $this->changeSets[$index];
        }

        return [];
    }

    /**
     * Store an entity in the list and return its index
     *
     * @param mixed $entity
     *
     * @return integer
     */
    private function indexEntity($entity)
    {
        if (!in_array($entity, $this->entities)) {
            $this->entities[] = $entity;
        }

        return $this->getEntityIndex();
    }

    /**
     * Get the index of the given entity in the list
     *
     * @param mixed $entity
     *
     * @return integer
     */
    private function getEntityIndex($entity)
    {
        return array_search($entity, $this->entities);
    }
```

### Delete trick

Some time ago, I needed to watch for deleted entities in my app.

I naturally used `postRemove` event, but when I tried to get the identifier of my entity with the `getId` method: the result was `null`.

Indeed Doctrine cleans any identifying attribute in your entity after it removed it. It's convenient because you can't re-persist the entity accidentally, but I _needed_ to identify deleted entities in my app!

Fortunately, in the `preRemove` event, the identifiers are available.

```php
<?php

// ...

class DoctrineSubscriber implements EventSubscriber
{
    // ...

    /**
     * Identifiers
     *
     * @var array
     */
    private $identifiers;

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            // ...
            'preRemove',
        ];
    }

    /**
     * Post remove event handler
     *
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $entity        = $args->getEntity();
        $entityManager = $args->getEntityManager();
        $classMetadata = $entityManager->getClassMetadata(get_class($entity));
        $identifiers   = $classMetadata->getIdentifierValues($entity);

        $this->setIdentifiers($entity, $identifiers);
    }

    /**
     * Post remove event handler
     *
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $event = new ModelDeletedEvent($entity, $this->getIdentifiers($entity));

        $this->dispatcher->dispatch(ModelEvents::DELETED, $event);
    }

    /**
     * Set identifiers for the given entity
     *
     * @param mixed $entity
     * @param array $identifiers
     */
    private function setIdentifiers($entity, array $identifiers)
    {
        $index = $this->indexEntity($entity);
        $this->identifiers[$index] = $identifiers;
    }

    /**
     * Get identifiers for the given entity
     *
     * @param mixed $entity
     *
     * @return array
     */
    private function getIdentifiers($entity)
    {
        if (false !== $index = $this->getEntityIndex($entity)) {
            return  $this->identifiers[$index];
        }

        return [];
    }
```

### Post flush trick

