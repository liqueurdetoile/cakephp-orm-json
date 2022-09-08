[![Latest Stable Version](https://img.shields.io/github/release/liqueurdetoile/cakephp-orm-json.svg?style=flat-square)](https://packagist.org/packages/liqueurdetoile/cakephp-orm-json)
![2.x-next status](https://github.com/liqueurdetoile/cakephp-orm-json/actions/workflows/ci.yml/badge.svg?branch=2.x-next)
[![Coverage Status](https://coveralls.io/repos/github/liqueurdetoile/cakephp-orm-json/badge.svg?branch=master)](https://coveralls.io/github/liqueurdetoile/cakephp-orm-json?branch=master)
[![license](https://img.shields.io/github/license/liqueurdetoile/cakephp-orm-json.svg?style=flat-square)](https://packagist.org/packages/liqueurdetoile/cakephp-orm-json)

# Cake-orm-json plugin

**This branch is for CakePHP >=4.3.5. It supports PHP ^7.2|^8.0**
**For previous CakePHP versions, please use v1 of this plugin**

This plugin extends usual CakePHP ORM operations with JSON fields. It embeds a special [datfield notation](#datfield-format) that allow to easily target a path into a JSON field data. With it, you can :

- select, order and filter queries : `$q = $table->find('json')->where(['jfield->darn.deep.key' => true])->all()`
- apply data types inside JSON data
- easily access, mutate and delete JSON data in entity : `$e->get('jfield->darn.deep.key')`
- use JSON data as foreign keys for associations (quite extreme indeed and not really recommended but it can be useful at margin)

**Relational databases are not primarily designed** to handle non-schemed data and using JSON data fields can issue really bad performances. Nevertheless the newest releases of engines have also show significant improvements in dealing with JSON data and raising of NoSQL has created different needs and constraints.

**Caution : As with version 2.0.0, it only works with Mysql databases >= 5.7.8. Setup is done to allow use of this plugin with other engines and I hope to release it at least for MariaDB, SQLite and PostgreSQL. Any help would be very appreciated though :smile**

## Installation

### Install plugin
You can install the latest version of this plugin into your CakePHP application using [composer](http://getcomposer.org).

```bash
composer require liqueurdetoile/cakephp-orm-json
```

The base namespace of the plugin is `Lqdt\OrmJson`.

### Recommended setup
This plugin is working by cloning the used connection in order to upgrade its driver and insert a translation step that will allow to parse datfield notation into a suitable form that can then be used by cakePHP ORM. Obviously, adding this layer if not using datfield notation is pretty useless.

There's many ways to setup the plugin in order to optimize things but we recommend this way as it will fit most of use cases :
- Add `DatFieldBehavior` that have JSON fields without upgrading table connection, and add `DatFieldTrait` to their corresponding entities;
- Add `DatFieldAwareTrait` to models without JSON fields but which uses associations relying on datfield foreign keys;
- Always call `find('datfields')` or `find('json')` when querying if using datfield notation to ensure that translation is enabled.

Keep in my mind that you keep full control on using regular or upgraded connection. If you have some performance issues with this setup, please check [advanced setup](#advanced_setup) for more informations.

#### Embeds `DatFieldAwareTrait` in models
Usually, you will use this trait in models that needs to be linked to another model with a foreign key living in JSON data. The trait allows you to link models based on datfield foreign key(s) and to easily switch between regular or upgraded connection.

```php
<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Lqdt\OrmJson\ORM\DatFieldAwareTrait;

class UsersTable extends Table {
  use DatFieldAwareTrait;

  // your model stuff
}
?>
```
#### Embeds `DatFieldBehavior` in models
Behavior brings up all of the convenience of [`DatFieldAwareTrait`](#embeds-datfieldawaretrait-in-models) and takes care of marshaling datfield notation when creating/patching entities. The behavior is targetted to models which contains JSON fields. It can also be used to store permanent JSON data types when marshaling or persisting data.

```php
<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class UsersTable extends Table
{
    public function initialize(array $config): void
    {
        $this->addBehavior('Lqdt/OrmJson.DatField');
    }
}
?>
```
You can pass `['upgrade' => true]` as behavior config options to request an immediate connection upgrade for the model.

The behavior can be used without the [entity trait](#use-datfieldtrait-with-entities) and vice-versa.

#### Enbeds `DatFieldTrait` with entities
Datfield trait brings up tools to access and manipulate with ease the content of JSON fields. Obviously, it's only useful with entities that contain JSON fieds.

```php
namespace App\Model\Entity;

use Cake\ORM\Entity;
use Lqdt\OrmJson\Model\Entity\DatFieldTrait;

class User extends Entity
{
    use DatFieldTrait;
}
```

The trait can be used without DatFieldAwareTrait or DatFieldBehavior in modelds and vice-versa.

## Datfield format
In order to work with inner JSON data, we need to know which field to use and which path to use in this field. You can, obviously use SQL fragments and/or native Mysql JSON functions but, believe me, it's very prone to error, needs securing user input, and, well, why using an ORM if we have to write raw SQL each time ?

This plugin leverage this difficulty by providing quickier syntax to target JSON data. In fact, this version brings up two ways and you can choose or mix which one will best suit your way.

For instance, let's say you have a JSON field named `position`, exposing two keys `lat` and `lon` in a `Locations` model.

This plugin introduced the `datfield` format (contraction of `dot` and `at field`) whick looks like : <tt>path@[Model.]field</tt> and can be used in the same way regular fields are used. As usual, `Model` part is optional if no name conflict may occurs.

Since v2, this plugin also supports a more *object* way which looks like : <tt>[Model.]field->path</tt>

For query operations or with special entity getters/setters, You may consider using `'lat@position'` or `'position->lat'` to easily manipulate and access the `lat` data in the `position` field.

Datfields become especially handy when accessing deep nested keys : `'lastknown.computed.lat@position'` (or `'position->lastknown.computed.lat'`) will target `'position['lastknown']['computed']['lat']'` value in data.

It also partially supports JSON path with (as now) the [syntax used by Mysql](https://dev.mysql.com/doc/refman/8.0/en/json.html#json-path-syntax) itself to target arrays : <tt>[Model.]field->path[*].prop</tt> will target the `prop` key of all items stored in the array at `path`.

## Usage

### Quick guide
DatField notation can be used in any statements involving named fields :

```php
// Assuming $table has DatFieldBehavior attached and its entity has DatFieldTrait attached
$customers = $table->find('json')
  // You can mix v1 and v2 syntax as will
  ->select(['id', 'attributes', 'name' => 'attributes->id.person.name'])
  ->where(['attributes->id.person.age >' => 40])
  ->order(['attributes->id.person.age'])
  ->all();

// Change the manager for all this customers
$customers = $table->patchEntities($customers, ['attributes->manager.id' => 153]);

// Update status
foreach ($customers as $customer) {
    $total = $customer->get('attributes->command.last.total');
    $stingy = $total < 50;
    $customer->set('attributes->status.stingy', $stingy);
    // You can also use array-like syntax
    $customer->{'attributes->status.vip'} = $total > 500;
    // You can also use curly syntax
    $customer->{'attributes->status.tobeCalled'} = !$stingy;
}

$table->saveMany($customers);
```

**In short** : just target and use JSON data as you would do it with regular fields throug datfield notation. If you know some troubles, feel free to open an issue as needed.

Read further for more advanced usage.

### Selecting fields
You can easily select specific paths in your data among paths with a regular select statement. Without alias, it will creates a composite key from datfield `<field><underscore><path with dot replaced by underscore>` :

```php
$e = $table->find()->select(['id', 'attributes->deep.nested.key'])->first();

/** Entity data will look like
* [
*   'id' => 0,
*   'attributes_deep_nested_key' => true
* ]
**/
```

You can also use field alias to control key in result :

```php
$e = $table->find()->select(['id', 'key' => 'attributes->deep.nested.key'])->first();

/** Entity data will look like
* [
*   'id' => 0,
*   'key' => true,
* ]
**/
```

`enableAutoFields` will work very fine to expose some data while also loading all other fields :

```php
$e = $table->find()->select('key' => 'attributes->deep.nested.key'])->enableAutoFields()->first();

/** Entity data will look like
* [
*   'id' => 0,
*   'key' => true,
*   'attributes' => [
*     'deep' => [
*       'nested' => [
*         'key' => true
*       ]
*     ]
*   ]
* ]
**/
```

### Filtering and ordering data
Filtering or ordering with datfields can be done like on any other fields and by any usual means of the ORM. Expressions will be automatically translated to usable ones in SQL. You can use datfields at any place (no really).

```php
// Simple search using v2 datfield notation
$data = $table->find()->where(['attributes->key' => 'key'])->first();
$data = $table->find()->where(['attributes->really.deep.number >=' => 10])->first();
$data = $table->find()->where(['attributes->key LIKE' => '%key%', 'attributes->really.deep.nested.key' => 'deep.nested.key'])->first();

// Looking for null will return all fields where key is missing or equals to null as default behavior
$data = $table->find()->where(['attributes->fool IS' => null])->first();

// Query builder is also fine
$data = $table
  ->find()
  ->where(function($exp) {
	   return $exp->between('attributes->lastkwown.position.lat', 2.257, 2.260);
   })
   ->first();
```

When filtering on null values, default behavior is to consider that any record that don't have the target path in its JSON field also fulfills `IS NULL` condition. To avoid this, you can pass `['ignoreMissingPath' => true]` as optiont of your query to target only records that have the path in their JSON field with a value set to `null`.


There's some caveats with data types not natively supported by JSON format, like datetimes, but it can be handled by using [JSON data types](using-json-data-types).

### Using aggregation and functions
Datfield are also fully supported and can be used as any regular fields.

```php
$q = $this->table->find();
$res = $q
  ->select(['kind' => 'data->group', 'total' => 'data->i + data->j', 'count' => $q->func()->count('*')])
	->group('kind')
	->having(['total >' => 10])
  ->distinct()
	->all();
```

### Marshaling data

**Note :** Marshaling datfields does not require to upgrade connection

In some cases, you may want to use datfield notation in data provided to `createEntity` or `patchEntity` methods and there's no trouble in doing so :

```php
$e = $table->createEntity(['data->key' => 'foo', 'data->really.deep.key' => 'not annoying']);

// $e will looks like
[
  'id' => null,
  'data' => [
    'key' => 'foo',
    'really' => [
      'deep' => [
        'key' => 'not annoying' // maybe yes if having to type arrays
      ]
    ]
  ]
]
```

When patching entities, the *default* behavior is to consider that the **whole** JSON structure is provided in data. Therefore, all previous data is lost and gone. To avoid this, you can either pass `jsonMerge` as `true` in `patchEntity` options or call `jsonMerge` on the resulting entity (if using `DatFieldTrait`) or through table :

```php
// Keep our previously created entity and patch it
$e = $table->patchEntity(['data->hacked' => true);

// $e will looks like
[
  'id' => null,
  'data' => [
    'hacked' => true,
  ]
]

// Damnit, let's restore lost data
$e->jsonMerge();
// or
$table->jsonMerge($e);

// $e will now looks like
[
  'id' => null,
  'data' => [
    'hacked' => true, // Not anymore
    'key' => 'foo',
    'really' => [
      'deep' => [
        'key' => 'not annoying' // maybe yes if using arrays
      ]
    ]
  ]
]

// Next time, just add option
$e = $table->patchEntity(['data->hacked' => true, ['jsonMerge' => true]);
```

You can fine tune which field **should be merged** by passing an array of the JSON fields name to `jsonMerge` option or method : `['data']` for instance.

### What brings `DatFieldTrait` within entities ?

**Note :** Using this trait does not require to upgrade connection nor adding `DatFieldAwareTrait` or `DatFieldBehavior` to model.

All regular methods are replaced when an entity used this trait to support datfield notation. Their behavior remains the same and they can obviously still be used for any regular field(s).

To get/set data with datfield, simply use `get` or `set`, array-like syntax or [curly syntax]((https://www.php.net/manual/en/language.types.string.php#language.types.string.parsing.complex)) :

```php
$e->get('attributes->deep.nested.value');
$e->get('deep.nested.value@attributes'); // both notations are supported
$e['attributes->deep.nested.value'];
$e['attributes->deep.nested.value'];
$e->{'attributes->deep.nested.value'};
$e->{'deep.nested.value@attributes'};
```

[Dirty state](https://book.cakephp.org/4/en/orm/entities.html#checking-if-an-entity-has-been-modified) is available at property level and field level :

```php
$e->set('attributes->deep.nested.value', 'foo');
$e->isDirty('attributes->deep.nested.value'); // true
$e->isDirty('attributes->deep.nested.othervalue'); // false
$e->isDirty('attributes'); // true
$e->isDirty(); // true
```

**Note :** If you call `setDirty('attributes', false)`, all currently dirty paths in`attributes` will cleared as well.

### Using JSON data types

There's some caveats when dealing with data types inside JSON. By itself JSON type handles natively null and usual scalar types : boolean, integer, float or string, plus arrays and objects of previous. Troubles may begin when you want to handle other types stored in JSON and the perfect example is datetime.

Usually, datetime/time/date/timestamp fields are mapped to a `FrozenTime` object in cakePHP and a [registered type](https://book.cakephp.org/4/en/orm/database-basics.html#datetime-type) takes care of handling needed castings. Most of the time, this type is inferred from reflected schema and it's working out of the box.

If a datetime is nested in some JSON data, it can't work like this as it is merely a string. When dealing with some usual string representations of datetimes, like Mysql one, ISO8601 or timestamps, it can be absolutely fine to simply do nothing as ordering will still work.

Nevertheless, you miss all the convenience that brings datetime data type for manipulating values. Moreover, if you have some nasty formats, queries may lead to wrong results. Due to JSON versatility, many APIs make use of custom string formats and it can be tricky to handle them.

To ease troubleshooting these things, `DatFieldBehavior`allow to define JSON data types permanently and/or per query. Because of JSON versatility, it extends regular typemaps by allowing the use of callbacks to cast data instead of multiplicating data types.

### Using JSON data types

**Note :** Connection **must** be upgraded in order to support JSON data type.

JSON data types are stored within and upgraded schema alongside regular fields. Therefore you will get errors if not upgrading connection before setting them up.

When registering JSON data type, you can either only provide a regular data type as string or an extended one to register callbacks for one or more of casting operations between :
- `marshal`: Callback will be called when marshaling data.
- `toPHP`: Callback will be called when processing fetched data
- `toDatabase`: Callback will be called when persisting data


All callbacks will receive the target value as first argument, the whole data as second argument and the query (if available in operation) as third argument.

If a callback is provided for a given operation (`marshal`, `toPHP` or `toDatabase`) alongside a regular data type, only callback will be applied to data. This way, you can override given data type operations instead of creating a new one.

#### Registering JSON data type permanently
When using `DatfieldBehavior`, you can easily and permanently register JSON types that will persist through each queries. :

```php
// Register a single datfield as datetime type
$table->getSchema()->setJsonTypes('data->time', 'datetime');

// Register a single datfield as date and overrides marshal hook with a callback
$table->getSchema()->setJsonTypes('data->frenchDateFormat', [
	'type' => 'date',
	'marshal' => function(string $value): FrozenDate {
		return FrozenDate::createFromFormat('d/m/Y', $value);
	}
]);

// Register many datfields as datetime type
$table->getSchema()->setJsonTypes([
	'data->time' => 'datetime',
	'date->anothertime' => 'datetime'
]);

// Register multiple datfields with full syntax
$table->getSchema()->setJsonTypes([
	'data->time' => [
		'type' => 'datetime',
		'marshal' => array($table, 'marshalTime'), // overrides datetime type marshal operation
	],
	'data->weirdthing' => [ // providing a type is not mandatory
		'marshal' => array($table, 'importWeirdthing'),
		'toPHP' => array($table, 'weirdthingToPHP'),
		'toDatabase' => array($table, 'weirdthingToDatabase'),
	],
]);
```

Please note that all JSON data types will be lost if connection is downgraded as regular schema will be restored.

#### Registering JSON data types for a single query
You can also register JSON data type per query by providing a `jsonTypeMap` option. In case of conflict, it overrides any JSON data type stored in the model.

```php
$q = $table->find('all', ['jsonTypeMap' => ['data->time' => 'datetime']])->all();
```

You can as well provides callback by using full syntax.

### Linking models together
**Special upgraded associations are available both in `DatFieldAwareTrait` and `DatFieldBehavior`**

The plugin allows to use datfield notation to reference a foreignKey and links tables on this basis. It will not be as efficient as regular foreign keys that will indexed but it can be handy in some edge cases.

In order to use datfield as foreign key, simply use datfield counterpart of any association and use dafield notation for foreign key option and/or targetFoeignKey option :

```php
$Clients->datFieldHasOne('Agents', ['foreignKey' => 'data->agent_id']);
$Clients->datFieldBelongsToMany('Products', [
  'foreignKey' => 'data->client_id'
  'targetForeignKey' => 'data->product_id'
]);
```

The full list :
- `belongsTo` <=> `datFieldBelongsToMany`
- `hasOne` <=> `datFieldHasOne`
- `belongsToMany` <=> `datFieldBelongsToMany`
- `belongsToManyThrough` <=> `datFieldBelongsToManyThrough`

**Note :** In MySQL, you can also use [virtual columns](https://vladmihalcea.com/index-json-columns-mysql/) to index JSON data.

## Advanced setup
This plugin contains :
- `Lqdt\OrmJson\Database\Driver\DatFieldMysql` driver: The driver will traverse all query parts in order to translate datFields in clauses to their MySQL counterpart, usually JSON_EXTRACT. Traversal can be disabled at runtime by providing `[useDatFields => false]` in query options;
- `Lqdt\OrmJson\ORM\DatFieldAwareTrait`: The trait is providing convenient methods to upgrade/downgrade connection driver and table schema at will and brings up special associations to allow linking models on JSON data;
- `Lqdt\OrmJson\Model\Table\DatFieldBehavior`: The behavior does exactly the same thing that `DatFieldAwareTrait`, plus handling marshalling with datfields when using `newEntity`, `patchEntity` or their plural counterparts;
- `Lqdt\OrmJson\Model\Entity\DatFieldTrait`: The trait overrides all regular accessors, mutators and utilities to handle datfield notation within entities while keeping full compatibility for regular fields.

Depending on what you're aiming for, you have different alternatives when using this plugin.
- **Use datfield notation and/or JSON data types to query database or persist data** : You must ensure that the model(s) that will rely on datfield notation for querying are using the upgraded connection;
- **Use datfield notation when patching data** : You must have embedded `DatFieldBehavior` in the model;
- **Use datfield notation to manipulate data in entities**: You must have added `DatFieldTrait` to related entities classes;
- **Use datfield notation to link models** : You must use the special associations methods provided by `DatFieldAwareTrait` or `DatFieldBehavior`;

It's up to you to find the right balance based on your needs between the connection upgrade step overhead and the datfield translation step overhead.

#### Use the upgraded driver for all models
Obviously, you can simply use upgraded driver in your [connection configuration](https://book.cakephp.org/4/en/orm/database-basics.html#database-configuration). This can be a real good option if all of your models will mostly use datfield notation. You can still disable datfield translation by providing `['useDatFields' => false]` as query option to avoid useless translation process when not using datfields.

```php
// Assuming that DatFieldAwareTrait or DatFieldBehavior are set in UsersTable
$user = $this->Users
  ->find() // Special finder is not required here as driver is already upgraded
  ->where(['attributes->phones.tel' => 'wathever_number'])
  ->first();

$users = $this->Users
  ->find('all', ['useDatFields' => false]) // Disable translation overhead as not needed in this query
  ->all();
```

#### Enable or disable upgraded driver per model
With addition of `DatFieldAwareTrait` or `DatFieldBehavior` to a model, you can enable/disable upgraded connection at runtime by using `Model::useDatFields()`/`Model::useDatFields(false)`. If you want to permanently use upgraded connection in the model, simply call `Model::useDatFields()` in the `initialize` hook or add the behavior with `['upgrade' => true]` as option. You can still disable datfield translation per query by providing `['useDatFields' => false]` as query option.

```php
// Assuming that DatFieldAwareTrait or DatFieldBehavior are set in UsersTable
// Connection is not already upgraded
$user1 = $this->Users
  ->useDatFields() // returns model instance, so it's chainable
  ->find() // Special finder is not required here as driver is already upgraded
  ->where(['attributes->phones.tel' => 'wathever_number1'])
  ->first();

$user2 = $this->Users
  ->find()
  ->where(['attributes->phones.tel' => 'wathever_number2'])
  ->first();

$user2 = $this->Users
  ->find()
  ->where(['attributes->phones.tel' => 'wathever_number3'])
  ->first();

$users = $this->Users
  ->find('all', ['useDatFields' => false]) // Disable translation overhead as not needed in this query
  ->all();

// Restore genuine driver
$this->Users->useDatFields(false);
```

**Caution** : As model instances are stored as singleton in a registry, I do recommend to cut off upgraded driver after all datfield queries are settled.

#### Use upgraded driver per query
It's probably the most usual case as datfield queries will mostly be occasional. With addition of `DatFieldAwareTrait` or `DatFieldBehavior` to a model, simply call find('datfields') or find('json') and the query will be provided with an upgraded connection though model connection remains genuine. You cannot use permanent JSON data types this way but still can provide `jsonTypeMap` option in query.

```php
// Assuming that DatFieldAwareTrait or DatFieldBehavior are set in UsersTable
// We're in a controller that is loading Users model and connection is not already upgraded
// We request connection upgrade as datfields will be used in query
$user = $this->Users
  ->find('datfields') // or ->find('json')
  ->where(['attributes->phones.tel' => 'wathever_number'])
  ->first();
```

#### Some tricky things to know
Lastly, you may face some issues with nested queries, when joining data. If doing a single query, CakePHP will logically populate query connection with the driver of the **root** model. In contrary, when launching subqueries, connection configuration of dependent models wii be used.

For instance, say you have a `Vehicles` model that has many `Locations` model. Upgraded driver is permanently used in `Locations` but not in `Vehicles`.

```php
// This will work fine because 2 databases requests will be made, 1 per model with respective connection setup
$this->Vehicles->find()->contain(['Locations'])->all();

// This will fail because only 1 request with an INNER JOIN will be done from `Locations` not upgraded connection
$this->Vehicles->find()->innerJoinWith('Locations', function($q) {
  return $q->where(['Locations.attributes->position->lat <' => 45.6]);
})->all();

// This will work because we're upgrading connection on Vehicles with `datfields` custom finder
// Vehicles model must at least use `DatFieldAwareTrait`
$this->Vehicles->find('datfields')->innerJoinWith('Locations', function($q) {
  return $q->where(['Locations.attributes->position->lat <' => 45.6]);
})->all();
```

If you begin to have Mysql syntax errors with unparsed datfields, it means that you have some datfields used in a not upgraded connection.

### API reference
See [API reference](https://liqueurdetoile.github.io/cakephp-orm-json/api)

## Difference from v1.x
In previous versions, we've tried to convert clauses within Query by dedicating the JsonQuery that extends it to bring up functionnalities. It worked very well but it was still limited to Query overrides and support for other engines was clearly impossible.

From version 2.0.0, translation is done at MySQL driver level. The behavior now creates an upgraded connection with the new driver that is able to translate any datfield notation before native CakePHP ORM processing.

CakePHP makers are great guys because they meant to plan many overrides that makes this plugin feasible.

Version 2.x is a quite a breaking change from 1.x as JsonQuery is not needed and available anymore. Similarly, you don't need any `jsonXXX` methods on entities. Regular mutators, accessors and magic properties will work well with datfields.

**Migrating is quite simple though**, simply stick to regular query statements and use `find`, `select`, `order`, `where` instead of previous ones `jsonQuery`, `jsonSelect`, `jsonOrder`, `jsonWhere`. In entities, use regular accessors and mutators to cope with data in JSON.

## What's next ?
It would be great to extends support to other SQL engines. I'm also

## Changelog
**v2.0.0**
- *BREAKING CHANGE* : Replace JsonQuery logic by a dedicated database driver that handles seamlessly the parsing of dat fields
- *BREAKING CHANGE* : Replace JsonXXX entity methods and use regular accessors and mutators
- Add v2 datfield notation support `'[Model.]field->path'`
- Completely rework and optimize query translations of datfield syntax
- Fully rework `DatFieldBehavior` and `DatFieldTrait` for entities
- Migrate CI to Github Actions
- Upgrade test environment
- Add a bunch of tests for a wide variety of situations

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
