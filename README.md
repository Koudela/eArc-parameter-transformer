# eArc-parameter-transformer

Lightweight parameter transformer component. Use type hints to auto-wire
`objects`, `functions` and `methods`.

## Table of Contents

- [install](#install)
- [bootstrap](#bootstrap)
- [basic usage](#basic-usage)
    - [auto wire functions and methods](#auto-wire-functions-and-methods)
    - [auto update objects](#auto-update-objects)
- [configuration](#configuration)
    - [mapping parameter](#mapping-parameter)
- [advanced usage](#advanced-usage)
    - [add customized support for special type hints](#add-customized-support-for-special-type-hints)
- [releases](#releases)
    - [release 0.0](#release-00)

## install

```shell script
$ composer require earc/parameter-transformer
```

## bootstrap

earc/parameter-transformer uses [earc/di](https://github.com/Koudela/eArc-di) for dependency injection and tagging.

```php
use eArc\DI\DI;

DI::init();
```

## basic usage

All transforming functionality of the earc/parameter-transformer is bundled into
the service `ParameterTransformer`. The parameter transformer exposes five methods:
- `objectTransform` (transforms `objects` by calling setters and adding values to properties from input)
- `callableTransform` (generates an array of arguments from input the `callable` can be called with)
- `callFromTransform` (shortcut function: calls the `callable` with the arguments provided via `callableTransform`)
- `constructFromTransform` (shortcut function: instantiates an object with the constructor arguments provided via `callableTransform`)
- `castFromTransform` (shortcut function: retrieves an object via `constructFromTransform` and calls `objectTransform` on it)

All methods take three arguments:
- *target* (the target the type hints are read from and transformations are applied to)
- *input* `array` (the input the arguments for the type hints are calculated from - if `null` input is taken from `php://input`)
- *config* `Configuration` (a config object to fine tune transformation - if `null` a default config is used)

### auto wire functions and methods

```php
use eArc\ParameterTransformer\Configuration;
use eArc\ParameterTransformer\ParameterTransformer;

$input = [
    'parameterNameOne' => 'someValue',
    'parameterNameTwo' => 'anotherValue',
    //...
];

// $target has to be a callable a class string or an instance of \ReflectionFunctionAbstract
$target = [MyClass::class, 'myMethod'];

$argv = (new ParameterTransformer())->callableTransform($target, $input, new Configuration());
```

### auto update objects

```php
use eArc\ParameterTransformer\Configuration;
use eArc\ParameterTransformer\ParameterTransformer;

$input = [
    'parameterNameOne' => 'someValue',
    'parameterNameTwo' => 'anotherValue',
    //...
];

$target = new MyClass();

$target = (new ParameterTransformer())->objectTransform($target, $input, new Configuration());
```

## configure and extend transformation

### how the input array is determined

If an input array is provided via the seconds service method parameter, this
input is taken. Otherwise `php://input` is used to create an input array.

You can provide an alternative fallback via the `setDefaultResource()` method:

```php
use eArc\ParameterTransformer\Configuration;

$config = (new Configuration())->setDefaultResource([new MyInputProvider(), 'getDefault']);
```

### how the input value is chosen

1. The name of the variable is used to retrieve the starting value from the input.
   For example if the variable is named `$product` the `$input['product']` is taken
   as value.

You can configure this behaviour via the `setMapping()` method:

```php
use eArc\ParameterTransformer\Configuration;

$mapping = [
    'id' => 0,
    'product' => 'productName',
    'description' => 'text',
];

$config = (new Configuration())->setMapping($mapping);
```

2. If the key does not exist, the next positional key (`int`) is used.
3. If neither the named key nor the next positional key exists a `NoInputException`
   is thrown.
   
You can change this behaviour via the `setNoInputIsAllowed()` method:

```php
use eArc\ParameterTransformer\Configuration;

$config = (new Configuration())->setNoInputIsAllowed(true);
```

Instead of a thrown exception a `null` value is used.

### how type hints are transformed

   
2. If `null` is type hinted, and the starting value is `'null'` the starting value 
   will be treated as `null`.
3. For build in primitive types the starting value is cast to the result value.
4. If a type hinted class implements the `ParameterTransformerFactoryInterface` the
   `buildFromParameter()` method is used to build the result value.
   
You can implement this if there is a way to build or retrieve an object via a single
parameter. For example an entity.

```php
use eArc\ParameterTransformer\Interfaces\ParameterTransformerFactoryInterface;

class MyClient implements ParameterTransformerFactoryInterface
{
    //...

    public function __construct(string $connectionConfigurationString)
    {
        //...
    }

    //...

    public static function buildFromParameter($parameter) : static
    {
        return new MyClient((string) $parameter);
    }
}
```

5. For all services implementing the `ParameterTransformerFactoryServiceInterface` 
   and tagged by it will have the `buildFromParameter()` called until one of them
   returns an object.

This is especially useful for entities.

```php
use eArc\Data\Entity\Interfaces\EntityInterface;
use eArc\ParameterTransformer\Interfaces\ParameterTransformerFactoryServiceInterface;

class MyEntityTypeHintingService implements ParameterTransformerFactoryServiceInterface
{
    public function buildFromParameter(string $fQCN, $parameter) : object|null
    {
        if (is_subclass_of($fQCN, EntityInterface::class)) {
            return data_load($fQCN, $parameter);
        }
        
        return di_get(EntityManagerInterface::class)->getRepository($fQCN)?->find($parameter);
    }
}

di_tag(ParameterTransformerFactoryServiceInterface::class, MyEntityTypeHintingService::class);
```

6. If it is a union type one type hint after the other is taken for evaluation.
   The first result different from the input value (`!=`) and not null is the result
   value.
   
7. If the result value is `null` and there is a default value hinted, the default
   value is the new result value.

### how callables are transformed

1. From class strings is the constructor method retrieved `callables` and `AbstractFunctionReflections`
   stay the same.
2. 

## advanced usage

### add customized support for special type hints

## releases

### release 0.0

- first official release
- PHP ^8.0
- supported type hints:
    - native:
        - null
        - int 
        - float 
        - bool
        - string
    - self:
        - `ParameterTransformerFactoryInterface`
- type hint transformation is extendable via the `ParameterTransformerFactoryServiceInterface`
