<?php
/*  Usage:
    Download phar from https://phar.phpunit.de/
    chmod +x phpunit-???.phar
    ln -s phpunit-???.phar phpunit
    phpunit CalendarTest.php
*/


require __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "Calendar.php";

use PHPUnit\Framework\TestCase;

class CalendarTest extends TestCase {
	public function testDayOfWeek() {
		$c = Calendar::parse("2019-10-01");
		$c->setWeekdayInMonth( Calendar::THURSDAY, 3);
		$this->assertEquals('2019-10-17', $c->toString('Y-m-d'));

		$c = Calendar::parse("2019-09-01");
		$c->setWeekdayInMonth( Calendar::MONDAY, 1);
		$this->assertEquals('2019-09-02', $c->toString('Y-m-d'));

		$c = Calendar::parse("2019-05-01");
		$c->setWeekdayInMonth( Calendar::MONDAY, -1);
		$this->assertEquals('2019-05-27', $c->toString('Y-m-d'));

		$c = Calendar::parse("2021-11-01");
		$c->setWeekdayInMonth( Calendar::THURSDAY, 4);
		$this->assertEquals('2021-11-25', $c->toString('Y-m-d'));

// 		try { 
// 			$c = Calendar::parse("2019-02-01");
// 			print "This should throw an error: ";
// 			$c->setWeekdayInMonth( Calendar::MONDAY, 6);
// 			print "Is last Monday of May 2019 the 27th? " . $c->toString() . "\n";
// 		} catch( Exception $e ) {
// 			print "Caught exception $e" . "\n";
// 		}
	}
	
	public function testClear() {
		$c = new Calendar();
		$this->assertNotEquals( null, $c->get( Calendar::DAY ));
		$c->clear( Calendar::DAY );
		$this->assertEquals( null, $c->get( Calendar::DAY ));
		$c->clear( );
		$this->assertEquals( null, $c->get( Calendar::YEAR ));
	}	
	
	public function testArithmetic() {		
		$c = Calendar::parse("1/31/2022");
		$c->add( Calendar::DAY, 1 );
		$this->assertEquals( "2022-02-01", $c->toString('Y-m-d'));
		$c->add( Calendar::MONTH, 2 );
		$this->assertEquals( "2022-04-01", $c->toString('Y-m-d'));
		
		$c2 = Calendar::parse("1/31/2022");
		$c2->add( Calendar::MONTH, 1 );
		$this->assertEquals( "2022-02-28", $c2->toString('Y-m-d'));
		$c2->add( Calendar::MONTH, 1 );
		$this->assertEquals( "2022-03-31", $c2->toString('Y-m-d'));
		$c2->add( Calendar::MONTH, 1 );
		$this->assertEquals( "2022-04-30", $c2->toString('Y-m-d'));
		$c2->add( Calendar::MONTH, 1 );
		$this->assertEquals( "2022-05-31", $c2->toString('Y-m-d'));
		$c2->add( Calendar::MONTH, 1 );
		$this->assertEquals( "2022-06-30", $c2->toString('Y-m-d'));
		$c2->add( Calendar::MONTH, 1 );
		$this->assertEquals( "2022-07-31", $c2->toString('Y-m-d'));
		$c2->add( Calendar::MONTH, 1 );
		$this->assertEquals( "2022-08-31", $c2->toString('Y-m-d'));
		$c2->add( Calendar::MONTH, 1 );
		$this->assertEquals( "2022-09-30", $c2->toString('Y-m-d'));
		$c2->add( Calendar::MONTH, 1 );
		$this->assertEquals( "2022-10-31", $c2->toString('Y-m-d'));
		$c2->add( Calendar::MONTH, 1 );
		$this->assertEquals( "2022-11-30", $c2->toString('Y-m-d'));
		$c2->add( Calendar::MONTH, 1 );
		$this->assertEquals( "2022-12-31", $c2->toString('Y-m-d'));
		$c2->add( Calendar::MONTH, 1 );
		$this->assertEquals( "2023-01-31", $c2->toString('Y-m-d'));
		

	}
		
	public function testComparisons() {		
		// Test 'after()'
		$c = Calendar::parse("2021-11-01");
		$d = Calendar::parse("2000-09-01");
		$this->assertTrue( $c->after( $d ));
		$this->assertFalse( $d->after( $c ));
		
		// Test 'before()'
		$this->assertTrue( $d->before( $c ));
		$this->assertFalse( $c->before( $d ));
		
		// Test 'compareTo()'
		$this->assertLessThan( 0, $d->compareTo( $c ));
		$this->assertGreaterThan( 0, $c->compareTo( $d ));

		// Test 'equals()' 'compareTo()' and 'clone()'
		$epoch_count = time();
		$eq1 = new Calendar($epoch_count);
		$eq2 = new Calendar($epoch_count);
		$eq3 = $eq1->clone();
		$this->assertTrue( $eq1->equals( $eq2 ) );
		$this->assertTrue( $eq1->equals( $eq3 ) );
		$this->assertTrue( $eq2->equals( $eq3 ) );
		$this->assertEquals( 0, $eq1->compareTo( $eq2 ) );	
		$this->assertEquals( 0, $eq1->compareTo( $eq3 ) );	
		$this->assertEquals( 0, $eq2->compareTo( $eq3 ) );
		
		// Test date comparisons
		$c = Calendar::parse("1/5/2022");
		$c->set( Calendar::HOUR, 23 );
		$d = Calendar::parse("1/5/2022");
		$d->set( Calendar::HOUR, 14 );
		$this->assertTrue( $c->equalsDate( $d ) );
		$this->assertEquals( 0, $c->compareToDate( $d ) );

		$c = Calendar::parse("11/1/2020");
		$d = Calendar::parse("11/3/2020");
		$this->assertTrue( $c->beforeDate( $d ) );
		$this->assertFalse( $d->beforeDate( $c ) );
		$this->assertFalse( $c->afterDate( $d ) );
		$this->assertTrue( $d->afterDate( $c ) );
		$this->assertFalse( $c->equalsDate( $d ) );
	}
	
	public function testDateCalculator() {
		
		// Should be 44 years, 11 months, 20 days between these dates:
		$c = Calendar::parse("1/31/1977");
		$d = Calendar::parse("1/20/2022");
		$cd = Calendar::calculateDuration($c, $d, Calendar::YEAR, Calendar::DAY);
		$this->assertEquals(44, $cd->get(Calendar::YEAR));
		$this->assertEquals(11, $cd->get(Calendar::MONTH));
		$this->assertEquals(20, $cd->get(Calendar::DAY));
		//	 Or 539 months, 20 days
		$cd = Calendar::calculateDuration($c, $d, Calendar::MONTH, Calendar::DAY);
		$this->assertEquals(539, $cd->get(Calendar::MONTH));
		$this->assertEquals(20, $cd->get(Calendar::DAY));
		//	 Or 16,425 days:
		$cd = Calendar::calculateDuration($c, $d, Calendar::DAY, Calendar::DAY);
		$this->assertEquals(16425, $cd->get(Calendar::DAY));

		// 	1/20/2022	to	12/25/2022	11 months, 5 days (339 days)
		$c = Calendar::parse("1/20/2022");
		$d = Calendar::parse("12/25/2022");
		$cd = Calendar::calculateDuration($c, $d, Calendar::YEAR, Calendar::DAY);
		$this->assertEquals(0, $cd->get(Calendar::YEAR));
		$this->assertEquals(11, $cd->get(Calendar::MONTH));
		$this->assertEquals(5, $cd->get(Calendar::DAY));
		$cd = Calendar::calculateDuration($c, $d, Calendar::DAY, Calendar::DAY);
		$this->assertEquals(339, $cd->get(Calendar::DAY));
		
		//	1/20/2022	to	1/31/2027	5 years, 11 days	60 months, 11 days	1837 days
		$c = Calendar::parse("1/20/2022");
		$d = Calendar::parse("1/31/2027");
		$cd = Calendar::calculateDuration($c, $d, Calendar::YEAR, Calendar::DAY);
		$this->assertEquals(5, $cd->get(Calendar::YEAR));
		$this->assertEquals(0, $cd->get(Calendar::MONTH));
		$this->assertEquals(11, $cd->get(Calendar::DAY));
		$cd = Calendar::calculateDuration($c, $d, Calendar::DAY, Calendar::DAY);
		$this->assertEquals(1837, $cd->get(Calendar::DAY));
		$cd = Calendar::calculateDuration($c, $d, Calendar::MONTH, Calendar::DAY);
		$this->assertEquals(60, $cd->get(Calendar::MONTH));
		$this->assertEquals(11, $cd->get(Calendar::DAY));

		// 2/1/2020 -> 3/1/2020 = 29 days (leap year)
		$c = Calendar::parse("2/1/2020");
		$d = Calendar::parse("3/1/2020");
		$cd = Calendar::calculateDuration($c, $d, Calendar::DAY, Calendar::DAY);
		$this->assertEquals(29, $cd->get(Calendar::DAY));

		// 2/1/2021 -> 3/1/2021 = 28 days (non-leap year)
		$c = Calendar::parse("2/1/2021");
		$d = Calendar::parse("3/1/2021");
		$cd = Calendar::calculateDuration($c, $d, Calendar::DAY, Calendar::DAY);
		$this->assertEquals(28, $cd->get(Calendar::DAY));

		// 2/1/2000 -> 3/1/2000 = 29 days (leap year)
		$c = Calendar::parse("2/1/2000");
		$d = Calendar::parse("3/1/2000");
		$cd = Calendar::calculateDuration($c, $d, Calendar::DAY, Calendar::DAY);
		$this->assertEquals(29, $cd->get(Calendar::DAY));

		// 2/1/1900 -> 3/1/1900 = 28 days (non-leap year)
		$c = Calendar::parse("2/1/1900");
		$d = Calendar::parse("3/1/1900");
		$cd = Calendar::calculateDuration($c, $d, Calendar::DAY, Calendar::DAY);
		$this->assertEquals(28, $cd->get(Calendar::DAY));
	}
}
?>