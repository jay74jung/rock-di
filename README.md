Dependency Injection library for PHP
=================

[![Latest Stable Version](https://poser.pugx.org/romeOz/rock-di/v/stable.svg)](https://packagist.org/packages/romeOz/rock-di)
[![Total Downloads](https://poser.pugx.org/romeOz/rock-di/downloads.svg)](https://packagist.org/packages/romeOz/rock-di)
[![Build Status](https://travis-ci.org/romeOz/rock-di.svg?branch=master)](https://travis-ci.org/romeOz/rock-di)
[![HHVM Status](http://hhvm.h4cc.de/badge/romeoz/rock-di.svg)](http://hhvm.h4cc.de/package/romeoz/rock-di)
[![Coverage Status](https://coveralls.io/repos/romeOz/rock-di/badge.svg?branch=master)](https://coveralls.io/r/romeOz/rock-di?branch=master)
[![License](https://poser.pugx.org/romeOz/rock-di/license.svg)](https://packagist.org/packages/romeOz/rock-di)

Features
-------------------

 * Service locator
 * Constructor injection
 * Support singleton
 * Standalone module/component for [Rock Framework](https://github.com/romeOz/rock)

Installation
-------------------

From the Command Line:

```
composer require romeoz/rock-di
```

In your composer.json:

```json
{
    "require": {
        "romeoz/rock-di": "*"
    }
}
```

Quick Start
-------------------

```php
namespace test;

use rock\di\Container;

class Foo 
{
    
}

$config = [
    'class' => '\test\Foo', 
    // 'singleton' => true,   // if you want to return singleton
 ];
$alias = 'foo' ;  // short alias
Container::register($alias, $config);

$foo = Container::load('foo');
```

####Constructor injection

```php
namespace test;

use rock\di\Container;

class Foo 
{
    
}

class Bar 
{
    public $foo;
        
    public function __construct(Foo $foo)
    {
        $this->foo = $foo;
    }
}

$config = [
    'class' => '\test\Foo',
 ];
Container::register('foo' , $config);

$config = [
    'class' => '\test\Bar',
 ];
Container::register('bar' , $config);

$bar = Container::load('bar');
$bar->foo instanceof Bar; // output: true
```

####Configure properties

```php
namespace test;

use rock\di\Container;
use rock\base\ObjectInterface;
use rock\base\ObjectTrait;

class Foo implements ObjectInterface
{
    use ObjectTrait;
    
    public $name;
}

$config = [
    'class' => '\test\Foo', 
    
    // properties
    'name' => 'Tom'
 ];

Container::register('foo', $config);

$foo = Container::load('foo');

echo $foo->name; // output: Tom 
```

Configure properties through setters and getters:

```php
namespace test;

use rock\di\Container;
use rock\base\ObjectInterface;
use rock\base\ObjectTrait;

class Foo implements ObjectInterface
{
    use ObjectTrait;
    
    private $name;
    
    public function setName($name)
    {
        $this->name = $name;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
}

$config = [
    'class' => '\test\Foo', 
    
    // properties
    'name' => 'Tom'
 ];

Container::register('foo', $config);

$foo = Container::load('foo');

echo $foo->name; // output: Tom 
```

Requirements
-------------------
 * **PHP 5.4+**

License
-------------------

The Rock Dependency Injection is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).