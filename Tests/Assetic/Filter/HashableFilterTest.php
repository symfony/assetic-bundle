<?php

namespace Symfony\Bundle\AsseticBundle\Tests\Assetic\Filter;

use Symfony\Bundle\AsseticBundle\Assetic\Filter\HashableFilter;

class HashableFilterTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider hashProvider
     * @param $value
     */
    public function testHash($value)
    {
        $filter = new HashableFilter($value);
        $this->assertEquals((string) $value, $filter->hash());
    }

    public static function hashProvider()
    {
        return array(
            array(10),
            array(99.99),
            array('foo'),
            array(true),
            array(false),
        );
    }
}
