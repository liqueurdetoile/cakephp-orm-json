[![Latest Stable Version](https://img.shields.io/github/release/liqueurdetoile/cakephp-orm-json.svg?style=flat-square)](https://packagist.org/packages/liqueurdetoile/cakephp-orm-json
)
[![Build Status](https://travis-ci.org/liqueurdetoile/cakephp-orm-json.svg?branch=master)](https://travis-ci.org/liqueurdetoile/cakephp-orm-json)
[![Coverage Status](https://coveralls.io/repos/github/liqueurdetoile/cakephp-orm-json/badge.svg?branch=master)](https://coveralls.io/github/liqueurdetoile/cakephp-orm-json?branch=master)
[![license](https://img.shields.io/github/license/liqueurdetoile/cakephp-orm-json.svg?style=flat-square)](https://packagist.org/packages/liqueurdetoile/cakephp-orm-json)


# Cake-orm-json plugin

**This branch is for CakePHP 3.5+**

This plugin adds support to perform usual CakePHP ORM operations on JSON types fields.

*Never forget that relational databases **are not primarily designed** to manage non-schemed data and using JSON data fields can issue bad performances.*

However, there is always some cases where JSON fields are handy, especially with EAV data model and this plugin can ease the pain to use them with CakePHP.

**Caution : It only works with Mysql databases > 5.7 (supporting JSON type field) by now.**

This plugin brings :
- DatFieldBehavior behavior for models
- DatFieldTrait trait for entities

Provided behavior and underlying database driver relying on [Mysql JSON_EXTRACT function](https://dev.mysql.com/doc/refman/8.0/en/json-functions.html) to work from CakePHP inside JSON data nearly as if each property was a regular field.

Provided trait provides functions to quick get/set values in JSON data.

Both are based on a [specific custom notation](#Datfield-format-explanation).

## What is changing from v1.x.x ?
In previous versions, we've tried to provide aside ab ORM dedicated to JSON operations through a special Query class. By now, datfield can be used as any regular field in :

 - select statements
 - where statements
 - order statements

They can also be used as foreign keys to link tables together (well, not BelongsToMany yet) !

Under the hood, this plugin now relies on a rewritten mysql driver to seamlessly translate queries to enable JSON operations in a Mysql database.

The best way to dig in is to peep at the test suite.

## Installation

### Install plugin
<strike>You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).</strike>
**The plugin v2.0.0 is still under development and must be installed from this repository : see https://getcomposer.org/doc/05-repositories.md#loading-a-package-from-a-vcs-repository**

<strike>
```
composer require liqueurdetoile/cakephp-orm-json
```
</strike>

The base namespace of the plugin is `Lqdt\OrmJson`.

### Loading plugin `Lqdt/OrmJson`
Go to [CakePHP 3.x reference for loading plugin](https://book.cakephp.org/3.0/en/plugins.html#loading-a-plugin)

You can then add `JsonBehavior` to tables and/or `JsonTrait` to entities.

### Add JSON behavior to tables
See [CakePHP 3.x reference for behaviors](https://book.cakephp.org/3.0/en/orm/behaviors.html#creating-a-behavior)
```php
// App/Model/Table/UsersTable.php
namespace App\Model\Table;

use Cake\ORM\Table;

class UsersTable extends Table
{
    public function initialize(array $config)
    {
        $this->addBehavior('Lqdt/OrmJson.json');
        // [...]
    }
}
```

### Add JSON Trait
See [CakePHP 3.x reference for traits](https://book.cakephp.org/3.0/en/orm/entities.html#creating-re-usable-code-with-traits)
```php
// App/Model/Entity/User.php
namespace App\Model\Entity;

use Cake\ORM\Entity;
use Lqdt\OrmJson\Model\Entity\JsonTrait;

class User extends Entity
{
    use JsonTrait;
}
```

## Usage

### Datfield format explanation
This plugin introduces the `datfield` format (contraction of `dot` and `at field`) like this : <tt>path@[Model.]field</tt> and can be used in the same way regular fields are used.

Path represents the properties way inside Model JSON field. For instance, with this schema :
```
Model = Users
Field = id INT PRIMARY KEY, attributes JSON

attributes field content = {
  "username": "user",
  "prefs": {
    "theme": "lovely",
    "color": "dark"
  },
  logs: [
    "log0",
    "log1"
  ]
}

username@attributes will yield 'user'
username@Users.attributes can be used for model disambiguation

prefs.theme@attributes will yield 'lovely'
```
When querying, this notation is automatically converted to JSON_EXTRACT short notation `field->"$.path"`.

Please note that queries can't be filtered by now on JSON array indexes (like kinda logs.0 inside the previous example).

### Use JSON setter/getter methods with entities

**If you're willing to use datfields as foreign keys, you must enable this trait in your entity class even if you will never use the getters/setters.**

When trait is used in an entity, you can use :
- `Entity::jsonGet` to fetch a value inside JSON data. It will return an object by default. You can get an associative array by providing true as second parameter.
- `Entity::jsonSet` to set a value inside JSON data. Method is chainable or accepts array
- `Entity::jsonIsset` to check if a key is defined inside JSON data
- `Entity::jsonUnset` to delete a key inside JSON data. Method is chainable or accepts array

All of these methods are relying on regular get/set/unset and triggers dirty state of the entity.

```php
// In your controller
$user = $this->Users->get(1);
$username = $user->jsonGet('username@attributes');
$user
  ->jsonSet('prefs.theme@attributes', 'notSoLovely')
  ->jsonSet([
    'metas.blue@attributes' => 'sea',
    'metas.red@attributes' => 'apple'
  ]);
```

If providing only field name string to `jsonGet`, the whole data is returned as an object. This way, you can easily fetch field properties like this :
```php
// In your controller
$user = $this->Users->get(1);
$username = $user->jsonGet('attributes')->username;
```

### API reference
See [API reference](https://liqueurdetoile.github.io/cakephp-orm-json/)

## Changelog
**v2.0.0-dev**
- Replace JsonQuery logic by a dedicated database driver that handles seamlessly the parsing of dat fields

**v1.5.0**
- Full rework of `jsonWhere` to replace previous conditions array parsing by a full `QueryExpression` build that allows the use of query expressions callbacks

**v1.4.0**
- Add support to optionally fetch back an associative array instead having flattened keys when selecting statements

**v1.3.0**
- Add support for dot seperator and dotted aliases in select operations
- Add support for sorting on datfield value
- Add support to accept regular database fields into json specific select, where and order statements

**v1.2.0**
- Add support for aliases in `jsonSelect` and `json.fields` option through associative arrays

**v1.1.0**
- Add support for `newEntity` and `patchEntity` through a `beforeMarshal` event and `jsonmerge`

**v1.0.0**
- Add `Lqdt\OrmJson\ORM\JsonQuery` to support basic formatting of fields names and conditions
- Add `Lqdt\OrmJson\Model\Behavior\JsonBehavior` to enhance tables with JSON cool stuff
- Add `Lqdt\OrmJson\Model\Entity\JsonTrait` to enhance entities with JSON cool stuff
- Only supports `Mysql`

## Disclaimer
By this time, the plugin only translates datfield notation to a suitable format to perform Mysql queries using CakePHP ORM.

The Mysql way of querying cannot be used *as is* in other RDBMS.
However, the logic can be ported to other systems, especially those working with TEXT.

This plugin exclusively relies on Mysql JSON_EXTRACT to perform finds. Other JSON functions are not implemented but can be useful (see [Mysql reference](https://dev.mysql.com/doc/refman/8.0/en/json-functions.html)).
