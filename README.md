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
- JsonBehavior behavior for models
- JsonTrait trait for entities
- Underlying JsonQuery class extending core Query to manage datfield notation and translate queries

Provided behavior and query are relying on [Mysql JSON_EXTRACT function](https://dev.mysql.com/doc/refman/8.0/en/json-functions.html) to work from CakePHP inside JSON data nearly as if each property was a regular field.

Provided trait provides functions to quick get/set values in JSON data.

Both are based on a [specific custom notation](#Datfield-format-explanation).

<!-- TOC depthFrom:2 depthTo:6 withLinks:1 updateOnSave:1 orderedList:0 -->

- [Installation](#installation)
	- [Install plugin](#install-plugin)
	- [Loading plugin `Lqdt/OrmJson`](#loading-plugin-lqdtormjson)
	- [Add JSON behavior to tables](#add-json-behavior-to-tables)
	- [Add JSON Trait](#add-json-trait)
- [Usage](#usage)
	- [Datfield format explanation](#datfield-format-explanation)
	- [Performs finds in JSON data](#performs-finds-in-json-data)
		- [Selecting datfields](#selecting-datfields)
		- [filtering datfields](#filtering-datfields)
		- [Sorting datfields](#sorting-datfields)
	- [Create and update JSON in an entity from model](#create-and-update-json-in-an-entity-from-model)
	- [Use JSON setter/getter methods with entities](#use-json-settergetter-methods-with-entities)
	- [API reference](#api-reference)
- [Changelog](#changelog)
- [Disclaimer](#disclaimer)

<!-- /TOC -->

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
$query = $this->Users->jsonQuery($query);
```
All existent query options will be cloned into the new `JsonQuery`.

`JsonQuery` extends core `Query` and all core methods are available, plus three specific json chainable functions.
```php
// In your controller
$query = $this->Users
  ->find('json')
  ->jsonSelect([
    'prefs.theme@attributes'
  ])
  ->jsonWhere([
    'username@attributes' => 'user'
  ])
  ->jsonOrder([
    'created@attributes' => 'DESC'
  ])
  ->all();
```

Alternatively, you can provide parameters to the `find` query :
```php
// In your controller
$query = $this->Users
  ->find('json', [
    'json.fields' => ['prefs.theme@attributes'],
    'json.conditions' => ['username@attributes' => 'user']),
    'json.sort' => ['created@attributes' => 'DESC']
  ->all();
```

#### Selecting datfields
It works exactly in the same manner than the `fields` option or the `select` method.

**Note: you can mix "regular" fields from table with JSON field internal data when using `json.fields` or `jsonSelect`.**

Aliases are fully supported in the same manner as CakePHP does through associative array.

You can use any usual regular options and mix methods with any of the syntaxes.

When using `jsonSelect`, returned field name is aliased like this : `[Model_]field_path`. You can provide a string as second parameter to change default `_` one. A third boolean parameter can be used to force lowercasing of the key when set to `true`.

By setting separator to `false`, the field key (aliased or not) won't be kept flattened but instead used to rebuild an associative array of data :

```php
$this->Users->find('json')->jsonSelect('the.deep.key@attributes', '.')->first()->toArray();
// will return ['attributes.the.deep.key' => 'deepvalue']

// With delimiter set to false
$this->Users->find('json')->jsonSelect('the.deep.key@attributes', false)->first()->toArray();
// will return ['attributes' => ['the' => ['deep' => ['key' => 'deepvalue']]]]

// With dotted alias and delimiter set to false
$this->Users->find('json')->jsonSelect(['my.key' => 'the.deep.key@attributes'], false)->first()->toArray();
// will return ['my' => ['key' => 'deepvalue']]
```

#### filtering datfields
When using `jsonWhere`, you can use any of regular nesting and operator provided as an array. You can also use plain query. In this last case, string values won't be escaped.

**Note**: you can mix "regular" fields from table with JSON field internal data when using `json.conditions` or `jsonWhere`.

```php
// In your controller
$query = $this->Users
  ->find('json')
  ->jsonWhere([ // Classic array way
    'OR' => [
      'username@attributes =' => 'user'
      'prefs.color@attributes LIKE' => '%dark%'
    ]
  ]);

	// Dangerous raw SQL way
  $query = $this->Users
    ->find('json')
		->jsonWhere("username@attributes = 'user' OR prefs.color@attributes LIKE '\"%dark\"'");

	// Query expression way
  $query = $this->Users
    ->find('json')
		->jsonWhere(function($q) {
				return $q->_or(['username@attributes' => 'user'])->like('prefs.color@attributes', '%dark%');
		});
```

#### Sorting datfields
It's exactly the same syntax than `order`|`sort` option or `order` method. If the provided parameter is a string, it will be treated as a default ASC ordering on this field. If the provided parameter is an array of strings, default ASC ordering will also be applied.

**Note**: you can mix "regular" fields from table with JSON field internal data when using `json.sort` or `jsonOrder`.

### Create and update JSON in an entity from model
Since v1.1.0, fields names are filtered before marshalling when using `Model::newEntity` or `Model::patchEntity`.

When using patchEntity, the whole JSON field will be replaced by new value. If you want to only mass update some properties, you can call `jsonMerge` on returned entity.

```php
// In your controller
$user = $this->Users->newEntity([
  'nickname@attributes' => 'Foo'
]);

// Replace field value by {"update":"Bar"}
$user = $this->Users->patchEntity([
  'update@attributes' => 'Bar'
]);

// Update/create attributes field value
$user = $this->Users->patchEntity([
  'update@attributes' => 'Bar'
])->jsonMerge();
```

### Use JSON setter/getter methods with entities
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
