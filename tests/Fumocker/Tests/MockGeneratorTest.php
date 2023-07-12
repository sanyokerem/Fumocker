<?php
namespace Fumocker\Tests;

use Fumocker\MockGenerator;
use Fumocker\CallbackRegistry;
use PHPUnit\Framework\TestCase;

class MockGeneratorTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider provideNotStringTypes
     */
    public function throwWhenFunctionNameNotStringWhileGeneration($invalidFunctionName)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid function name provided');

        $generator = new MockGenerator();

        $generator->generate('namespace', $invalidFunctionName);
    }

    /**
     * @test
     *
     * @dataProvider provideEmpties
     */
    public function throwWhenFunctionEmptyWhileGeneration($emptyFunctionName)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Given function name is empty');

        $generator = new MockGenerator();

        $generator->generate('namespace', $emptyFunctionName);
    }

    /**
     * @test
     *
     * @dataProvider provideNotStringTypes
     */
    public function throwWhenNamespaceNotStringWhileGeneration($invalidNamespace)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid namespace provided');

        $generator = new MockGenerator();

        $generator->generate($invalidNamespace, 'function');
    }

    /**
     * @test
     *
     * @dataProvider provideEmpties
     */
    public function throwWhenNamespaceEmptyWhileGeneration($emptyNamespace)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Given namespace is empty');

        $generator = new MockGenerator();

        $generator->generate($emptyNamespace, 'function');
    }


    /**
     * @test
     */
    public function shouldAllowToCheckWhetherFunctionMocked()
    {
        $generator = new MockGenerator();

        $this->assertTrue($generator->hasGenerated(__NAMESPACE__, 'mocked_function'), 'Should be mocked function');
    }

    /**
     * @test
     */
    public function shouldAllowToCheckWhetherFunctionMockedOrUserDefined()
    {
        $generator = new MockGenerator();

        $this->assertFalse($generator->hasGenerated(__NAMESPACE__, 'user_defined_function'), 'Should be user defined function');
    }

    /**
     * @test
     */
    public function throwIfUserAlreadyDefineFunctionInTheNamespace()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The function `user_defined_function` in the namespace `Fumocker\Tests` has already been defined by a user');

        $generator = new MockGenerator();

        $generator->generate(__NAMESPACE__, 'user_defined_function');
    }

    /**
     * @test
     */
    public function throwIfMockedFunctionAlreadyGeneratedInTheNamespace()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The function `mocked_function` in the namespace `Fumocker\Tests` has been already mocked');

        $generator = new MockGenerator();

        $generator->generate(__NAMESPACE__, 'mocked_function');
    }

    /**
     * @test
     */
    public function shouldGenerateMockedFunction()
    {
        //guard
        $this->assertFunctionNotExists(__NAMESPACE__, 'test_generate_function_mock');

        $generator = new MockGenerator();

        $generator->generate(__NAMESPACE__, 'test_generate_function_mock');

        $this->assertFunctionExists(__NAMESPACE__, 'test_generate_function_mock');
        $this->assertTrue($generator->hasGenerated(__NAMESPACE__, 'test_generate_function_mock'));
    }

    /**
     * @test
     */
    public function shouldGenerateConstantWhileGeneratingFunctionMock()
    {
        //guard
        $this->assertFunctionNotExists(__NAMESPACE__, 'test_set_identifier');

        $generator = new MockGenerator();

        $generator->generate(__NAMESPACE__, 'test_set_identifier');

        $mockedFunctionConstant = __NAMESPACE__ . '\\' . '__FUMOCKER_TEST_SET_IDENTIFIER';
        $this->assertTrue(defined($mockedFunctionConstant));
    }

    /**
     * @test
     */
    public function shouldRedirectMockedFunctionCallToAssignedCallable()
    {
        //guard
        $this->assertFunctionNotExists(__NAMESPACE__, 'test_redirect_call_to_callable');

        $mockCallable = $this
            ->getMockBuilder(\stdClass::class)
            ->addMethods(['__invoke'])
            ->getMock();
        $mockCallable
            ->expects($this->once())
            ->method('__invoke')
        ;

        $generator = new MockGenerator();

        $generator->generate(__NAMESPACE__, 'test_redirect_call_to_callable');
        CallbackRegistry::getInstance()->set(__NAMESPACE__, 'test_redirect_call_to_callable', $mockCallable);

        $this->assertFunctionExists(__NAMESPACE__, 'test_redirect_call_to_callable');

        test_redirect_call_to_callable();
    }

    /**
     * @test
     */
    public function shouldProxyMockedFunctionArgumentsToCallable()
    {
        //guard
        $this->assertFunctionNotExists(__NAMESPACE__, 'test_proxy_arguments_to_callable');

        $expectedFirstArgument = 'foo';
        $expectedSecondArgument = array('bar');
        $expectedThirdArgument = new \stdClass();

        $mockCallable = $this
            ->getMockBuilder(\stdClass::class)
            ->addMethods(['__invoke'])
            ->getMock();
        $mockCallable
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->equalTo($expectedFirstArgument),
                $this->equalTo($expectedSecondArgument),
                $this->equalTo($expectedThirdArgument)
            )
        ;

        $generator = new MockGenerator();

        $generator->generate(__NAMESPACE__, 'test_proxy_arguments_to_callable');
        CallbackRegistry::getInstance()->set(__NAMESPACE__, 'test_proxy_arguments_to_callable', $mockCallable);

        $this->assertFunctionExists(__NAMESPACE__, 'test_proxy_arguments_to_callable');

        test_proxy_arguments_to_callable($expectedFirstArgument, $expectedSecondArgument, $expectedThirdArgument);
    }

    /**
     * @test
     */
    public function shouldReturnCallableResultAsMockedFunction()
    {
        //guard
        $this->assertFunctionNotExists(__NAMESPACE__, 'test_return_callable_result');

        $expectedResult = 'foo';

        $mockCallable = $this
            ->getMockBuilder(\stdClass::class)
            ->addMethods(['__invoke'])
            ->getMock();
        $mockCallable
            ->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($expectedResult))
        ;

        $generator = new MockGenerator();

        $generator->generate(__NAMESPACE__, 'test_return_callable_result');
        CallbackRegistry::getInstance()->set(__NAMESPACE__, 'test_return_callable_result' ,$mockCallable);

        $this->assertFunctionExists(__NAMESPACE__, 'test_return_callable_result');

        $this->assertEquals($expectedResult, test_return_callable_result());
    }

    public function assertFunctionExists($namespace, $functionName)
    {
        $this->assertTrue(function_exists($namespace . '\\' . $functionName));
    }

    public function assertFunctionNotExists($namesppace, $functionName)
    {
        $this->assertFalse(function_exists($namesppace . '\\' . $functionName));
    }

    /**
     * @static
     *
     * @return array
     */
    public static function provideNotStringTypes()
    {
        return array(
            array(123),
            array(new \stdClass()),
            array(array()),
            array(null),
        );
    }

    /**
     * @static
     *
     * @return array
     */
    public static function provideEmpties()
    {
        return array(
            array(''),
            array('  '),
        );
    }
}

function user_defined_function()
{

}

const __FUMOCKER_MOCKED_FUNCTION = 1;

function mocked_function()
{

}
