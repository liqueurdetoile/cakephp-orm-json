[![Latest Stable Version](https://img.shields.io/github/release/liqueurdetoile/cakephp-orm-json.svg?style=flat-square)](https://packagist.org/packages/liqueurdetoile/cakephp-orm-json
)
[![license](https://img.shields.io/github/license/liqueurdetoile/cakephp-orm-json.svg?style=flat-square)](https://packagist.org/packages/liqueurdetoile/cakephp-orm-json)


# Cake-orm-json plugin

**This branch is for CakePHP 3.5+ or 4.0+**

This plugin adds support to perform usual CakePHP ORM operations on JSON types fields.

*Never forget that relational databases **are not primarily designed** to manage non-schemed data and using JSON data fields can issue bad performances.*

However, there is always some cases where JSON fields are handy, especially with EAV data model and this plugin can ease the pain to use them with CakePHP.

**Caution : It only works with Mysql databases > 5.7 (supporting JSON type field) by now.**

This plugin brings :
- DatFieldBehavior behavior for models
- DatFieldTrait trait for entities

Provided behavior and underlying database driver are relying on [Mysql JSON_EXTRACT function](https://dev.mysql.com/doc/refman/8.0/en/json-functions.html) to work from CakePHP inside JSON data nearly as if each property was a regular field.

Provided trait provides functions to quick get/set values in JSON data.

Both are based on a [specific custom notation](#Datfield-format-explanation).

<!-- TOC depthFrom:1 depthTo:6 withLinks:1 updateOnSave:1 orderedList:0 -->

- [Cake-orm-json plugin](#cake-orm-json-plugin)
	- [What is changing from v1.x.x ?](#what-is-changing-from-v1xx-)
	- [Installation](#installation)
		- [Install plugin](#install-plugin)
		- [Loading plugin `Lqdt/OrmJson`](#loading-plugin-lqdtormjson)
		- [Add DatField behavior to tables](#add-datfield-behavior-to-tables)
		- [Add DatField Trait (useless if using fallback entity class with behavior or extending ObjectEntity classes)](#add-datfield-trait-useless-if-using-fallback-entity-class-with-behavior-or-extending-objectentity-classes)
	- [Usage](#usage)
		- [Datfield format explanation](#datfield-format-explanation)
		- [Basic usage](#basic-usage)
		- [Table or finder options](#table-or-finder-options)
		- [Spreading json data on entity](#spreading-json-data-on-entity)
		- [Patching entities (behavior needed)](#patching-entities-behavior-needed)
		- [Selecting fields (behavior needed)](#selecting-fields-behavior-needed)
		- [Use JSON setter/getter methods with entities](#use-json-settergetter-methods-with-entities)
		- [API reference](#api-reference)
	- [Changelog](#changelog)
	- [Disclaimer](#disclaimer)

<!-- /TOC -->

## What is changing from v1.x.x ?
In previous versions, we've tried to convert clauses within Query by dedicating the JsonQuery that extends it to bring up functionnalities. It worked very well but it was still limited to Query overrides.

From this version 2, translation is done deeper, at driver level. The behavior nows create a temporary upgraded connection with the new driver that is able to translate any datfield notation in :

 - select statements
 - order statements
 - where statements

Datfield can now even be used to declare foreign keys in associations.

Therefore, this version is really a breaking change from v1x as JsonQuery is not needed and available anymore.
Another breaking change is that merging json data when patching entities is now the default behavior. You don't have to call `jsonMerge` anymore. This can still be disabled (see [patching entities](#patching-entities-behavior-needed) for details).


**Migrating is quite simple though**, simply stick to regular query statements and use `find`, `select`, `order`, `where` instead of previous ones `jsonQuery`, `find('json')`, `jsonSelect`, `jsonOrder`, `jsonWhere`.

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

### Add DatField behavior to tables
You simply need to attach declare the behavior. It automatically allows use of datfield notation within queries with no more burden. It also allows to use datfield notation whith `setEntity` or `patchEntity`.

```php
// App/Model/Table/UsersTable.php
namespace App\Model\Table;

use Cake\ORM\Table;

class UsersTable extends Table
{
    public function initialize(array $config)
    {
        $this->addBehavior('Lqdt/OrmJson.DatField');
        // [...]
    }
}
```

### Add DatField Trait (useless if using fallback entity class with behavior or extending ObjectEntity classes)
Datfield trait brings up tools to access with ease the content of JSON fields.

When using `DatFieldBehavior`, fallack entity class is updated to use the trait. If you are using entity classes (and you should in order to handle mass assignment correctly), simply extends `Lqdt\OrmJson\ORM\ObjectEntity` instead of regular `Cake\ORM\Entity` to automatically loads trait or add the trait.

Anyway, you might want to use only the trait and not the behavior and its totally fine.

```php
// App/Model/Entity/User.php
namespace App\Model\Entity;

use Cake\ORM\Entity;
use Lqdt\OrmJson\Model\Entity\DatFieldTrait;

class User extends Entity
{
    use DatFieldTrait;
}
```
## Datfield format
In order to work with inner JSON data, we need to know which field to use and which path to use in this field. You can, obviously use SQL fragments and native Mysql JSON functions but it's very prone to error and, well, why use an ORM if writing raw SQL each time ?

This plugin leverage this by providing a quickier syntax to access and manipulate JSON data. In fact, this version brings up two ways and you can chosse or mix which one will best suit your way.

For instance, let's say you have a JSON field named `position`, exposing two keys `lat` and `lon` in a `Locations` model.

In V1, this plugin introduced the `datfield` format (contraction of `dot` and `at field`) whick looks like : <tt>path@[Model.]field</tt> and can be used in the same way regular fields are used. `Model` part is optional if no name conflict may occurs.

This version also supports a more *conventional* way which looks like : <tt>[Model.]field->path</tt>

For query operations or with special entity getters/setters, You may consider using `'lat@position'` or `'position->lat'` to easily manipulate and access the `lat` data in the `position` field.

Datfield become especially powerful when accessing deep nested keys : `'lastknown.computed.lat@position'` (or `'position->lastknown.computed.lat'`) will target `'position['lastknown']['computed']['lat']'` value in data.

In depth, the plugin will translate query to have operations done by Mysql itself through [JSON functions](https://dev.mysql.com/doc/refman/5.7/en/json-function-reference.html) (mostly JON_EXTRACT);


## Usage
within next examples, we will always work with a data structure like so :
```php
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
```

### Basic usage
DatField notation can be used in nearly any statements involving fields.

### Connection upgrade and autoquoting of identifiers
When using `DatFieldBehavior`, the table is upgrading the connection and creates a new one that will use `DatFieldMysql` driver. Unfortunately, Cakephp identifier autoquoting is messing up with MYSQL JSON functions. If enabled on your connection, it will be disabled in upgraded connection.

### Table/finder options
When using this plugin, there's a few of default behaviors when manipulating json fields :

- When using query `where` or `order` statements, json fields are kept as associative arrays (default mapping of json fields in Cakephp)
- When using `select` with DatField notation, a new property is created on entity with a composite key made from the JSON field name and the data path in this field.

If you want to change the default behavior, it is done by calling `DatFieldBehavior::configureJsonFields(array $configuration, array $fields = ['*'])` or by providing option(s) at runtime with targetted fields. You can also target only specific fields.

Available options are :

Option  | Default value  |  Section
--|---|--
`jsonReplace`  | `true`  |  see Patching entities
`keepJsonNested`  | `false`  | see Selecting fields
`jsonDateTimeTemplate`  | `"Y-m-d M:m:s"` | See Filtering
`jsonPropertyTemplate`  | `"{{field}}{{separator}}{{path}}"` | See Selecting fields
`jsonSeparator`  | `"_"`  | see Selecting fields
`parseJsonAsObject`  | `false`  | See Use PHP objects instead of arrays
`jsonFields`  | null | If null or missing, configuration will be applied to all JSON fields. You can provide an array of JSON field names to restrict configuration effects to these fields

Any option can be used to permanently configure fields or be applied at runtime when patching or querying data :

```php
// Permanently disable field extraction when using field selection with datfield notation
$this->table->configureJsonFields(['keepJsonNested' => true]);

// Permanently disable field extraction when using field selection with datfield notation only for field named attributes
$this->table->configureJsonFields(['keepJsonNested' => true, 'jsonFields' => ['attributes']]);

// Disable field extraction for all JSON fields and only for this query
$this->table->find('all', ['keepJsonNested' => true])->all()

// Disable field extraction only for `attributes` JSON field and only for this query
$this->table->find('all', ['keepJsonNested' => true, 'jsonFields' => ['attributes']])->all()
```

### Use PHP objects instead of arrays
**If you want to permanently use objects, it is much easier to use [Cakephp data types](https://book.cakephp.org/4/en/orm/database-basics.html#data-types) to do so**

DatFieldBehavior allows to tweak default Cakephp mapping to associative array through `parseJsonAsObject` option :

```php
// Permanently use objects
$this->table->configureJsonFields(['parseJsonAsObject' => true]);

// Parse all JSON fields as objects only for this query
$this->table->find('all', ['parseJsonAsObject' => true])->all()
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

### Selecting fields (behavior needed)

You can easily select specific paths in your data among paths with a regular select statement. As default, extracted data will be flattened at entity level by using `jsonPropertyTemplate` to generate field name.

When using, you can use `{{model}}` to parse Model name, `{{field}}` to parse field name, `{{path}}` to parse full path and `{{separator}}` to use configured separator. Aliases parsed with template are always lowercased.

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

```php
  /**
   * Keep data nested (i.e. filtering json data)
   */
  $table->configureJsonFields(['keepJsonNested' => true]); // permanently disable flattening for all JSON fields
  // Change nesting behavior for the query only
  $e = $table->find(['keepJsonNested' => true])->select(['id', 'deep.nested.key@attributes'])->first();
  //or
	$e = $table->find(['keepJsonNested' => true])->select(['id', 'deep.nested.key@attributes'])->first();
  // $e->attributes['deep']['nested']['key'] = 'deep.nested.key'
}
```

When using `keepJsonNested` and dotted alias, you can create arbitrary JSON structure to reorganize your data :

```php
  $e = $table->find(['keepJsonNested' => true])->select(['id', 'my.very.special.way' => 'deep.nested.key@attributes'])->first();
  // $e->attributes['my']['very']['special']['way'] = 'deep.nested.key'
}
```

### Filtering (behavior needed)
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

### Use JSON setter/getter methods with entities

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

You can also use [complex (curly) syntax](https://www.php.net/manual/en/language.types.string.php#language.types.string.parsing.complex) for shorter access :

```php
$username = $user->{'username@attributes'};
$user->{'username@attributes'} = 'new-one';
isset($user->{'username@attributes'}) // true
unset($user->{'username@attributes'})
isset($user->{'username@attributes'}) // false
```

### API reference
See [API reference](https://liqueurdetoile.github.io/cakephp-orm-json/)

## Changelog
**v2.0.0**
This version is more closing to regular use of ORM and should be used as it's still compatibable with Cakephp 3.5+.

- *BREAKING CHANGE* : Replace JsonQuery logic by a dedicated database driver that handles seamlessly the parsing of dat fields
- *BREAKING CHANGE* : Data merging is now default behavior when using `patchEntity`
- Add compatibility with Cakephp 4x and PHP 8
- Add v2 datfield notation support `'[Model.]field->path'`
- Completely rework and optimize query translations of datfield syntax
- Fully rework `DatFieldBehavior`
- Add `ObjectEntity` class
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
