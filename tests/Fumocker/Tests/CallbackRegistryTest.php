<?php
namespace Fumocker\Tests;

use Fumocker\CallbackRegistry;
use PHPUnit\Framework\TestCase;

class CallbackRegistryTest extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        self::unsetCallbackRegistrySingleton();
    }

    public static function tearDownAfterClass(): void
    {
        self::unsetCallbackRegistrySingleton();
    }

    /**
     * @test
     *
     * @dataProvider provideValidCallbacks
     */
    public function shouldAllowToSetCallable($validCallable)
    {
        $this->expectNotToPerformAssertions();

        CallbackRegistry::getInstance()->set('namespace', 'functionName', $validCallable);
    }

    /**
     * @test
     *
     * @dataProvider provideNoCallableItems
     */
    public function throwWhenSetInvalidCallable($invalidCallable)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid callable provided');

        CallbackRegistry::getInstance()->set('namespace', 'functionName', $invalidCallable);
    }

    /**
     * @test
     *
     * @dataProvider provideNoStrings
     */
    public function throwWhenSetCallableWithInvalidFunctionName($invalidFunctionName)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid function name provided. Should be a string');

        $registry = CallbackRegistry::getInstance();

        $registry->set('namespace', $invalidFunctionName, function(){});
    }

    /**
     * @test
     *
     * @dataProvider provideNoStrings
     */
    public function throwWhenSetCallableWithInvalidNamespace($invalidNamespace)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid namespace provided. Should be a string');

        $registry = CallbackRegistry::getInstance();

        $registry->set($invalidNamespace, 'functionName', function(){});
    }

    /**
     * @test
     */
    public function shouldAllowToGetCallableByFunctionNameAndNamespace()
    {
        $functionName = 'functionFoo';
        $namespace = 'foo';
        $expectedCallable = function(){};

        $registry = CallbackRegistry::getInstance();
        $registry->set($namespace, $functionName, $expectedCallable);

        $actualCallable = $registry->get($namespace, $functionName);

        $this->assertSame($expectedCallable, $actualCallable);
    }

    /**
     * @test
     */
    public function throwWhenGetCallableNotSetBefore()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot find a callable related to foo_ns\bar_func()');

        $registry = CallbackRegistry::getInstance();

        $registry->get('foo_ns', 'bar_func');
    }

    /**
     * @test
     */
    public function shouldNotAllowToInstantiateViaConstructor()
    {
        $reflectionClass = new \ReflectionClass('Fumocker\CallbackRegistry');

        $reflectionConstructor = $reflectionClass->getConstructor();
        $this->assertInstanceOf('ReflectionMethod', $reflectionConstructor, 'Constructor method should be defined in the class');
        $this->assertFalse($reflectionConstructor->isPublic(), 'The constructor method should not have public access');
    }

    /**
     * @test
     */
    public function shouldNotAllowToClone()
    {
        $reflectionClass = new \ReflectionClass('Fumocker\CallbackRegistry');

        $reflectionClone = $reflectionClass->getMethod('__clone');
        $this->assertInstanceOf('ReflectionMethod', $reflectionClone, 'Clone method should be defined in the class');
        $this->assertFalse($reflectionClone->isPublic(), 'The clone method should not have public access');
    }

    /**
     * @test
     */
    public function shouldAllowToGetSingletonInstanceOfRegistry()
    {
        $registry = CallbackRegistry::getInstance();

        $this->assertInstanceOf('Fumocker\CallbackRegistry', $registry);
    }

    /**
     * @test
     */
    public function shouldAlwaysReturnTheSameInstance()
    {
        $registryOne = CallbackRegistry::getInstance();
        $registryTwo = CallbackRegistry::getInstance();

        $this->assertSame($registryOne, $registryTwo);
    }

    /**
     * @test
     */
    public function shouldAllowToGetAllDefinedCallables()
    {
        $expectedCallableFoo = function() {};
        $expectedNamespaceFoo = 'namespace\\foo';
        $expectedFunctionFoo = 'functionNameFoo';

        $expectedCallableBar = function() {};
        $expectedNamespaceBar = 'namespace\\bar';
        $expectedFunctionBar = 'functionNameBar';

        $registry = CallbackRegistry::getInstance();

        $registry->set($expectedNamespaceFoo, $expectedFunctionFoo, $expectedCallableFoo);
        $registry->set($expectedNamespaceBar, $expectedFunctionBar, $expectedCallableBar);

        $callables = $registry->getAll();

        $this->assertIsArray($callables);
        $this->assertCount(2, $callables);

        $this->assertIsArray($callables[0]);
        $this->assertIsArray($callables[1]);

        $expectedFirstCallable = array(
            'namespace' => $expectedNamespaceFoo,
            'function' => $expectedFunctionFoo,
            'callable' => $expectedCallableFoo
        );
        $this->assertEquals($expectedFirstCallable, $callables[0]);

        $expectedSecondCallable = array(
            'namespace' => $expectedNamespaceBar,
            'function' => $expectedFunctionBar,
            'callable' => $expectedCallableBar
        );
        $this->assertEquals($expectedSecondCallable, $callables[1]);
    }

    protected static function unsetCallbackRegistrySingleton()
    {
        $reflectionClass = new \ReflectionClass('Fumocker\CallbackRegistry');
        $reflectionProperty = $reflectionClass->getProperty('instance');

        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($reflectionClass, null);
        $reflectionProperty->setAccessible(false);
    }

    /**
     * @static
     *
     * @return array
     */
    public static function provideNoStrings()
    {
        return array(
            array(null),
            array(true),
            array(false),
            array(new \stdClass()),
            array(function() {}),
            array(-10),
            array(0),
            array(10),
            array(1.1),
        );
    }

    /**
     * @static
     *
     * @return array
     */
    public static function provideValidIdentifiers()
    {
        return array(
            array('a'),
            array('a1'),
            array(''),
            array('  '),
        );
    }

    /**
     * @static
     *
     * @return array
     */
    public static function provideValidCallbacks()
    {
        $staticMethod = array(__NAMESPACE__.'\StubMethodCall', 'staticMethod');
        $objectMethod = array(new StubMethodCall(), 'objectMethod');
        $closure = function() {};
        $function = 'is_callable';

        return array(
            array($staticMethod),
            array($objectMethod),
            array($closure),
            array($function),
        );
    }

    /**
     * @static
     *
     * @return array
     */
    public static function provideNoCallableItems()
    {
        return array(
            array('string'),
            array(1),
            array(12.2),
            array(array()),
            array(false),
            array(null),
            array(new \stdClass()),
            array(array(new \stdClass(), 'no_exist_method')),
            array(array('stdClass', 'no_exist_method')),
        );
    }
}

class StubMethodCall
{
  public static function staticMethod() {}

  public function objectMethod() {}
}