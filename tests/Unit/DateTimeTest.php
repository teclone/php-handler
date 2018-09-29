<?php
declare(strict_types = 1);

namespace Forensic\Handler\Test\Unit;

use PHPUnit\Framework\TestCase;
use Forensic\Handler\DateTime;

class DateTimeTest extends TestCase
{
    public function testConstruct()
    {
        $date_time = new DateTime();
        $this->assertInstanceOf(\DateTime::class, $date_time);
    }

    /**
     * test the default to string format
    */
    public function testDefaultToStringFormat()
    {
        $date_time = new DateTime();
        $this->assertEquals('Y-m-d', $date_time->getToStringFormat());
    }

    /**
     * test that the setToStringFormat works as expected
    */
    public function testSetToStringFormat()
    {
        $date_time = new DateTime();
        $date_time->setToStringFormat('Y-M-D');
        $this->assertEquals('Y-M-D', $date_time->getToStringFormat());
    }

    /**
     * test the toString method
    */
    public function testToString()
    {
        $date_time = new DateTime();
        $original_date_time = new \DateTime();

        $this->assertEquals(
            '' . $date_time,
            $original_date_time->format($date_time->getToStringFormat())
        );
    }
}