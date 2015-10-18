# Clean Doctrine events

Every time you're client tells you "When this thing append, then the app should do that something", you're about to implement an event workflow.

When an action on content (create, update, delete) triggers one or more reaction: you're likely to rely on Doctrine Events.

Indeed Doctrine provide a convenient way to watch for events occuring on the data. I'm talking about the [LifeCycle Events](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/events.html#lifecycle-events) and the associated [Listeners and Subscribers](http://symfony.com/doc/current/cookbook/doctrine/event_listeners_subscribers.html).

The Symfony documentation show us how to listen for Doctrine events and then "do something with the Product".

But I think we too often rely on Doctrine event to "do something" whereas they should only be used as sources of information.

Why do I...?
- Doctrine listeners and subscribers are not Symfony listeners and subscribers.
- They're too close to the persistence concern (if flush fail, then no events).

That's why I recommend that you use your own

## Use domain events

## Consider working after the client is served

