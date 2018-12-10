<?php
/**
 * Created by zed.
 */

use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    /**
     * @var \Zed\Block\Container
     */
    protected $container;

    public function setUp()
    {
        parent::setUp();
        $this->container = new \Zed\Block\Container();
    }

    /**
     * @throws ReflectionException
     */
    public function testNormal()
    {
        $this->container['str'] = '123';
        $this->assertTrue(isset($this->container['str']));
        $this->assertEquals('123', $this->container['str']);
        unset($this->container['str']);
        $this->assertFalse(isset($this->container['str']));
    }

    /**
     * @throws ReflectionException
     */
    public function testSetClass()
    {
        $this->container->set(TestClass::class, TestClass::class);
        $object = $this->container->get(TestClass::class);
        $this->assertInstanceOf(TestClass::class, $object);
        $this->assertEquals('hello', $object->sayHello());
    }

    /**
     * @throws ReflectionException
     */
    public function testSetClosure()
    {
        $this->container->set(TestClass::class, function () {
            return new TestClass();
        });
        $object = $this->container->get(TestClass::class);
        $this->assertInstanceOf(TestClass::class, $object);
        $this->assertEquals('hello', $object->sayHello());
    }

    /**
     * @throws ReflectionException
     */
    public function testSetSingleton()
    {
        $this->container->set(TestClass::class, TestClass::class);
        $object1 = $this->container->get(TestClass::class);
        $object2 = $this->container->get(TestClass::class);
        $this->assertNotEquals($object1->random, $object2->random);

        $this->container->set(TestClass::class, TestClass::class, true);
        $object1 = $this->container->get(TestClass::class);
        $object2 = $this->container->get(TestClass::class);
        $this->assertEquals($object1->random, $object2->random);
    }

    /**
     * @throws ReflectionException
     */
    public function testRecursionBuild()
    {
        $this->container->set(TestClass::class, TestClass::class);
        $this->container->set(OtherClass::class, OtherClass::class);
        $object = $this->container->get(OtherClass::class);
        $this->assertInstanceOf(OtherClass::class, $object);
        $this->assertEquals('hello', $object->sayHello());

        $this->container->set(OtherClass::class, function (TestClass $testClass) {
            return new OtherClass($testClass);
        });
        $object = $this->container->get(OtherClass::class);
        $this->assertInstanceOf(OtherClass::class, $object);
        $this->assertEquals('hello', $object->sayHello());
    }

    /**
     * @throws ReflectionException
     */
    public function testNullableParam()
    {
        $this->container->set(OtherClass::class, OtherClass::class);
        $object = $this->container->get(OtherClass::class);
        $this->assertInstanceOf(OtherClass::class, $object);
        $this->assertNull($object->testClass);

        $this->container->set(OtherClass::class, function (TestClass $testClass = null) {
            return new OtherClass($testClass);
        });
        $object = $this->container->get(OtherClass::class);
        $this->assertInstanceOf(OtherClass::class, $object);
        $this->assertNull($object->testClass);
    }

    /**
     * @throws ReflectionException
     */
    public function testServiceProvider()
    {
        $this->container->register(ServiceProvider::class);
        $object = $this->container->get(TestClass::class);
        $this->assertInstanceOf(TestClass::class, $object);
        $this->assertEquals('hello', $object->sayHello());

        $this->container->register(new ServiceProvider());
        $object = $this->container->get(TestClass::class);
        $this->assertInstanceOf(TestClass::class, $object);
        $this->assertEquals('hello', $object->sayHello());
    }
}

class TestClass
{
    public $random;
    public function __construct()
    {
        $this->random = rand(1, 10000);
    }

    public function sayHello()
    {
        return 'hello';
    }
}

class OtherClass
{
    public $testClass;
    public function __construct(TestClass $testClass = null)
    {
        $this->testClass = $testClass;
    }

    public function sayHello()
    {
        return $this->testClass->sayHello();
    }
}

class ServiceProvider implements \Zed\Block\Contracts\ServiceProvider
{
    public function register(\Zed\Block\Contracts\ContainerInterface $container): void
    {
        $container->set(TestClass::class, TestClass::class);
    }
}