<?php
namespace Fumocker\Tests;

use Fumocker\Fumocker;
use PHPUnit\Framework\TestCase;

class FumockerIntegrationTest extends TestCase
{
    /**
     * @var \Fumocker\Fumocker
     */
    protected $fumocker;

    protected function setUp(): void
    {
        $this->fumocker = new Fumocker();
    }

    protected function tearDown(): void
    {
        $this->fumocker->cleanup();
    }

    /**
     * @test
     */
    public function shouldMockRangeFunctionAndUseItsMock()
    {
        $functionMock = $this->fumocker->getMock(__NAMESPACE__, 'range');
        $functionMock
            ->expects($this->once())
            ->method('range')
            ->with(
                $this->equalTo(4),
                $this->equalTo(5)
            )
            ->will($this->returnValue(array(2, 3)))
        ;

        $result = range(4, 5);

        $this->assertEquals(array(2, 3), $result);
    }

    /**
     * @test
     */
    public function shouldCleanupAndUseGlobalFunctionAsCallable()
    {
        $this->assertEquals(array(4, 5), range(4, 5));
    }
}
