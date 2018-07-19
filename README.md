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

**Caution : It only works with Mysql databases by now.**

This plugin brings :
- JsonBehavior behavior for models
- JsonTrait trait for entities
- Underlying JsonQuery class extending core Query to manage datfield notation and translate queries

Provided behavior and query are relying on [Mysql JSON_EXTRACT function](https://dev.mysql.com/doc/refman/8.0/en/json-functions.html) to work from CakePHP inside JSON data nearly as if each property was a regular field.

Provided trait provides functions to quick get/set values in JSON data.

Both are based on a [specific custom notation](#Datfield-format-explanation).

## Installation

### Install plugin
You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

```
composer require liqueurdetoile/cakephp-orm-json
```

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
When querying, this notation is automatically converted to JSON_EXTRACT short notation `field->"$.path"`.

Please note that queries can't be filtered by now on JSON array indexes (like logs.0 inside the previous example).

### Performs finds in JSON data
When the JsonBehavior is added to a table, you can get a `JsonQuery` instance like this :
```php
// In your controller
$query = $this->Users->jsonQuery();
$query = $this->Users->find('json');
```

You can late bind a previous `Query` to a `JsonQuery` like this :
```php
// In your controller
$query = $this->Users->find('stuff');
$jsonquery = $this->Users->jsonQuery($query);
```
All existent query options will be cloned into the new `JsonQuery`.

`JsonQuery` extends core `Query` and all core methods are available, plus two specific json chainable functions.
```php
// In your controller
$query = $this->User
  ->find('json')
  ->jsonSelect([
    'prefs.theme@attributes'
  ])
  ->jsonWhere([
    'username@attributes' => 'user'
  ])
  ->all();
```

Alternatively, you can provide parameters to the `find` query :
```php
// In your controller
$query = $this->User
  ->find('json', [
    'json.fields' => ['prefs.theme@attributes'],
    'json.conditions' => ['username@attributes' => 'user'])
  ->all();
```

You can use any usual regular options and mix methods with any of the syntaxes.

When using `jsonSelect`, returned field name is aliased like this : `[Model_]field_path`. You can provide a string as second parameter to change default `_` one.

When using `jsonWhere`, you can any of regular nesting and operator provided as an array. You can also use plain query. In this last case, string values won't be escaped.
```php
// In your controller
$query = $this->User
  ->find('json')
  ->jsonWhere([
    'OR' => [
      'username@attributes =' => 'user'
      'prefs.color@attributes LIKE' => '%dark%'
    ]
  ]);

  $query = $this->User
    ->find('json')
    ->jsonWhere("username@attributes = 'user' OR prefs.color@attributes LIKE '\"%dark\"'")
```
When using array form, string values will be escaped through PDO prepared query.

At this time, you can't use function callbacks to build complex queries.

If you're in need, a `JsonQuery` also expose a `jsonExpression` mthod that will return a core `QueryExpression` that can be latter combined. See API reference for details.

### Use JSON specific methods in entities
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

If providing only field name string to `jsonGet`, the whole data is returned as an object/array. This way, you can easily fetch field properties like this :
```php
// In your controller
$user = $this->Users->get(1);
$username = $user->jsonGet('attributes')->username;
// or with associative array parameter set to true
$username = $user->jsonGet('attributes', true)['username'];
```

### API reference
See [API reference](https://liqueurdetoile.github.io/cakephp-orm-json/)

## Changelog
**v1.0.0**
- Add `Lqdt\OrmJson\ORM\JsonQuery` to support basic formatting of fields names and conditions
- Add `Lqdt\OrmJson\Model\Behavior\JsonBehavior` to enhance tables with JSON cool stuff
- Add `Lqdt\OrmJson\Model\Entity\JsonTrait` to enhance entities with JSON cool stuff
- Only supports `Mysql`

## Todo
By this time, the plugin only translates datfield notation to a suitable format to perform Mysql queries using CakePHP ORM.

The Mysql way of querying cannot be used *as is* in other RDMS.
However, the logic can be ported to other systems, especially those working with TEXT.

This plugin exclusively relies on Mysql JSON_EXTRACT to perform finds. Other JSON functions are not implemented but can be useful (see [Mysql reference](https://dev.mysql.com/doc/refman/8.0/en/json-functions.html)).

Finally support for accessing array values by index may also be added.
