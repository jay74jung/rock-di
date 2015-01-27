<?php

namespace rockunit;

use rock\base\ObjectInterface;
use rock\base\ObjectTrait;
use rock\di\Container;

/**
 * @group base
 */
class ContainerTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Container::removeMulti(['bar', 'foo' , 'baz']);
    }

    public static function tearDownAfterClass()
    {
        static::setUpBeforeClass();
    }

    public function testAddAndGet()
    {
        $config = ['class' => Bar::className(), 'singleton' => true];
        Container::add('bar', $config);
        $this->assertSame(Bar::className(), Container::get('bar')['class']);
    }

    /**
     * @depends testAddAndGet
     */
    public function testExists()
    {
        $this->assertTrue(Container::exists('bar'));
    }

    /**
     * @depends testAddAndGet
     */
    public function testRemove()
    {
        Container::remove('bar');
        $this->assertNull(Container::get('bar'));
        $this->assertFalse(Container::exists('bar'));
    }

    public function testLoad()
    {
        // Singleton
        $config = ['class' => Bar::className(), 'singleton' => true];
        Container::add('bar', $config);
        $this->assertTrue(Container::load(['class' => Bar::className()]) instanceof Bar);

        // new instance
        $config = ['class' => Bar::className()];
        Container::add('bar', $config);
        $this->assertTrue(Container::load('bar') !== Container::load('bar'));

        // as Closure
        $config = function($data = null){
            $this->assertSame($data[0], 'test');
            return new Bar();
        };
        Container::add('bar', $config);
        $this->assertTrue(Container::load('test','bar') instanceof Bar);
        $this->assertTrue(Container::load('test', ['class' => 'bar']) instanceof Bar);
    }

    public function testNewCustomArgsConstruct()
    {
        $foo = new Foo(new Bar, null, null, ['baz' => new Baz]);
        $this->assertTrue($foo->bar instanceof Bar);
        $this->assertTrue($foo->baz instanceof Baz);
        $this->assertNull($foo->baz->bar);

        $foo = Container::load(['class'=>Foo::className(), 'baz' => new Baz]);
        $this->assertTrue($foo->bar instanceof Bar);
        $this->assertTrue($foo->baz instanceof Baz);
        $this->assertTrue($foo->baz2 instanceof Baz);
        $this->assertNull($foo->param);

        $foo = Container::load(new Bar, 'test', ['class'=>Foo::className(), 'baz' => new Baz]);
        $this->assertTrue($foo->bar instanceof Bar);
        $this->assertTrue($foo->baz instanceof Baz);
        $this->assertTrue($foo->baz2 instanceof Baz);
        $this->assertSame('test', $foo->param);

        // inline class
        $foo = Container::load(new Bar, ['test'], new Baz, Foo::className());
        $this->assertTrue($foo->bar instanceof Bar);
        $this->assertTrue($foo->baz2 instanceof Baz);
        $this->assertSame(['test'], $foo->param);

    }

    public function testIoCCustomArgsConstruct()
    {
        Container::add('foo', ['class'=>Foo::className(), 'baz' => new Baz]);
        $foo = Container::load(['class'=>Foo::className(), 'baz' => new Baz]);
        $this->assertTrue($foo->bar instanceof Bar);
        $this->assertTrue($foo->baz instanceof Baz);
        $this->assertTrue($foo->baz2 instanceof Baz);
        $this->assertNull($foo->param);

        Container::add('foo', ['class'=>Foo::className(), 'singleton' =>true, 'baz' => new Baz]);
        $foo = Container::load(new Bar, 'test', ['class'=>Foo::className(), 'baz' => new Baz]);
        $this->assertTrue($foo->bar instanceof Bar);
        $this->assertTrue($foo->baz instanceof Baz);
        $this->assertTrue($foo->baz2 instanceof Baz);
        $this->assertSame('test', $foo->param);

        $foo = Container::load(new Bar, 'test', ['class'=>Foo::className(), 'baz' => Container::load(Baz::className())]);
        $this->assertTrue($foo->bar instanceof Bar);
        $this->assertTrue($foo->baz instanceof Baz);
        $this->assertTrue($foo->baz2 instanceof Baz);
        $this->assertSame('test', $foo->param);

        // inline class
        Container::add('foo', ['class'=>Foo::className(), 'singleton' =>true, 'baz' => new Baz]);
        $foo = Container::load(new Bar, ['test'], new Baz, Foo::className());
        $this->assertTrue($foo->bar instanceof Bar);
        $this->assertTrue($foo->baz2 instanceof Baz);
        $this->assertSame(['test'], $foo->param);
    }

    public function testWithoutObjectTrait()
    {
        Container::add('test3', ['class'=>'\rockunit\Test3']);
        $this->assertTrue(Container::load('test3') instanceof Test3);
        $this->assertTrue(Container::load('test3')->baz instanceof Baz);

        Container::add('\rockunit\Test4', ['class'=>'\rockunit\Test4']);
        $this->assertTrue(Container::load('\rockunit\Test4') instanceof Test4);
        $this->assertTrue(Container::load('\rockunit\Test4') !== Container::load('\rockunit\Test4'));

        // singleton (only by alias)
        Container::add('test4', ['class'=>'\rockunit\Test4', 'singleton' => true]);
        $this->assertTrue(Container::load('test4') === Container::load('test4'));
    }

    public function testThrowException()
    {
        try {
            Container::load(Test::className());
        } catch (\Exception $e) {
            $this->assertNotEmpty($e->getMessage());
        }

        try {
            Container::load('unknown');
        } catch (\Exception $e) {
            $this->assertNotEmpty($e->getMessage());
        }

        $test = Container::load(new Bar, Test::className());
        $this->assertTrue($test->bar instanceof Bar);

        Container::load(Test2::className());
    }

    public function testThrowExceptionDisable()
    {
        $this->assertNull(Container::load('unknown', false));
    }

    public function testSetProperties()
    {
        Container::add('test5', ['class' => Test5::className(), 'name' => 'Tom', 'age' => 20]);
        $object = Container::load(['class' => Test5::className(), 'age' => 25]);
        $this->assertSame('Tom', $object->name);
        $this->assertSame(25, $object->age);
    }
}


class Foo implements ObjectInterface
{
    use \rock\base\ObjectTrait {
        ObjectTrait::__construct as parentConstruct;
    }

    public $baz;
    public $baz2;


    public $param;
    public $bar;

    public function __construct(Bar $bar, $param = null, Baz $baz = null, array $config = [])
    {
        $this->parentConstruct($config);
        $this->param = $param;
        $this->bar = $bar;
        $this->baz2 = $baz;
    }
}


class Baz implements BarInterface, ObjectInterface
{
    use ObjectTrait;

    public $bar;
}

interface BarInterface{

}

class Bar implements BarInterface, ObjectInterface
{
    use ObjectTrait;

}

class Test implements ObjectInterface
{
    use ObjectTrait;

    public $bar;
    public function __construct(BarInterface $bar, array $configs = [])
    {
        $this->setProperties($configs);
        $this->bar = $bar;
    }
}

class Test2 implements ObjectInterface
{
    use ObjectTrait;

    public function __construct(BarInterface $bar = null, array $configs = [])
    {
        $this->setProperties($configs);
    }
}


class Test3
{
    public $baz;
    public function __construct(Baz $baz = null)
    {
        $this->baz = $baz;
    }
}

class Test4
{
    public function __construct()
    {
    }
}

class Test5 implements ObjectInterface
{
    use ObjectTrait;

    public $name;
    private $age;

    public function setAge($age)
    {
        $this->age = $age;
    }

    public function getAge()
    {
        return $this->age;
    }
}