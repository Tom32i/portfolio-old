---
date: "2015-08-27"
tags: []
title: "Better serialization with Symfony"
description: "How to write strong and clean Serialization process with Symfony2 and why you should."
---

If you ever built an export script or an API, you surely had to format your content and deal with serialization.

In Symfony, I often see this matter handled by the [JMS Serializer](http://jmsyst.com/libs/serializer), as it is suggested by Symfony documentation.

But after using it in several projects, I'm not totally happy with it.
I've encountered small problems, mostly __assumptions that don't fit my needs__ and __can't be overrided or redefined easily__.
Wich makes them deal-breakers in my opinion.

The solution may be fine fine for big backends and API where you just want to have your entities serialized "automatically".

However, if you're working on specific domain logic for small/medium projects (as I mostly do), you might want to look more flexible solutions.

But you know what?

## Symfony has a great serialization component!

When I find myself struggling with a third-party component that is supposed to solve a problem for me, I often ask myself:

> What would Symfony do?

Is this case, Symfony already addressed the problem of content serialization with the __Serializer Component__.

In the Symfony serialization component, a serializer is composed of two half:

- The __Normalizer__: responsible for transforming the source object into an array (_normalize/denormalize_).
- The __Encoder__: responsible for transforming the normalised data into a formatted string (_encode/decode_).

[![](http://symfony.com/doc/current/_images/serializer_workflow.png)](http://symfony.com/doc/current/components/serializer.html)

You can provide the serializer with several normalizers and encoders so it can handle more serialization cases.

Before going further, I recommend that you refresh your memory with [the documentation](http://symfony.com/doc/current/components/serializer.html) if you're not familiar with this component.

### Your domain logic lies into the Normalizer

The Serializer component povide you with several encoders (notably for _JSON_ and _YML_) but you could write an encoder for any format you need: _CSV_, _YAML_,...

But the heart of the problem of Serialization is to transform your object into array (a.k.a the normalization), that's what you do in JMS when you write annotations to tell which property should be included and how.

_That's where the value is, so that's where you want to put your time and efforts._

> Need to serialize a specific entity in a specific way?
Declare a Normalizer that support this single model!

You will have access to the entity and __all the power of a service__:

- Call a third-party service for information (database, webservice, ...)
- Serialize all the objects, not just entities (did you ever needed _form errors_ in JSON?)
- Have several serializers handle different needs in your app for the same model

To write a custom normalizer, you need to implements NormalizerInterface, wich describes two methods:

- __supportsNormalization__: Answer the question "Can you normalize that object?".
- __normalize__: Do the transformation from object into array.

Here's an exemple:

``` php
<?php

namespace Acme\Serializer\Normalizer;

use Acme\Model\User;
use Acme\Model\Group;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * User normalizer
 */
class UserNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        return [
            'id'     => $object->getId(),
            'name'   => $object->getName(),
            'groups' => array_map(
                function (Group $group) {
                    return $group->getId();
                },
                $object->getGroups()
            )
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof User;
    }
}
```

The result of the normalization:

``` php
<?php
[
    'id'     => 1,
    'name'   => 'Foo Bar',
    'groups' => [1, 2]
]
```

You are free to add some complexity here, you've separated the _model_ from the _serialization of the model_. Hurrah for decoupling \o/

### Handling object associations

When normalizing an object, you might encouter relations to other objects that the Normalizer doesn't support. The `SerializerAwareNormalizer` is here to help you:

When your normalizer exends the `SerializerAwareNormalizer`, it will recieve the parent Serializer as a dependence. So you can send back the normalization of other objects back to the serializer (which may have other Normalizers for that object you can't normalize).

Here's an exemple:

``` php
<?php

namespace Acme\Serializer\Normalizer;

// ...
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;

/**
 * User normalizer
 */
class UserNormalizer extends SerializerAwareNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        return [
            // ...
            'groups' => array_map(
                function ($object) use ($format, $context) {
                    return $this->serializer->normalize($object, $format, $context);
                },
                $object->getGroups()
            ),
        ];
    }
}
```

All you need to do now is to write a Normalizer that support `Group` objects!

``` php
<?php

// ...

/**
 * Group normalizer
 */
class GroupNormalizer extends SerializerAwareNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        return [
            'id'   => $object->getId(),
            'name' => $object->getName(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Group;
    }
}
```

The result of the normalization:

``` php
<?php
[
    'id'        => 1,
    'firstname' => 'Foo',
    'lastname'  => 'Bar',
    'groups'    => [
        [
            'id'   => 1,
            'name' => 'FooFighters'
        ],
        [
            'id'   => 2,
            'name' => 'BarFighters'
        ],
    ],
]
```
### The context

The Serializer component offers a `$context` variable that is passed on throught the whole serialization process.

You can use it to store any information that your normalizer would need and affect their behavior.

``` php
<?php

namespace Acme\Serializer\Normalizer;

// ...
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;

/**
 * User normalizer
 */
class UserNormalizer extends SerializerAwareNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        return [
            // ...
            'groups' => array_map(
                function ($object) use ($format, $context) {
                    if ($context['include_relations']) {
                        return $this->serializer->normalize($object, $format, $context);
                    } else {
                        return $object->getId();
                    }
                },
                $object->getGroups()
            ),
        ];
    }
}
```

Can you see how our Serializer is getting flexible and powerfull?

### The Serializer(s) as service(s)

Declare a service for each of the encoders you will need:

``` yaml
services:
    # JSON Encoder
    acme.encoder.json:
        class: 'Symfony\Component\Serializer\Encoder\JsonEncoder'

    # XML Encoder
    acme.encoder.xml:
        class: 'Symfony\Component\Serializer\Encoder\XmlEncoder'
```

Declare your custom normalizers as services:

``` yaml
services:
    # User Normalizer
    acme.normalizer.user:
        class: 'Acme\Serializer\Normalizer\UserNormalizer'

    # Group Normalizer
    acme.normalizer.group:
        class: 'Acme\Serializer\Normalizer\GroupNormalizer'
```

Compose as many serializers as you need with different normalizers:

``` yaml
services:
    # Serializer
    acme.serializer:
        class: 'Symfony\Component\Serializer\Serializer'
        arguments:
            0:
                - '@acme.normalizer.user'
                - '@acme.normalizer.group'
            1:
                - '@acme.encoder.json'
                - '@acme.encoder.xml'
```

_Note:_ If you [enabled the serializer services](http://symfony.com/doc/current/cookbook/serializer.html), as I did here, you can use the `serializer.normalizer.object` service as a fallback normalizer for all object that you din't specifically handled with a custom normalizer.
