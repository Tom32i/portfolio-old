---
date: "2015-08-27T06:20:39Z"
description: "A simple test of blog article. You should love it."
tags: []
title: "Better serialization with symfony"
---

If you ever built an export process or an API, you surely had to format you content and deal with serialization.

In Symfony, I often see this matter settled using the [JMS Serializer](http://jmsyst.com/libs/serializer), as it is recommended by Symfony documenation itself.

I've encoutered several problems while using it:

1. Your entities are responsible for declaring the way they are to be serialized (that's particularly the case if you use annotations).
2. It assume the 1 entity = 1 output format. You can play with group to make this output format "vary" but that's not enough in my opinion.
3. Deepth management is very painful and complicated. I had a hard time configuring properly which sub-object should be serialized and how.

I think this tool is fine for big backends and API where you juste want to provide some guidelines and have your entities serialized "automatically".

However, if you're working on specific domain logic small projects (as I mostly do), JMS Serializer is probably going to loose your time instead of saving it.

But you know what?

## Symfony has a [great serialization component](http://symfony.com/doc/current/components/serializer.html)!

When I find myself struggling with a third-party component that is suposed to solve a problem for me, I often ask myself "What would Symfony do?".

Is this case, Symfony already adressed the problem of content serialization.
