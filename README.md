# eArc-parameter-transformer

Lightweight parameter transformer component. Use type hints to auto-wire
`objects`, `functions` and `methods`.

## Table of Contents

- [install](#install)
- [bootstrap](#bootstrap)
- [configure](#configure)
- [basic usage](#basic-usage)
    - [transform](#transform)
    - [input parameter](#input-parameter)
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

The earc/parameter-transformer does not require any bootstrapping.

## configure

The earc/parameter-transformer does not require any configuration.

## basic usage

### transform

```php
use eArc\ParameterTransformer\ParameterTransformer;

$argv = (new ParameterTransformer())->transform($target); // string|object|callable
```

```php
use eArc\ParameterTransformer\ParameterTransformer;

$result = (new ParameterTransformer())->callFromTransform($callable);
```

```php
use eArc\ParameterTransformer\ParameterTransformer;

$result = (new ParameterTransformer())->castFromTransform($object);
```

```php
use eArc\ParameterTransformer\ParameterTransformer;

$result = (new ParameterTransformer())->constructFromTransform($fQCN);
```

### input parameter

### mapping parameter


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
        - every class (that fits the supplied input value)
    - self:
        - `eArc\ParameterTransformer\ParameterTransformerFactoryInterface`
    - [earc/data](https://github.com/Koudela/eArc-data)
        - `eArc\Data\Entity\Interfaces\EntityInterface`
    - [earc/di](https://github.com/Koudela/eArc-di)
        - every service that can be build via `di_get`
