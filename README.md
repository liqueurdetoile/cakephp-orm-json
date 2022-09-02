[![Latest Stable Version](https://img.shields.io/github/release/liqueurdetoile/cakephp-orm-json.svg?style=flat-square)](https://packagist.org/packages/liqueurdetoile/cakephp-orm-json)
[![license](https://img.shields.io/github/license/liqueurdetoile/cakephp-orm-json.svg?style=flat-square)](https://packagist.org/packages/liqueurdetoile/cakephp-orm-json)

# Cake-orm-json plugin

**This branch is for CakePHP ^4.3 and has heavy breaking changes from 1.x. It supports PHP ^7.1 or ^8.0**

This plugin adds support to perform usual CakePHP ORM operations on JSON fields. It embeds [datfield notation](#datfield-format) within CakePHP ORM and allows :

- to select, order and filter queries while targetting JSON data : `$q = $table->find()->where(['jfield->darn.deep.key' => true])->all()`
- to apply data types inside JSON data
- to easily access, mutate and delete JSON data in entity : `$e->get('jfield->darn.deep.key')`
- to use JSON data as foreign keys for associations (quite extreme indeed but it can sometimes be useful)

**Relational databases are not primarily designed** to handle non-schemed data and using JSON data fields can issue really bad performances. Nevertheless the newest releases of engines have also show significant improvements in dealing with JSON data and raising of NoSQL has created different needs and constraints.

**Caution : As with version 2.0.0, it only works with Mysql databases since 5.7.8 and it should work with MariaDB since 10.2.7, though I've not tested it yet. Set up is done to allow use of this plugin with any engine and I hope to release it at least for SQLite and PostgreSQL. Any help would be very appreciated anyway :smile**

<!-- TOC depthFrom:1 depthTo:6 withLinks:1 updateOnSave:1 orderedList:0 -->
- [Cake-orm-json plugin](#cake-orm-json-plugin)
	- [Installation](#installation)
		- [Install plugin](#install-plugin)
		- [Basic setup](#basic-setup)
			- [Use `DatFieldAwareTrait`](#use-datfieldawaretrait)
			- [Use `DatFieldBehavior`](#use-datfieldbehavior)
			- [Use `DatFieldTrait` with entities](#use-datfieldtrait-with-entities)
		- [Advanced setup](#advanced-setup)
			- [Permanently use the upgraded driver](#permanently-use-the-upgraded-driver)
			- [Use upgraded driver at runtime per query](#use-upgraded-driver-at-runtime-per-query)
			- [Enable or disable upgraded driver in model](#enable-or-disable-upgraded-driver-in-model)
			- [Some tricky things to know](#some-tricky-things-to-know)
			- [A note about connection upgrade and autoquoting of identifiers](#a-note-about-connection-upgrade-and-autoquoting-of-identifiers)
	- [Datfield format](#datfield-format)
	- [Usage](#usage)
		- [Basic usage](#basic-usage)
		- [Querying data with datfield notation](#querying-data-with-datfield-notation)
			- [Selecting fields](#selecting-fields)
			- [Finder options](#finder-options)
		- [Table/finder options](#tablefinder-options)
		- [Patching entities (behavior needed)](#patching-entities-behavior-needed)
		- [Filtering data (behavior needed)](#filtering-data-behavior-needed)
		- [Using aggregation and Mysql functions](#using-aggregation-and-mysql-functions)
		- [Use `DatFieldTrait` within entities](#use-datfieldtrait-within-entities)
			- [Accessors](#accessors)
			- [Mutators](#mutators)
			- [Property guard](#property-guard)
			- [Dirty state](#dirty-state)
		- [Linking models together](#linking-models-together)
		- [API reference](#api-reference)
	- [Migrating from v1.x](#migrating-from-v1x)
	- [Changelog](#changelog)
	- [Disclaimer](#disclaimer)

<!-- /TOC -->

## Installation

### Install plugin
You can install the latest version of this plugin into your CakePHP application using [composer](http://getcomposer.org).

```bash
composer require liqueurdetoile/cakephp-orm-json
```

The base namespace of the plugin is `Lqdt\OrmJson`.

### Basic setup
We recommend the following setup that will fit most of use cases :
- Add `DatFieldBehavior` to models that have JSON fields and `DatFieldTrait` to their corresponding entities;
- Add `DatFieldAwareTrait` to models without JSON fields but which uses associations relying on datfield foreign keys;
- Always call `find('datfields')` or `find('json')` when querying and using datfield notation.

If you have some performance issues with this setup, please check [advanced setup](#advanced_setup) for more informations.

#### Use `DatFieldAwareTrait`
Usually, you will use this trait in models that needs to be linked to another model with a foreign key living in JSON data.

```php
<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Lqdt\OrmJson\ORM\DatFieldAwareTrait;

class UsersTable extends Table {
  use DatFieldAwareTrait;
}
?>
```
The trait will add these methods to your model :

Method  | Description
--|--
`UsersTable::datFieldBelongsTo`  |  Associates a model with [BelongsTo](https://book.cakephp.org/4/en/orm/associations.html) and datfield notation as foreign key
`UsersTable::datFieldHasOne`  |  Associates a model with [HasOne](https://book.cakephp.org/4/en/orm/associations.html) and datfield notation as foreign key
`UsersTable::datFieldhasMany`  |  Associates a model with [HasMany](https://book.cakephp.org/4/en/orm/associations.html) and datfield notation as foreign key
`UsersTable::datFieldBelongsToMany`  |  Associates a model with [BelongsToMany](https://book.cakephp.org/4/en/orm/associations.html) and datfield notation as foreign key and/or target foreign key
`UsersTable::getUpgradedConnectionForDatFields`  |  Process a connection and returns a cloned one with its driver upgraded
`UsersTable::useDatFields` | Enable/disable driver upgrade
`UsersTable::findDatfields`  | [Custom finder](https://book.cakephp.org/4/en/orm/retrieving-data-and-resultsets.html#custom-finder-methods) that enable driver upgrade when used in a query
`UsersTable::findJson`  | Alias for `UsersTable::findDatfields`

#### Use `DatFieldBehavior`
Behavior brings up all of the methods of [`DatFieldAwareTrait`](#use-datfieldawaretrait) and takes care of automatically marshaling datfield notation when patching entities. Usually, you will attach the behavior to models which contains JSON fields. It can also be used to use CakePHP data types or apply callbacks for targetted JSON keys when marshaling/persisting data.

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
You can pass `['upgrade' => true]` as behavior config options to request an immediate and permanent connection upgrade.

The behavior can be used without the [entity trait](#use-datfieldtrait-with-entities) and vice-versa.

#### Use `DatFieldTrait` with entities
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

The trait can be used without the behavior and vice-versa.

### Advanced setup
This plugin contains :
- `Lqdt\OrmJson\Database\Driver\DatFieldMysql` driver: The driver will traverse all query parts in order to translate datFields in clauses to their MySQL counterpart, usually JSON_EXTRACT. Traversal can be disabled at runtime by providing `[useDatFields => false]` in query options;
- `Lqdt\OrmJson\ORM\DatFieldAwareTrait`: The trait is providing convenient methods to upgrade/downgrade connection driver at will and brings up special associations to allow linking models on JSON data;
- `Lqdt\OrmJson\Model\Table\DatFieldBehavior`: The behavior does exactly the same thing that `DatFieldAwareTrait`, plus handling marshalling with datfields when using `newEntity`, `patchEntity` or their plural counterparts. It also handles casting inner JSON data when marshaling or persisting data;
- `Lqdt\OrmJson\Model\Entity\DatFieldTrait`: The trait overrides all accessors and mutators to handle datfield notation within entities while keeping full compatibility for regular fields.

Depending on what you're aiming for, you have different alternatives when using this plugin.
- **Use datfield notation to query database** : You must ensure that the model(s) that will rely on datfield notation for querying are using the upgraded driver. See [use the upgraded driver](#use-the-upgraded-driver) for details;
- **Use datfield notation to link models** : You must use the special associations methods provided by `DatFieldAwareTrait` or `DatFieldBehavior`;
- **Use datfield notation when patching data** : You must have embedded `DatFieldBehavior` in the model;
- **Use data types with inner JSON data** : You must have embedded `DatFieldBehavior` in the model;
- **Use datfield notation to manipulate data in entities**: You must have added `DatFieldTrait` to related entities classes.

There's different available setups in order to find the right balance between the connection upgrade step overhead and the datfield translation step overhead :

#### Use the upgraded driver for all models
Obviously, you can simply use upgraded driver in your [connection configuration](https://book.cakephp.org/4/en/orm/database-basics.html#database-configuration). This can be a real good option if all of your models will mostly use datfield notation. You can still disable datfield translation by providing `['useDatFields' => false]` as query option.

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
It's probably the most usual case as datfield queries will mostly be occasional. With addition of `DatFieldAwareTrait` or `DatFieldBehavior` to a model, simply call find('datfields') or find('json') and the query will be provided with an upgraded connection though model connection remains genuine.

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
Lastly, you may face some issues with nested queries, when adding `contain` or `joins` or instance on a table. If doing a single query, CakePHP will logically populate query connection with the driver of the **root** model. In contrary, when launching subqueries, connection configuration of dependent models wii be used.

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

If you begin to have Mysql syntax errors with unparsed datfields, it means that your root model needs to use the upgraded driver. **Unless permanently use upgraded driver, my best advice is to always use `find('datfields') or find('json')` on main query if there's some nested datfield notation.**

## Datfield format
In order to work with inner JSON data, we need to know which field to use and which path to use in this field. You can, obviously use SQL fragments and native Mysql JSON functions but, believe me, it's very prone to error, needs securing user input, and, well, why use an ORM if we have to write raw SQL each time ?

This plugin leverage this difficulty by providing a quickier syntax to access and manipulate JSON data. In fact, this version brings up two ways and you can choose or mix which one will best suit your way.

For instance, let's say you have a JSON field named `position`, exposing two keys `lat` and `lon` in a `Locations` model.

In v1, this plugin introduced the `datfield` format (contraction of `dot` and `at field`) whick looks like : <tt>path@[Model.]field</tt> and can be used in the same way regular fields are used. As usual, `Model` part is optional if no name conflict may occurs.

Since v2, this plugin also supports a more *object* way which looks like : <tt>[Model.]field->path</tt>

For query operations or with special entity getters/setters, You may consider using `'lat@position'` or `'position->lat'` to easily manipulate and access the `lat` data in the `position` field.

Datfields become especially handy when accessing deep nested keys : `'lastknown.computed.lat@position'` (or `'position->lastknown.computed.lat'`) will target `'position['lastknown']['computed']['lat']'` value in data.

## Usage

### Basic usage
DatField notation can be used in any statements involving fields :

```php
// Assuming $table has DatFieldBehavior attached and its entity has DatFieldTrait attached
$customers = $table->find('datfields')
  // You can mix v1 and v2 syntax as will
  ->select(['id', 'attributes', 'name' => 'attributes->id.person.name'])
  ->where(['attributes->id.person.age >' => 40])
  ->order(['attributes->id.person.age'])
  ->all();

// Change the manager for all this customers
$customers = $table->patchEntities($customers, ['attributes->manager.id' => 153]);

// Update status
foreach ($customers as $customer) {
    $stingy = $customer->get('attributes->command.last.total') < 50;
    $customer->set('attributes->status.stingy', $stingy);
    // You can also use curly syntax
    $customer->{'attributes->status.tobeCalled'}, !$stingy);
}

$table->saveMany($customers);
```
Just give it a try or read further for more detailed explanations. If you know some troubles, feel free to open an issue as needed.

### Handling special datatypes like datetimes within JSON data
There's some caveats when dealing with data types inside JSON. By itself JSON type handles natively null and usual scalar types : boolean, integer, float or string, plus arrays and objects of previous types. Troubles may begin when you want to handle other types stored in JSON and the perfect example is datetimes.

Usually, datetime/time/date/timestamp fields are mapped to a `FrozenTime` object in cakePHP and a [registered type](https://book.cakephp.org/4/en/orm/database-basics.html#datetime-type) takes care of handling needed castings. Most of the time, this type is inferred from reflected schema and it's working out of the box.

If a datetime is nested in some JSON data, it can't work like this. When dealing with some usual string representations of datetimes, like Mysql one, ISO8601 or timestamps, it can be absolutely fine to simply handle it as string comparisons beacause they will match datetime comparisons results. Nevertheless, you miss all the convenience that brings datetime data type for manipulating values. Moreover, if you have some nasty formats, queries may lead to wrong results. Due to JSON versatility, many APIs make use of custom string formats and it can be tricky to handle them.

To ease troubleshooting these things, `DatFieldBehavior`allow to define JSON types permanently and/or per query. Because of JSON versatility, it extends regular typemaps by allowing the use of callbacks to cast data instead of multiplicating data types. If a callback is provided for a given operation (`marshal`, `toPHP`, `toDatabase`) alongside a data type, only callback will be applied to data. This way, you can override given data type operations instead of creating a new one.

#### Registering JSON types
When registering JSON, you either only provide a data type and/or register callbacks for one or more of casting operations between :
- `marshal`: Callback will be called when marshaling data
- `toPHP`: Callback will be called when processing fetched data
- `toDatabase`: Callback will be called when persisting data

When using `DatfieldBehavior`, you can easily and permanently register JSON types :

```php
// Assuming $table has DatFieldBehavior attached

// Register a single datfield as datetime type
$table->addJsonTypes('data->time', 'datetime');

// Register a single datfield as date and overrides marshal hook with a callback
$table->addJsonTypes('data->frenchDateFormat', [
	'type' => 'date',
	'marshal' => function(string $value): FrozenDate {
		return FrozenDate::createFromFormat('d/m/Y', $value);
	}
]);

// Register many datfields as datetime type
$table->addJsonTypes([
	'data->time' => 'datetime',
	'date->anothertime' => 'datetime'
]);

// Register multiple datfields with full syntax
$table->addJsonTypes([
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

#### Marshaling data



#### Fetching data

#### Selecting data
SELECT * FROM dsjkld WHERE STR_TO_DATE(attributes->>"$.time", '%d') < NOW();

#### Filtering, ordering or grouping data


#### Persisting data



Let's take current cases :
- When marshalling from raw string data, string datetime will be kept as is;
- When persisting, FrozenTime object will be cast to an ISO8601 string. String comparisons will be safe on this format, so you can filter or order data;
- When fetching from database, string date will be kept as is and you must manually convert it to FrozenTime if needed;
- When ordering, filtering or grouping data on datetime values, all is done as string comparison which will not necessarily work out of the box given the string representation of datetime.

Obviously, you loose all gain

With datetime in JSON, you can't do that and FrozenTime objects are converted to string (which is ISO8601 one as default). On the other side, Mysql is also able to cast or parse datetime from strings.

If nothing is done, date comparison in JSON will be a **string** comparison. It can be fine if using regular Mysql date string format, ISO8601 or timestamps. For some formats, comparison will lead to false results. You may also want to use `FrozenTime` tools to manipulate the date (add 2 monthes for instance).

When using `DatFieldBehavior`, you have two ways for troubleshooting this :
- You can use (CakePHP internals)[https://book.cakephp.org/4/en/orm/database-basics.html#data-types] and register a data type for a given datfield;
- You can register a callback to be executed at `afterMarshal` event or `beforeSave` event.

You can declare them at runtime through query or save options. As regular typemaps, current data in entity stay safe and untouched.

```php
// Let's say that you only want using regular `datetime` data type in this one
use Cake\I18n\FrozenTime

// Let's create and save a user
$user = $this->Users->newEntity(['attributes->last.login' => new FrozenTime()]);
$this->Users->save($user, ['jsonTypes' = ['attributes->last.login' => 'datetime']]);
// Last login will be stored in datetime mysql format

// Lets load it
$user = $this->Users->get('0', ['jsonTypes' = ['attributes->last.login' => 'datetime']]);
$user->{'attributes->last.login'} instance of FrozenTime; // true
```

```php
// Let's say that you need a very special this time
// Let's create and save a user
$this->Users->save($user, ['jsonSaveCallbacks' = ['attributes->last.login' => 'datetime']]);

// Lets load it
$user = $this->Users->get('0', ['jsonTypes' = ['attributes->last.login' => 'datetime']]);
$user->{'attributes->last.login'} instance of FrozenTime; // true
```

### Querying data using datfield notation

#### Selecting fields
You can easily select specific paths in your data among paths with a regular select statement. It simply filters JSON data to only keep selected paths :

```php
$e = $table->find()->select(['id', 'attributes->deep.nested.key'])->first();

/** Entity data will look like
* [
*   'id' => 0,
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

You can also use field alias to expose a path :

```php
$e = $table->find()->select(['id', 'key' => 'attributes->deep.nested.key'])->first();

/** Entity data will look like
* [
*   'id' => 0,
*   'key' => true,
* ]
**/
```

`enableAutoFields` will work very fine to expose some data while loading all data :

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

If using dotted field alias, you can create arbitrary JSON structure to reorganize your data :

```php
$e = $table->find()->select([
  'id',
  'my.very.special.way' => 'deep.nested.key@attributes'
])->first();

/** Entity data will look like
* [
*   'id' => 0,
*   'my' => [
*     'very' => [
*       'special' => [
*         'way' => true
*       ]
*     ]
*   ]
* ]
**/
}
```

When defining a template, you can use `{{model}}` to parse Model name, `{{field}}` to parse field name, `{{path}}` to parse full path and `{{separator}}` to use configured separator. Aliases parsed with template are always lowercased.

```php
  $e = $table->find()->select(['id', 'deep.nested.key@attributes'])->first();
  // $e->attributes_deep_nested_key = 'deep.nested.key'
}
```

You can also use regular aliases to rename extracted fields or tweak separator that will be used :

```php
	// use alias
	$e = $table->find()->select(['id', 'k' => 'deep.nested.key@attributes'])->first();
  // $e->k = 'deep.nested.key'

	// Change separator string
	$table->configureJsonFields(['jsonSeparator' => '.']); // permanently change separator
	$table->configureJsonFields(['jsonSeparator' => '.', 'jsonFields' => ['attributes']]); // permanently change separator only for `attributes` field

	// Change separator for the query only and for all JSON fields
	$e = $table->find(['jsonSeparator' => '.'])->select(['deep.nested.key@attributes'])->first();
	// Change separator for the query only and only for `attributes` JSON field
	$e = $table->find(['jsonSeparator' => '.', 'jsonFields' => ['attributes']])->select(['deep.nested.key@attributes'])->first();
	// $e->attributes.deep.nested.key = 'deep.nested.key'

	// Change template
	$table->configureJsonFields(['jsonPropertyTemplate' => '{{model}}_{{field}}_{{path}}']); // permanently change template for all fields
	// Change separator for the query only and for all JSON fields
	$e = $table->find(['jsonPropertyTemplate' => ['*' => '{{model}}_{{field}}_{{path}}']])->select(['deep.nested.key@attributes'])->first();
	// $e->mymodel_attributes_deep_nested_key = 'deep.nested.key'
}
```

Finally, you may want to select some data but to keep it nested like in JSON structure.



#### Finder options
Available options are :

Option  | Default value  |  Section
--|---|--
`jsonExtract`  | null  | see [Selecting fields](#selecting-fields-behavior-needed)
`jsonDateTimeTemplate`  | `"Y-m-d M:m:s"` | See [Filtering](#filtering-data-behavior-needed)
`jsonSeparator`  | `"_"`  | See [Selecting fields](#selecting-fields-behavior-needed)
`jsonFields`  | null | If null or missing, provided configuration will be applied to all JSON fields. You can provide an array of JSON field names to restrict configuration effects to these fields only

### Table/finder options
If you want to change default behavior settings, it can be done by calling `DatFieldBehavior::configureJsonFields(array $configuration)` or by providing option(s) to query at runtime. Both allow you to target only specific fields.

Available options are :

Option  | Default value  |  Section
--|---|--
`jsonReplace`  | `true`  |  see [Patching entities](#patching-entities-behavior-needed)
`jsonDateTimeTemplate`  | `"Y-m-d M:m:s"` | See [Filtering](#filtering-data-behavior-needed)
`jsonFields`  | null | If null or missing, provided configuration will be applied to all JSON fields. You can provide an array of JSON field names to restrict configuration effects to these fields only

Any option can be used to permanently configure fields and/or be applied only at runtime when patching or querying data :

```php
// Permanently disable field extraction when using field selection with datfield notation
$this->table->configureJsonFields(['keepJsonNested' => true]);

/**
 * Permanently disable field extraction when using field selection with datfield notation
 * only for field named attributes
 */
$this->table->configureJsonFields(['keepJsonNested' => true, 'jsonFields' => ['attributes']]);

// Disable field extraction for all JSON fields and only for this query
$this->table->find('all', ['keepJsonNested' => true])->all()

// Disable field extraction only for `attributes` JSON field and only for this query
$this->table->find('all', ['keepJsonNested' => true, 'jsonFields' => ['attributes']])->all()
```

### Patching entities (behavior needed)
**! Breaking change from v1**
When patching entities with partial data, previous data that is not overriden will be kept *as is*. This can be disabled by providing `jsonReplace` with either `['*']` or `['<fieldname1>', ...]` to override all or targetted fields. You can also permanently disable merging by updating behavior configuration.

```
  // $e->j1 = ['key1' => true], j1 is JSON field
  // $e->j2 = ['key1' => true], j2 is JSON field

	// Merge is default behavior
  $table->patchEntity($e, ['key2@j1' => false, 'key2@j2' => false]);
  // $e->j1 = ['key1' => true, 'key2' => false]
  // $e->j2 = ['key1' => true, 'key2' => false]

	// Replace only for this patch operation
  $table->patchEntity($e, ['key2@j1' => false, 'key2@j2' => false], ['jsonReplace' => true]);
  // $e->j1 = ['key2' => false]
  // $e->j2 = ['key2' => false]

	// Apply replace only for j1 fields and this patch operation
  $table->patchEntity($e, ['key2@j1' => false, 'key2@j2' => false], ['jsonReplace' => true, 'jsonFields' => ['j1']]);
  // $e->j1 = ['key2' => false]
  // $e->j2 = ['key1' => true, 'key2' => false]

	// Permanently enable replace for all fields
	$table->configureJsonFields(['jsonReplace' => true]);
	$table->patchEntity($e, ['key2@j1' => false, 'key2@j2' => false]);
  // $e->j1 = ['key2' => false]
  // $e->j2 = ['key2' => false]

	// Permanently enable replace for j1 fields
	$table->configureJsonFields(['jsonReplace' => true, 'jsonFields' => ['j1']]);
	$table->patchEntity($e, ['key2@j1' => false, 'key2@j2' => false]);
  // $e->j1 = ['key2' => false]
  // $e->j2 = ['key1' => true, 'key2' => false]
}
```



### Filtering data (behavior needed)
Filtering on datfields can be done like on any other fields and by any usual means. Filtering expressions will be automatically translated to usable ones in JSON data.

Nevertheless, querying on date, time or datetime can be tricky as value may be stored in any thinkable format. As comparison is done by strings, you can either parse the target datetime before querying or either use `jsonDateTimeTemplate` option to specifiy or `DateTime` object provided as parameter should be formatted.

[
  'id' => 0,
  'attributes' => [
    'key' => 'key',
    'nested' => [
      'key' => 'nested.key'
    ],
    'really' => [
      'deep' => [
        'nested' => [
          'key' => 'deep.nested.key'
        ]
      ]
    ]
  ]
]

```php
// Simple search using v2 datfield notation
$data = $table->find()->where(['attributes->key' => 'key'])->first();
$data = $table->find()->where(['attributes->really.deep.nested.key' => 'deep.nested.key'])->first();
$data = $table->find()->where(['attributes->key LIKE' => '%key%', 'attributes->really.deep.nested.key' => 'deep.nested.key'])->first();

// Query builder is also fine
$data = $table->find()->where(function($exp, $q) {
	return $exp->between('attributes->lastkwown.position.lat', 2.257, 2.260);
})

// Datetime handling. say that we have stored dates in mysql format Y-m-d
$q = $table->configureJsonFields(['jsonDateTimeTemplate' => 'Y-m-d']); // Apply for all queries
$q = $table->find('all', ['jsonDateTimeTemplate' => 'Y-m-d']); // Apply for current query only
$data = $q->where('attributes->nested.date >', (new FrozenDate())); // Search for future dates from now
```

### Using aggregation and Mysql functions
At this time, the driver is not able to translate datfields in [mysql functions](https://book.cakephp.org/4/en/orm/query-builder.html#using-sql-functions) and (aggregation)https://book.cakephp.org/4/en/orm/query-builder.html#aggregates-group-and-having.

**Anyway, it's pretty easy to turn out this limitations by using alias :**

```php
$q = $this->table->find();
$res = $q->select(['string' => 'attributes->string', 'count' => $q->func()->count('*')])
	->group('string')
	->having(['string' => 'foo'])
	->distinct()
	->all(); // yields [['string' => 'foo', 'count' => 9]]
```

### Use `DatFieldTrait` within entities
You can either use `JsonEntity` or use the `Lqdt\OrmJson\Model\Entity\DatFieldTrait` trait. It extends regular `EntityTrait` to handle datfield notation while providing full comptibility with usual calls.

#### Accessors
To access data, simply use regular `get` or [curly syntax]((https://www.php.net/manual/en/language.types.string.php#language.types.string.parsing.complex)) :

```php
$e->get('attributes->deep.nested.value');
$e->get('deep.nested.value@attributes');
$e->{'attributes->deep.nested.value'};
$e->{'deep.nested.value@attributes'};
```

#### Mutators
For setting value, simply use regular `set` or [curly syntax]((https://www.php.net/manual/en/language.types.string.php#language.types.string.parsing.complex)) :

```php
$e->set('attributes->deep.nested.value', 'foo');
$e->{'attributes->deep.nested.value'} = 'foo';
```

If you want to delete a key in JSON data **and triggers dirty state**, use `delete` :

```php
$e->delete('attributes->deep.nested.value');
$e->isDirty('attributes'); // true
```

If you want to delete a key in a JSON data **and consider that is like genuine datat state**, use `unset` :

```php
$e->unset('attributes->deep.nested.value');
$e->isDirty('attributes'); // false
```

#### Property guard
You can define a field guard on JSON field and/or on a property within it. If both are defined, the property guard informaton have the precedence :

```php
$e->setAccess('attributes', false);
$e->isAccessible('attributes->deep.nested.value'); // false
$e->setAccess('attributes->deep.nested.value', true);
$e->isAccessible('attributes->deep.nested.value'); // true
```

If you want to define guarded properties in entity declaration, use an underscored notation as `<field>_<path>` :

```php
namespace App\Model\Entity;

use Lqdt\OrmJson\ORM\JsonEntity;

class Article extends JsonEntity
{
    protected $_accessible = [
        'attributes' => true,
				'attributes_deep_nested_value' => false
    ];
}
```

#### Dirty state

[Dirty state](https://book.cakephp.org/4/en/orm/entities.html#checking-if-an-entity-has-been-modified) is available at property level and field level :

```php
$e->set('attributes->deep.nested.value', 'foo');
$e->isDirty('attributes->deep.nested.value'); // true
$e->isDirty('attributes->deep.nested.othervalue'); // false
$e->isDirty('attributes'); // true
$e->isDirty(); // true
```
If you call `setDirty('attributes', false)`, all currently dirty properties of `attributes` will be set as not dirty.


**If you're willing to use datfields as foreign keys, you must enable this trait in your entity class even if you will never use the getters/setters.**

When trait is used in an entity, you can use :
- `Entity::jsonGet` to fetch a value inside JSON data. It will return an object by default. You can get an associative array by providing true as second parameter.
- `Entity::jsonSet` to set a value inside JSON data. Method is chainable or accepts array
- `Entity::jsonIsset` to check if a key is defined inside JSON data
- `Entity::jsonUnset` to delete a key inside JSON data. Method is chainable or accepts array

All of these methods are relying on regular get/set/unset and triggers dirty state of the entity. Any of these can handle regular fields as they map to native entity methods when anything else a dat .

```php
$username = $user->jsonGet('username@attributes');
$id = $user->jsonGet('id'); // Will also work !
$user
  ->jsonSet('prefs.theme@attributes', 'notSoLovely')
  ->jsonSet([
    'metas.blue@attributes' => 'sea',
    'metas.red@attributes' => 'apple'
  ]);
```

You can also use [complex (curly) syntax] for shorter access :

```php
$username = $user->{'username@attributes'};
$user->{'username@attributes'} = 'new-one';
isset($user->{'username@attributes'}) // true
unset($user->{'username@attributes'})
isset($user->{'username@attributes'}) // false
```

### Linking models together
**You must extends `Lqdt\OrmJson\ORM\JsonTable in order to have associations working with datfields**

The plugin allows to use datfield notation to reference a foreignKey and links tables on this basis. It will not be as efficient as regular foreign keys that will indexed but it can be handy.

You'll find in `GeolocationExampleTest`a case study :
Let's say that you manage a fleet of vehicles and a team of drivers. Geolocation of vehicles is stored in a separate NoSQL database as an array of :

```
[
	{
		timestamp: 1657714125,
		position: {
			lat: 45.721459
			lon: 4.585568
		},
		vehicle: {
			id: '420EF'
		}
	},
	[...]
]
```

For weekly reports, you have to performs some advanced queries and bundle it up with the MySQL data part. Say that you want to know which driver(s) were in a given area at a given time. We won't cover here how to perform import of NoSQL data but it may likely result in a huge `locations` table with has many rows that you have in your NoSQL database and a big `data` JSON field that contains the NoSQL data.

What you want now is linking your `Vehicles` model to the `Locations` :

```php
<?php
namespace App\Model\Table;

use Lqdt\OrmJson\ORM\JsonTable;

class VehiclesTable extends JsonTable {
	public function initialize(array $options) {
		parent::initialize($options);

		// [...]

		$this->hasMany('Locations', [
			'bindingKey' => 'geocode_id', // stores the vehicle id used by NoSQL database
			'foreignKey' => 'data->vehicle.id',
		]);
	}
}
?>
```
The `LocationsTable` should at least use the behavior or also extends `JsonTable`.

It's done ! You can now query things to find out which drivers were in a given area at a given time :

```php
$drivers = $Drivers
	->find()
	->innerJoinWith('Vehicles.Locations', function($q) {
			return $q
				->where('Locations.data->timestamp', 1657714125)
				->between('Locations.data->position.lat', 45.1, 45.3)
				->between('Locations.data->position.lon', 4.5, 4.6);
		])
	})
	->all();
```


### API reference
See [API reference](https://liqueurdetoile.github.io/cakephp-orm-json/)

## Migrating from v1.x
In previous versions, we've tried to convert clauses within Query by dedicating the JsonQuery that extends it to bring up functionnalities. It worked very well but it was still limited to Query overrides.

From version 2.0.0, translation is done at MySQL driver level. The behavior nows create a temporary upgraded connection with the new driver that is able to translate any datfield notation in :

 - select statements
 - order statements
 - where statements
 - groupBy and having statements

CakePHP makers are great guys because they meant to plan many overrides that makes this plugin feasible.

Datfield can now even be used to declare foreign keys in associations. It only implies using `Lqdt/OrmJson/ORM/JsonTable` as base class for your tables in order to upgrade associations for supporting datfield notation. It is surely not a great idea when speaking performance but it can be handy when importing raw data from unstructured database system.

Version 2.x is a breaking change from 1.x as JsonQuery is not needed and available anymore. Similarly, you don't need any `jsonX` methods on entities. Regular mutators, accessors and magic properties will work well with datfields.

Last breaking change is that merging json data when patching entities is now the default behavior. You don't have to call `jsonMerge` anymore. This can still be disabled (see [patching entities](#patching-entities-behavior-needed) for details).

**Migrating is quite simple though**, simply stick to regular query statements and use `find`, `select`, `order`, `where` instead of previous ones `jsonQuery`, `find('json')`, `jsonSelect`, `jsonOrder`, `jsonWhere`. In entities, use regular accesors and mutators or curly brackets notation to access data in JSON.

## Changelog
**v2.0.0**
This version is more closing to regular use of ORM and should be used as it's still compatibible with Cakephp 3.5+.

- *BREAKING CHANGE* : Replace JsonQuery logic by a dedicated database driver that handles seamlessly the parsing of dat fields
- *BREAKING CHANGE* : Replace JsonXXX entity methods and use regular accessors and mutators
- *BREAKING CHANGE* : Data merging is now default behavior when using `patchEntity`
- Add compatibility with Cakephp 4x and PHP 8
- Add v2 datfield notation support `'[Model.]field->path'`
- Completely rework and optimize query translations of datfield syntax
- Fully rework `DatFieldBehavior`
- Add `JsonEntity` class
- Add support for curly syntax when dealing with entity data
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
