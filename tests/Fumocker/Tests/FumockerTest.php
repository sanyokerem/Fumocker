<?php
namespace Fumocker\Tests;

use Fumocker\CallbackRegistry;
use Fumocker\Fumocker;
use Fumocker\MockGenerator;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FumockerTest extends TestCase
{
    /**
     * @test
     */
    public function throwWhileGettingMockOfNotExistGlobalFunction()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The global function with name `foo` does not exist.');

        $fumocker = new Fumocker(
            $this->createGeneratorMock(),
            $this->createCallbackRegistryMock()
        );

        $fumocker->getMock('Bar', 'foo');
    }

    /**
     * @test
     */
    public function shouldReturnPhpunitMockObjectWithMethodNamedAsGivenFunction()
    {
        $namespace = 'Bar';
        $function = 'mail';

        $generatorMock = $this->createGeneratorMock();
        $generatorMock
            ->expects($this->any())
            ->method('generate')
        ;

        $registryMock = $this->createCallbackRegistryMock();
        $registryMock
            ->expects($this->any())
            ->method('set')
        ;

        $fumocker = new Fumocker($generatorMock, $registryMock);

        $functionMockObject = $fumocker->getMock($namespace, $function);

        $this->assertInstanceOf(MockObject::class, $functionMockObject);
        $this->assertTrue(method_exists($functionMockObject, $function));
    }

    /**
     * @test
     */
    public function shouldGenerateFunctionMockIfNotGenerated()
    {
        $namespace = 'Bar';
        $function = 'mail';

        $generatorMock = $this->createGeneratorMock();
        $generatorMock
            ->expects($this->once())
            ->method('generate')
            ->with(
                $this->equalTo($namespace),
                $this->equalTo($function)
            )
        ;
        $generatorMock
            ->expects($this->once())
            ->method('hasGenerated')
            ->will($this->returnValue(false))
        ;

        $registryMock = $this->createCallbackRegistryMock();
        $registryMock
            ->expects($this->any())
            ->method('set')
        ;

        $fumocker = new Fumocker($generatorMock, $registryMock);

        $fumocker->getMock($namespace, $function);
    }

    /**
     * @test
     */
    public function shouldNotGenerateFunctionMockIfAlreadyGenerated()
    {
        $namespace = 'Bar';
        $function = 'mail';

        $generatorMock = $this->createGeneratorMock();
        $generatorMock
            ->expects($this->never())
            ->method('generate')
        ;
        $generatorMock
            ->expects($this->once())
            ->method('hasGenerated')
            ->will($this->returnValue(true))
        ;

        $registryMock = $this->createCallbackRegistryMock();
        $registryMock
            ->expects($this->any())
            ->method('set')
        ;

        $fumocker = new Fumocker($generatorMock, $registryMock);

        $fumocker->getMock($namespace, $function);
    }

    /**
     * @test
     */
    public function shouldSetPhpunitMockObjectToCallBackRegistryAsCallable()
    {
        $namespace = 'Bar';
        $function = 'mail';

        $generatorMock = $this->createGeneratorMock();
        $generatorMock
            ->expects($this->any())
            ->method('generate')
        ;


        $checker = new \stdClass;
        $checker->actualCallable = null;

        $registryMock = $this->createCallbackRegistryMock();
        $registryMock
            ->expects($this->once())
            ->method('set')
            ->will($this->returnCallback(function($namespace, $function, $callable) use ($checker) {
                $checker->actualNamespace = $namespace;
                $checker->actualFunction = $function;
                $checker->actualCallable = $callable;
            }))
        ;

        $fumocker = new Fumocker($generatorMock, $registryMock);

        $functionMock = $fumocker->getMock($namespace, $function);

        $this->assertEquals($namespace, $checker->actualNamespace);
        $this->assertEquals($function, $checker->actualFunction);
        $this->assertSame(array($functionMock, $function), $checker->actualCallable);
    }

    /**
     * @test
     */
    public function shouldCleanupAllMockedFunctionBySettingGlobalFunctionAsCallable()
    {
        $firstNamespace = 'Foo';
        $firstFunctionName = 'mail';
        $firstCallable = function() {};

        $secondNamespace = 'Bar';
        $secondFunctionName = 'file_get_contents';
        $secondCallable = function() {};

        //guard
        $generatorMock = $this->createGeneratorMock();
        $generatorMock
            ->expects($this->never())
            ->method('generate')
        ;

        $registryMock = $this->createCallbackRegistryMock();
        $registryMock
            ->expects($this->once())
            ->method('getAll')
            ->will($this->returnValue(array(
                array(
                    'namespace' => $firstNamespace,
                    'function' => $firstFunctionName,
                    'callable' => $firstCallable,
                ),
                array(
                    'namespace' => $secondNamespace,
                    'function' => $secondFunctionName,
                    'callable' => $secondCallable
                ),
            )))
        ;
        $registryMock
            ->expects($this->exactly(2))
            ->method('set')
            ->willReturnOnConsecutiveCalls(
                $this->returnCallback(function (
                    string $namespace,
                    string $functionName,
                    string $callable
                ) use ($firstNamespace, $firstFunctionName) {
                    $this->assertEquals($namespace, $firstNamespace);
                    $this->assertEquals($functionName, $firstFunctionName);
                    $this->assertEquals($callable, $firstFunctionName);
                }),
                $this->returnCallback(function (
                    string $namespace,
                    string $functionName,
                    string $callable
                ) use ($secondNamespace, $secondFunctionName) {
                    $this->assertEquals($namespace, $secondNamespace);
                    $this->assertEquals($functionName, $secondFunctionName);
                    $this->assertEquals($callable, $secondFunctionName);
                })
            )
        ;

        $fumocker = new Fumocker($generatorMock, $registryMock);

        $fumocker->cleanup();
    }

    /**
     * @test
     *
     * @depends shouldReturnPhpunitMockObjectWithMethodNamedAsGivenFunction
     */
    public function shouldVerifyFunctionMockThatItCalledOneTimeWhenInRealNeverCalled()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Method was expected to be called 1 times, actually called 0 times.');

        $registryMock = $this->createCallbackRegistryMock();
        $registryMock
            ->expects($this->any())
            ->method('getAll')
            ->will($this->returnValue(array()))
        ;

        $fumocker = new Fumocker($this->createGeneratorMock(), $registryMock);

        $functionMock = $fumocker->getMock('Bar', 'mail');

        $functionMock->expects($this->once())->method('mail');

        $fumocker->cleanup();
    }

    /**
     * @test
     *
     * @depends shouldReturnPhpunitMockObjectWithMethodNamedAsGivenFunction
     * @depends shouldVerifyFunctionMockThatItCalledOneTimeWhenInRealNeverCalled
     */
    public function shouldNotVerifyFunctionMockTwice()
    {
        $this->expectNotToPerformAssertions();

        $registryMock = $this->createCallbackRegistryMock();
        $registryMock
            ->expects($this->any())
            ->method('getAll')
            ->will($this->returnValue([]))
        ;

        $fumocker = new Fumocker($this->createGeneratorMock(), $registryMock);

        $functionMock = $fumocker->getMock('Bar', 'mail');

        $functionMock->expects($this->once())->method('mail');

        try {
            $fumocker->cleanup();

            $this->fail('Cleanup should throw verify exception');
        } catch (ExpectationFailedException $e) { }

        $fumocker->cleanup();
    }

    /**
     * @return \Fumocker\CallbackRegistry|MockObject
     */
    protected function createCallbackRegistryMock()
    {
        return $this
            ->getMockBuilder(CallbackRegistry::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['set', 'get', 'getAll'])
            ->getMock();
    }

    /**
     * @return MockGenerator
     */
    protected function createGeneratorMock()
    {
        return $this
            ->getMockBuilder(MockGenerator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['generate', 'hasGenerated'])
            ->getMock();
    }
}
