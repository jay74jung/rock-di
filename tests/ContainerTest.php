<?php

namespace rockunit;

use rock\base\ObjectInterface;
use rock\base\ObjectTrait;
use rock\di\Container;
use rock\di\ContainerException;

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

    public function testRegistryAndGet()
    {
        $config = ['class' => Bar::className(), 'singleton' => true];
        Container::registryMulti(['bar' => $config]);
        $this->assertSame(Bar::className(), Container::get('bar')['class']);

        // getAll
        $this->assertNotEmpty(Container::getAll());

        // count
        $this->assertSame(1, Container::count());
    }

    /**
     * @depends testRegistryAndGet
     */
    public function testExists()
    {
        $this->assertTrue(Container::exists('bar'));

        // is singleton
        $this->assertTrue(Container::isSingleton('bar'));
        $this->assertFalse(Container::isSingleton('baz'));
    }

    /**
     * @depends testRegistryAndGet
     */
    public function testRemove()
    {
        Container::removeMulti(['bar']);
        $this->assertNull(Container::get('bar'));
        $this->assertFalse(Container::exists('bar'));
    }

    public function testLoad()
    {
        // Singleton
        $config = ['class' => Bar::className(), 'singleton' => true];
        Container::registry('bar', $config);
        $this->assertTrue(Container::load(['class' => Bar::className()]) instanceof Bar);

        // new instance
        $config = ['class' => Bar::className()];
        Container::registry('bar', $config);
        $this->assertTrue(Container::load('bar') !== Container::load('bar'));

        // as Closure
        $config = function($data = null){
            $this->assertSame($data[0], 'test');
            return new Bar();
        };
        Container::registry('bar', $config);
        $this->assertTrue(Container::load('test','bar') instanceof Bar);
        $this->assertTrue(Container::load('test', ['class' => 'bar']) instanceof Bar);

        // as Closure + array
        $config = function($data = null){
            $this->assertSame($data[0], 'test');
            return [
                'class' => Bar::className()
            ];
        };
        Container::registry('bar', $config);
        $this->assertTrue(Container::load('test','bar') instanceof Bar);
        $this->assertTrue(Container::load('test', ['class' => 'bar']) instanceof Bar);
    }

    /**
     * @depends testLoad
     */
    public function testGet()
    {
        $this->assertNotEmpty(Container::get('\\' .Bar::className()));
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
        Container::registry('foo', ['class'=>Foo::className(), 'baz' => new Baz]);
        $foo = Container::load(['class'=>Foo::className(), 'baz' => new Baz]);
        $this->assertTrue($foo->bar instanceof Bar);
        $this->assertTrue($foo->baz instanceof Baz);
        $this->assertTrue($foo->baz2 instanceof Baz);
        $this->assertNull($foo->param);

        Container::registry('foo', ['class'=>Foo::className(), 'singleton' =>true, 'baz' => new Baz]);
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
        Container::registry('foo', ['class'=>Foo::className(), 'singleton' =>true, 'baz' => new Baz]);
        $foo = Container::load(new Bar, ['test'], new Baz, Foo::className());
        $this->assertTrue($foo->bar instanceof Bar);
        $this->assertTrue($foo->baz2 instanceof Baz);
        $this->assertSame(['test'], $foo->param);
    }

    public function testWithoutObjectTrait()
    {
        Container::registry('test3', ['class'=>'\rockunit\Test3']);
        $this->assertTrue(Container::load('test3') instanceof Test3);
        $this->assertTrue(Container::load('test3')->baz instanceof Baz);

        Container::registry('\rockunit\Test4', ['class'=>'\rockunit\Test4']);
        $this->assertTrue(Container::load('\rockunit\Test4') instanceof Test4);
        $this->assertTrue(Container::load('\rockunit\Test4') !== Container::load('\rockunit\Test4'));

        // singleton (only by alias)
        Container::registry('test4', ['class'=>'\rockunit\Test4', 'singleton' => true]);
        $this->assertTrue(Container::load('test4') === Container::load('test4'));
    }

    public function testInterfaceThrowException()
    {
        $this->setExpectedException(ContainerException::className());
        Container::load(Test5::className());
    }

    /**
     * @depends testInterfaceThrowException
     */
    public function testInterface()
    {
        Container::registry('rockunit\BarInterface', ['class'=>Bar::className()]);
        $instance = Container::load(Test5::className());
        $this->assertInstanceOf(Bar::className(), $instance->bar);
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

    public function testSetterGetter()
    {
        Container::registry('test5', ['class' => SetterGetter::className(), 'name' => 'Tom', 'age' => 20]);
        $object = Container::load(['class' => SetterGetter::className(), 'age' => 25]);
        $this->assertSame('Tom', $object->name);
        $this->assertSame(25, $object->age);
    }

    public function testRegistryWrongTypeConfigThrowException()
    {
        $this->setExpectedException(ContainerException::className());
        Container::registry('unknown', 7);
    }

    public function testRemoveAll()
    {
        Container::removeAll();
        $this->assertEmpty(Container::getAll());
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
    use ObjectTrait {
        ObjectTrait::__construct as parent__construct;
    }

    public function __construct($arg = null, $config = [])
    {
        $this->parent__construct($config);
    }
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
    public $bar;

    public function __construct(BarInterface $bar, array $configs = [])
    {
        $this->setProperties($configs);
        $this->bar = $bar;
    }
}

class SetterGetter implements ObjectInterface
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