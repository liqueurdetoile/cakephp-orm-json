# Cake-orm-json plugin

This plugin adds support to perform usual ORM operations on JSON types fields. **It's still WIP**.

*Never forget that relational databases **are not primarily designed** to manage non-schemed data.* However, there is always some cases where JSON fields are handy and this plugin can ease the pain to use them with CakePHP.

**Caution : It only works with Mysql databases by now.**

This plugin brings :
- JsonBehavior behavior for models
- JsonTrait trait for entities
- Underlying JsonQuery class based on Query to manage datfield notation and translate queries

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

```
composer require liqueurdetoile/cakephp-orm-json
```

The base namespace of the plugin is `lqdt\Coj`

## Quick reference
### Add JSON behavior
TODO

### Add JSON Trait
TODO

## Usage

### Datfield format explanation
This plugin introduces the `datfield` format (contraction of `dot` and `at field`) like this : <tt>path@[Model.]field</tt> and can be used in any Json specific functions brought by behavior and trait in the same way fields are used in regular core data functions.

Path represents the properties way inside Model JSON field. For instance :
```
Model = Users
Field = attributes
attributes field content = {
  username: 'user',
  prefs: {
    theme: 'lovely',
    color: 'dark'
  }
}

username@attributes will yield 'user'
username@Users.attributes can be used for model disambiguation

prefs.theme@attributes will yield 'lovely'
```
Please note that queries can't be filtered upon array values (like logs.0 inside the previous example).

### Use JSON specific methods in finds
TODO

### Use JSON specific methods in entities
TODO

## Changelog
**v1.0.0**
- Add `lqdt\Coj\ORM\JsonQuery` to support basic formatting of fields names and conditions
- Add `lqdt\Coj\Model\Behavior\JsonBehavior` to enhance tables with JSON cool stuff
- Add `lqdt\Coj\Model\Entity\JsonTrait` to enhance entities with JSON cool stuff
- Only supports `Mysql`

## Todo
By this time, the plugin only translates datfield notation to a suitable format to perform Mysql queries using CakePHP ORM.

The Mysql way of querying cannot be used *as is* in other RDMS.
However, the logic can be ported to other systems, especially those working with TEXT.

This plugin exclusively relies on Mysql JSON_EXTRACT to perform finds. Other JSON functions are not implemented but can be useful (see [Mysql reference](https://dev.mysql.com/doc/refman/8.0/en/json-functions.html)).

Finally support for accessing array values by index may also be added.
