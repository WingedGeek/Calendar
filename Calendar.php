<?php
/**
	2021.12.27 wingedgeek heavily influenced by Java's Calendar module
    PHP 7.1+
 	Internally uses PHP's time() (seconds since epoch) and GMT:	https://www.php.net/manual/en/function.time.php
 	A lot of this is probably redundant with DateTimeImmutable and DateInterval and I should probably re-implement this as essentially an abstraction layer.
 	Don't use this code for anything. :)

*/

// TODO timezone support	https://www.php.net/manual/en/datetimezone.gettransitions.php https://www.php.net/manual/en/class.datetimezone.php

class Calendar {
	private $_year;
	private $_month;
	private $_day;
	private $_hour;
	private $_minute;
	private $_second;

	private $_startMonth;	// Used for leap year arithmetic
	private $_startDay;		// Used for leap year arithmetic

	
	private $_epoch_timestamp;
	public const YEAR = 1000;
	public const MONTH = 1001;
	public const DAY = 1002;
	public const HOUR = 1003;
	public const MINUTE = 1004;
	public const SECOND = 1005;
	public const DAYOFWEEK = 1006;
	
	public const SUNDAY = 0;
	public const MONDAY = 1;
	public const TUESDAY = 2;
	public const WEDNESDAY = 3;
	public const THURSDAY = 4;
	public const FRIDAY = 5;
	public const SATURDAY = 6;

	public const JANUARY = 1;
	public const FEBRUARY = 2;
	public const MARCH = 3;
	public const APRIL = 4;
	public const MAY = 5;
	public const JUNE = 6;
	public const JULY = 7;
	public const AUGUST = 8;
	public const SEPTEMBER = 9;
	public const OCTOBER = 10;
	public const NOVEMBER = 11;
	public const DECEMBER = 12;


	private $_weekdays = [ "SUNDAY", "MONDAY", "TUESDAY", "WEDNESDAY", "THURSDAY", "FRIDAY", "SATURDAY" ];
	
	private $_firstDayOfWeek = SELF::SUNDAY;
		
	/**
	 * Constructor, creates a new Calendar object.
	 *
	 * This class is loosely based on the Java Calendar class. This constructor calls the class method computeFields().
	 *
	 * @param int $timestamp Seconds since epoch. If null, sets to *now* (the value returned by PHP's internal time() function).
	 *
	 * @return void
	 */
	public function __construct( int $timestamp = null ) {
		if ($timestamp === null) {
			$this->_epoch_timestamp = time();
		} else {
			$this->_epoch_timestamp = $timestamp;
		}
		$this->computeFields();
		$this->_startMonth = $this->_month;
		$this->_startDay = $this->_day;
	}
		
	/**
	 * Parses a date string.
	 *
	 * @param string $dateString Any string that can be parsed by PHP's \DateTime constructor is valid here (e.g., "9/27/2022" or "September 27, 2022").
	 *
	 * @return self
	 */
	public static function parse( $dateString ) {
		$dt = new DateTime($dateString);
		return new Calendar( $dt->getTimestamp() );		
	}

	/**
	 * Based on the current seconds-since-epoch value,determine the date and time. Changes internal state only.
	 *
	 * @return void
	 */
	public function computeFields() {
		//
// 		print "Computing fields for epoch timestamp: " . $this->epoch_timestamp . "\n";
// 		print "Y-m-d G:i:s: " . $this->toString("Y-m-d G:i:s") . "\n"; 

		$this->_year = (int)date("Y", $this->_epoch_timestamp);
		$this->_month = (int)date("m", $this->_epoch_timestamp);	// 
		$this->_day = (int)date("d", $this->_epoch_timestamp);
		$this->_hour = (int)date("G", $this->_epoch_timestamp);	// 24 hour no leading 0
		$this->_minute = (int)date("i", $this->_epoch_timestamp);
		$this->_second = (int)date("s", $this->_epoch_timestamp);
	}

	/**
	 * Private function; (re)sets the internal seconds-since-epoch value based on the current date/time values.
	 */
	private function updateEpoch() {
		$date = $this->_year . "-" . sprintf("%02d", $this->_month) . "-" . sprintf("%02d", $this->_day) . " " . sprintf("%02d", $this->_hour) . ":" . sprintf("%02d", $this->_minute) . ":" . sprintf("%02d", $this->_second);
		
		$dt = new DateTime($date);

		$this->_epoch_timestamp = $dt->getTimestamp();
	}	
	
	/**
	 * Returns the value of the given Calendar field.
	 *
	 * @param int $field The Calendar field to return (e.g., Calendar::YEAR).
	 *
	 * @return mixed The value of the supplied Calendar field (e.g., 2022).
	 */
	public function get(int $field ) {
		switch( $field ) {
			case Calendar::YEAR:
				return $this->_year;
				break;
			case Calendar::MONTH:
				return $this->_month;
				break;
			case Calendar::DAY:
				return $this->_day;
				break;
			case Calendar::HOUR:
				return $this->_hour;
				break;
			case Calendar::MINUTE:
				return $this->_minute;
				break;
			case Calendar::SECOND:
				return $this->_second;
				break;
			case Calendar::DAYOFWEEK:
				return (int)$this->toString('w');
				break;
			}
	}
	
	/**
	 * Sets a Calendar field to the supplied value. Optionally forces an update to the seconds-since-epoch internal value.
	 *
	 * @param int $field A Calendar field (e.g., Calendar::YEAR).
	 * @param int $value The new value (e.g., 2022).
	 * @param bool $updateEpoch (optional) Update the seconds-since-epoch value? Defaults to true.
	 *
	 * @return void
	 */
	public function set(int $field, int $value, bool $updateEpoch = true ) {
		$v = ($value < 0) ? null : $value;
		
		switch( $field ) {
			case Calendar::YEAR:
				$this->_year = $v;
				break;
			case Calendar::MONTH:
				$this->_month = $v;
				break;
			case Calendar::DAY:
				$this->_day = $v;
				break;
			case Calendar::HOUR:
				$this->_hour = $v;
				break;
			case Calendar::MINUTE:
				$this->_minute = $v;
				break;
			case Calendar::SECOND:
				$this->_second = $v;
				break;
		}
		
		if( $updateEpoch ) {
			$this->updateEpoch();
		}
	}

	/**
	 * Sets the weekday in a month, e.g., the 1st Monday or the last Friday.
	 *
	 * If the count is negative, search backwards from the end of the month.
	 * Otherwise, start from the first of the month and find the $count instance of the specified weekday.
	 * Examples: Calendar::THURSDAY, 4 - the 4th Thursday (e.g., Thanksgiving)
	 *   Calendar::MONDAY, 1 - the first Monday.
	 *   Calendar::MONDAY, -1 - the last Monday.
	 *
	 * @param int $dayofweek A Calendar constant (e.g., Calendar::MONDAY)
	 * @param int $count The number of the weekday (e.g. 2 for the 2nd Monday; -1 for the last Monday, in a month)
	 *
	 * @return void
	 */
	public function setWeekdayInMonth( int $dayofweek, int $count ) {
		$date_weekday = $dayofweek;	// 0 = Sunday etc. 'w'
		$tcal = $this->clone();
		$endcal = $this->clone();
		// Default: Count forward, starting on the 1st:
		$offset = 1;
		$tcal->set(Calendar::DAY, 1);
		$endcal->set(Calendar::DAY, $this->daysInMonth() );
		
		// Override if counting backwards:
		if ($count < 0) {
			$offset = -1;
			$tcal->set(Calendar::DAY, $this->daysInMonth() );
			$endcal->set(Calendar::DAY, 1);
		}
		
		$foundcount = 0;
		do {
			
			if ( $tcal->get(Calendar::DAYOFWEEK) == $date_weekday) {
				$foundcount++;
				if ($foundcount == abs($count)) {
					$this->set( Calendar::DAY, $tcal->get(Calendar::DAY));
					return;
				}
			}
			$tcal->add( Calendar::DAY, $offset );
			
		} while( $tcal->compareTo( $endcal ) != 0);
		// If we've gotten here, no date matches the $count instance of $weekday
		throw new Exception("Invalid specification: No [" . $count . "] instance of Calendar::" . $this->_weekdays[ $date_weekday] . " in " . $this->toString('F Y'));
	}

	// Precision start: The largest unit to count
	// Precision end:	The smallest.
	//	E.g., Calendar::YEAR, Calendar::DAY will provide years, months, and days, but not hours, minutes, or seconds.
	//	Calendar::DAY, Calendar::DAY will provide a straight day count, no years or months, or hours, minutes, or seconds.
	/**
	 * Calculates the duration between two Calendar objects.
	 * 
	 * The precision_start and precision_end values determine how 
	 *
	 * @param Calendar $a The first Calendar object
	 * @param Calendar $b The second Calendar object
	 * @param int $precision_start 
	 *
	 *
	 *
	 */
	public static function calculateDuration( Calendar $a, Calendar $b, int $precision_start = Calendar::YEAR, int $precision_end = Calendar::SECOND) {
		// TODO rewrite this to return a class that includes whatever values (0 if beyond the duration; e.g., July 2 to July 4 won't have week, month, year, set...)
		$retval = new CalendarDuration();
		if ( $a->compareTo($b) == 0 ) {
			return $retval;
		}
		
		$pointer = $a->before($b) ? $a : $b;
		$end = $a->before($b) ? $b : $a;
		
// 		print "Starting at " . $pointer->toString() . "\n";
// 		print "Counting to " . $end->toString() . "\n";	
		
		$values = [ 
			Calendar::YEAR,
			Calendar::MONTH,
			Calendar::DAY,
			Calendar::HOUR,
			Calendar::MINUTE,
			Calendar::SECOND,
		];
		
		foreach($values as $const) {
// 			print "Comparing $const to < $precision_start ...\n";
			if ($const < $precision_start) {
// 				print "\tContinue;\n";
				continue;
			} else if ($const > $precision_end) {
// 				print "Breaking ($const > $precision_end)\n";
				break;
			}
			
// 			print "Counting '$const'\n";
// 			print "---------------\n";
			
			$placeholder = $pointer->clone();
			$placeholder->add($const, 1);
// 			print "Comparing PH[" . $placeholder->toString("Y-m-d H:i:s") . "] to end[" . $end->toString("Y-m-d H:i:s") . "]\n";
// 			print "\t" . $placeholder->compareTo($end) . "\n";
			if ($placeholder->compareTo($end) <= 0) {
				do {
					$retval->increment($const);
//  					print_r($retval);
					$pointer = $placeholder->clone();
// 					print "\tPointer now: " . $pointer->toString() . "\n";
					$placeholder->add($const, 1);
// 					print "*****\n\t\tPH: " . $placeholder->toString("Y-m-d H:i:s") . "\n";
// 					print "\t\tPtr " . $pointer->toString("Y-m-d H:i:s") . "\n";
// 					sleep(2);
// 					print "End of do ... while(); test now PH[" . $placeholder->toString("Y-m-d H:i:s") . "] to end[" . $end->toString("Y-m-d H:i:s") . "]\n";
// 					print "\t" . $placeholder->compareTo($end) . "\n";
				} while($placeholder->compareTo($end) <= 0);
			}
		}
		
		#fnord		
		return $retval;
		
	}

	





	// abstract void 	add(int field, int amount)
	// Adds or subtracts the specified amount of time to the given calendar field, based on the calendar's rules.
	/**
	 * Adds the supplied value to the specified field.
	 *
	 * //TODO Document edge cases (leap years, etc)
	 *
	 * @param int $field The field to perform addition on (e.g. Calendar::YEAR)
	 * @param int $value The value to add to the specified field, use a negative number to go backwards in time (e.g., for a Calendar object with the YEAR value 2022, add( Calendar::YEAR, 1) goes to 2023, while -1 rolls it back to 2021.
	 *
	 * @return void
	 */
	public function add( int $field, int $value ) {
// 		print "... add( $field, $value) called ...\n";
		if ($value == 0) {
			return;
		}

		// Count by -1 if subtracting:
		$increment = ($value < 0) ? -1 : 1;

		if ($field == Calendar::YEAR) {
			// Years are easy; just add/subtract one.
			$this->_year += $value;
			// print "Now: " . $this->year . "\n";
			
			// Special case: February and last day of leap year month, reset to # of days in this month
			if ($this->_month == 2) {	// February. 				
				$this->resetToLastDayOfMonth();
			}
			$this->updateEpoch();
// 			print "Year added; now " . $this->toString("Y-m-d H:i:s") . "\n";
			return;
		}

		if ($field == Calendar::MONTH) {
			// Subtract from the month, if < 1 roll over to 12 and subtract year.
			// At the end adjust last day of month if exceeded (e.g. to a 28 day month from a 31 day month)
			for($i = 0; $i < abs($value); $i++) {
				$this->_month += $increment;
				if ($this->_month < 1) {
					$this->_year--;
					$this->_month = 12;
				} elseif ( $this->_month > 12) {
					$this->_year++;
					$this->_month = 1;
				}
			}
			$last_day = $this->daysInMonth();
			if ($this->_startDay > $last_day) {
				$this->_day = $last_day;
			} else {
				$this->_day = $this->_startDay;
			}
			$this->updateEpoch();

			return;
		}

		if ($field == Calendar::DAY) {
			for($i = 0; $i < abs($value); $i++) {
				$days_in_month = $this->daysInMonth();
 				$this->_day += $increment;
				
				if ($this->_day < 1) {
					$parse_string = ($this->_month == 1) 
						? "12/1/" . $this->_year - 1
						: $this->_month - 1 . "/1/" . $this->_year;
					$temp_cal = Calendar::parse( $parse_string );
					$this->add( Calendar::MONTH, $increment );
					$this->_day = $temp_cal->daysInMonth();
				} elseif ($this->_day > $days_in_month) {
					$this->add( Calendar::MONTH, $increment );
					$this->_day = 1;
				}
			}
			$this->_startDay = $this->_day;
			$this->updateEpoch();
			return;		
		}
		
		if ($field == Calendar::HOUR) {			
			$days = (int)($value / 24);
			$hours = (int)($value % 24);
			
			$this->add(Calendar::DAY, $days); 
			
			for($i = 0; $i < abs($hours); $i++) {
				$this->_hour += $increment;
				if ($this->_hour < 0) {
					$this->_hour = 23;
					$this->add( Calendar::DAY, $increment );
				} elseif ($this->_hour > 23) {
					$this->_hour = 0;
					$this->add( Calendar::DAY, $increment );
				}
			}
			$this->updateEpoch();
			return;		
		}


		if ($field == Calendar::MINUTE) {			
			$hours = (int)($value / 60);
			$minutes = (int)($value % 60);
			
			$this->add(Calendar::HOUR, $hours); 
			for ($i = 0; $i < abs($minutes); $i++) {
				$this->_minute += $increment;
				if ($this->_minute < 0) {
					$this->_minute = 59;
					$this->add( Calendar::HOUR, $increment );
				} elseif ($this->_minute > 59) {
					$this->_minute = 0;
					$this->add( Calendar::HOUR, $increment );
				}
			}
			$this->updateEpoch();
			return;		
		}

		if ($field == Calendar::SECOND) {			
			$minutes = (int)($value / 60);
			$seconds = (int)($value % 60);
			
			$this->add(Calendar::MINUTE, $minutes); 
			
			for($i = 0; $i < abs($seconds); $i++) {
				$this->_second += $increment;
				if ($this->_second < 0) {
					$this->_second = 59;
					$this->add( Calendar::MINUTE, $increment );
				} elseif ($this->_second > 59) {
					$this->_second = 0;
					$this->add( Calendar::MINUTE, $increment );
				}
			}
			$this->updateEpoch();
			return;		
		}
	}

	/**
	 * Returns true if this Calendar object occurs after the specified Calendar object
	 *
	 * @param Calendar $obj The Calendar object to compare this instance with
	 *
	 * @return bool True if this instance represents a time after the supplied Calendar instance 
	 */
	public function after( Calendar $obj ):bool {
		if (! is_a($obj, get_class())) {
			throw new Exception('Non-' . get_class() . ' object supplied.');
		}
		return ($this->getTimestamp() > $obj->getTimestamp() );	
	}

	
	/**
	 * Returns true if this Calendar instance represents a time before the time represented by the supplied Calendar instance
	 *
	 * @param Calendar $obj The Calendar object to compare this instance with
	 *
	 * @return bool True if this instance represents a time before the supplied Calendar instance
	 */
	public function before( Calendar $obj ):bool {
		// With a typed parameter, this shouldn't be necessary:
		if (! is_a($obj, get_class())) {
			throw new Exception('Non-' . get_class() . ' object supplied.');
		}
		return ($this->getTimestamp() < $obj->getTimestamp() );	
	}
	
	/**
	 * Compares the two Calendar objects.
	 *
	 * Returns 0 if identical, a value of less than 0 if this Calendar object is before the supplied Calendar object, or greater than 0 if after. E.g., this(5)->compareTo(5) returns 0; (10)->compareTo(8) returns 2, (8)->compareTo(10) returns -2.
	 *
	 * @param Calendar $obj The Calendar object to compare this object with.
	 *
	 * @return int 0 if identical, < 0 if $this is before $obj, > 0 if $this is after $obj
	 */
	public function compareTo( Calendar $obj ):int {
		return ($this->getTimestamp() - $obj->getTimestamp());
	}
		
	/** 
	 * Returns true if the two Calendar objects represent the same time.
	 * 
	 * @param Calendar $obj The Calendar object to compare $this to
	 *
	 * @return bool True if the same time.
	 */
	public function equals( Calendar $obj ):bool {
		// Compares this Calendar to the specified Object.
		return ! $this->compareTo($obj);
	}
	

	/**
	 * Compares two Calendar objects looking only at the date portion (ignoring hours, minutes, seconds)
	 * 
	 * @param Calendar $obj The Calendar instance to compare $this to
	 *
	 * @return int 0 if equal, < 0 if $this before $obj, > 0 if $this after $obj
	 */
	public function compareToDate( Calendar $obj ):int {
		// Compare two Calendar objects looking only at Year/Month/Day
		$a = (int)$this->toString("Ymd");
		$b = (int)$obj->toString("Ymd");
		return $a - $b;
	}
	
	/**
	 * Compare two Calendar objects looking only at the date portion (ignoring hours, minutes, seconds)
	 *
	 * Equivalent to compareToDate == 0
	 *
	 * @param $obj The Calendar instance to compare $this to
	 *
	 * @return bool True if the same date
	 */
	public function equalsDate( Calendar $obj ):bool {
		return ! $this->compareToDate( $obj );
	}
	
	/**
	 * Determine if $this is after $obj looking only at the date
	 *
	 * @param Calendar $obj Calendar instance to compare $this to
	 *
	 * @return bool True if $this is after $obj
	 */
	public function afterDate( Calendar $obj ):bool {
		if($this->compareToDate( $obj ) > 0 ) {
			return true;
		}
		return false;
	}

	/**
	 * Determine if $this is before $obj looking only at the date
	 *
	 * @param Calendar $obj Calendar instance to compare $this to
	 *
	 * @return bool True if $this is before $obj
	 */
	public function beforeDate( Calendar $obj ):bool {
		if($this->compareToDate( $obj ) < 0 ) {
			return true;
		}
		return false;
	}

	/**
	 * Clears the specified field; if null, // TODO
	 *
	 * @param int $field (optional) The Calendar field (e.g. Calendar::YEAR) to clear.
	 *
	 * @return void
	 */
	public function clear( int $field = null ) {
		// TODO null:			// TODO 
		$fields = array();
		if($field != null ) {
			// not null: 	Sets the given calendar field value and the time value (millisecond offset from the Epoch) of this Calendar to null.
			$fields = [ $field ];
		} else {
			// Sets all the calendar field values and the time value (millisecond offset from the Epoch) of this Calendar to null.
			$fields = [ self::YEAR, self::MONTH, self::DAY, self::HOUR, 
						self::MINUTE, self::SECOND, self::DAYOFWEEK, self::YEAR ];
		}
		foreach($fields as $f) { 
			$this->set( $f, -1, false );
		}
		$this->_epoch_timestamp = null;
	}

	/**
	 * Creates a duplicate instance of $this
	 *
	 * @return Calendar copied instance of $this
	 */
	public function clone():Calendar {
		return new Calendar( $this->getTimestamp() );
	}
	
	public function complete() {
		// TODO Fills in any unset fields in the calendar fields.
	}
	
	#public function computeFields() {
	#	// TODO Converts the current millisecond time value time to calendar field values in fields[].
	#}
	
	public function computeTime() {
		// TODO Converts the current calendar field values in fields[] to the millisecond time value time.
	}
	
	public function getActualMaximum( int $field ):int {
		// TODO Returns the maximum value that the specified calendar field could have, given the time value of this Calendar.
	}
	
	public function getActualMinimum( int $field ):int {
		// TODO Returns the minimum value that the specified calendar field could have, given the time value of this Calendar.
	}
	
	public function getAvailableLocales() {
		// TODO Returns an array of all locales for which the getInstance methods of this class can return localized instances. Java, returns Locale[]
	}
	
	//public function getDisplayName(int field, int style, Locale locale) {
		// TODO Returns the string representation of the calendar field value in the given style and locale.
	//}
	
	// Map<String,Integer> 	getDisplayNames(int field, int style, Locale locale) {
		// TODO Returns a Map containing all names of the calendar field in the given style and locale and their corresponding field values.
	// }
	
	public function getFirstDayOfWeek():int { 
		return $this->_firstDayofWeek;
	}
	
	public function getGreatestMinimum(int $field):int {
		// TODO Returns the highest minimum value for the given calendar field of this Calendar instance.
	}
	
	public static function getInstance( int $locale = null, int $timezone = null):Calendar {
		// TODO Gets a calendar using the default time zone and locale.
		//	TODO If isset locale, Gets a calendar using the specified time zone and default locale.
		//	TODO if isset timezone, Gets a calendar using the specified time zone and default locale.
		// 	TODO if both set, Gets a calendar with the specified time zone and locale.
	}

	public function getLeastMaximum(int $field):int {
		// TODO Returns the lowest maximum value for the given calendar field of this Calendar instance.
	}
	
	// int 	getMaximum(int field)
	// TODO Returns the maximum value for the given calendar field of this Calendar instance.
	
	//	int getMinimalDaysInFirstWeek()
	// TODO Gets what the minimal days required in the first week of the year are; e.g., if the first week is defined as one that contains the first day of the first month of a year, this method returns 1.

	// int 	getMinimum(int field)
	// TODO Returns the minimum value for the given calendar field of this Calendar instance.
	
	// Date getTime()
	// Returns a Date object representing this Calendar's time value (millisecond offset from the Epoch").

	//	long 	getTimeInMillis()
	//	TODO Returns this Calendar's time value in milliseconds.

	//	TimeZone 	getTimeZone()
	//	TODO Gets the time zone.

	//	int 	getWeeksInWeekYear()
	//	Returns the number of weeks in the week year represented by this Calendar.

	//	int 	getWeekYear()
	//	Returns the week year represented by this Calendar.

	//	int 	hashCode()
	//	Returns a hash code for this calendar.

	//	int 	internalGet(int field)
	//	Returns the value of the given calendar field.

	//	boolean 	isLenient()
	//	Tells whether date/time interpretation is to be lenient.

	//	boolean 	isSet(int field)
	//	Determines if the given calendar field has a value set, including cases that the value has been set by internal fields calculations triggered by a get method call.

	//	boolean 	isWeekDateSupported()
	//	Returns whether this Calendar supports week dates.

	//	roll(int field, boolean up)
	//	Adds or subtracts (up/down) a single unit of time on the given time field without changing larger fields.

	//	roll(int field, int amount)
	//	Adds the specified (signed) amount to the specified calendar field without changing larger fields.

	//	set(int field, int value)
	//	Sets the given calendar field to the given value.

	//	set(int year, int month, int date)
	//	Sets the values for the calendar fields YEAR, MONTH, and DAY_OF_MONTH.

	//	set(int year, int month, int date, int hourOfDay, int minute)
	//	Sets the values for the calendar fields YEAR, MONTH, DAY_OF_MONTH, HOUR_OF_DAY, and MINUTE.

	//	set(int year, int month, int date, int hourOfDay, int minute, int second)
	//	Sets the values for the fields YEAR, MONTH, DAY_OF_MONTH, HOUR, MINUTE, and SECOND.
	
	//	setFirstDayOfWeek(int value)
	//	Sets what the first day of the week is; e.g., SUNDAY in the U.S., MONDAY in France.
	function setFirstDayOfWeek( int $newFirstDayOfWeek ) {
		if ( 
			($newFirstDayOfWeek != Calendar::SUNDAY) &&
			($newFirstDayOfWeek != Calendar::MONDAY) &&
			($newFirstDayOfWeek != Calendar::TUESDAY) &&
			($newFirstDayOfWeek != Calendar::WEDNESDAY) &&
			($newFirstDayOfWeek != Calendar::THURSDAY) &&
			($newFirstDayOfWeek != Calendar::FRIDAY) &&
			($newFirstDayOfWeek != Calendar::SATURDAY)
		) {
			throw new Exception( "Value out of range." );
		}
			
		$this->_firstDayOfWeek = $newFirstDayOfWeek;
	}
	
	//	setLenient(boolean lenient)
	//	Specifies whether or not date/time interpretation is to be lenient.

	//	setMinimalDaysInFirstWeek(int value)
	//	Sets what the minimal days required in the first week of the year are; For example, if the first week is defined as one that contains the first day of the first month of a year, call this method with value 1.

	//	setTime(Date date)
	//	Sets this Calendar's time with the given Date.

	//	setTimeInMillis(long millis)
	//	Sets this Calendar's current time from the given long value.

	//	setTimeZone(TimeZone value)
	//	Sets the time zone with the given time zone value.

	//	setWeekDate(int weekYear, int weekOfYear, int dayOfWeek)
	//	Sets the date of this Calendar with the the given date specifiers - week year, week of year, and day of week.

	//	https://docs.oracle.com/javase/7/docs/api/java/util/Calendar.html#compareTo(java.util.Calendar)
	
	// PHP timezones: 	https://www.php.net/manual/en/timezones.php
	//					https://www.php.net/manual/en/function.date-default-timezone-get.php
	
	// PHP Locale:	https://www.php.net/manual/en/class.locale.php
	
	
	
	
	
	/****** /To Do ******/
	

	
	
	
	/**
	 * Returns the milliseconds since epoch timestamp for this Calendar instance (equivalent to PHP's {@link PHP_MANUAL#function.time.php time() } function)
	 *
	 * @return int Timestamp
	 */
	public function getTimestamp():int {
		return $this->_epoch_timestamp;
	}
	
	private function resetToLastDayOfMonth() {
		// TODO track if we were on a leapyear 2/29 and if so, if the landing year is a leap year, put it back to 2/29...
		$days_in_month = $this->daysInMonth();
		if ($this->_day > $days_in_month) {
			$this->_day = $days_in_month;
		}	
	}
	
	public function daysInMonth() {
		$t = date("t", strtotime( $this->_year . "-" . $this->_month . "-01"));
		// print "daysInMonth for " . $this->year . "-" . $this->month . "-01: $t\n";
		return (int)($t);
	}
	
	public function toString( string $format = null ) {
		$fmt = (! isset($format) || $format == null) 
			? "F j, Y"
			: $format;
		return date($fmt, $this->_epoch_timestamp);
	}

	public static function ordinal( int $day ) {
		$cal = new Calendar();
		$cal->set( Calendar::DAY, $day );
		return $cal->toString( "S" );
	}
}

/*
The following characters are recognized in the format parameter string format character 	Description 	Example returned values
Day 	--- 	---
d 	Day of the month, 2 digits with leading zeros 	01 to 31
D 	A textual representation of a day, three letters 	Mon through Sun
j 	Day of the month without leading zeros 	1 to 31
l (lowercase 'L') 	A full textual representation of the day of the week 	Sunday through Saturday
N 	ISO 8601 numeric representation of the day of the week 	1 (for Monday) through 7 (for Sunday)
S 	English ordinal suffix for the day of the month, 2 characters 	st, nd, rd or th. Works well with j
w 	Numeric representation of the day of the week 	0 (for Sunday) through 6 (for Saturday)
z 	The day of the year (starting from 0) 	0 through 365
Week 	--- 	---
W 	ISO 8601 week number of year, weeks starting on Monday 	Example: 42 (the 42nd week in the year)
Month 	--- 	---
F 	A full textual representation of a month, such as January or March 	January through December
m 	Numeric representation of a month, with leading zeros 	01 through 12
M 	A short textual representation of a month, three letters 	Jan through Dec
n 	Numeric representation of a month, without leading zeros 	1 through 12
t 	Number of days in the given month 	28 through 31
Year 	--- 	---
L 	Whether it's a leap year 	1 if it is a leap year, 0 otherwise.
o 	ISO 8601 week-numbering year. This has the same value as Y, except that if the ISO week number (W) belongs to the previous or next year, that year is used instead. 	Examples: 1999 or 2003
Y 	A full numeric representation of a year, 4 digits 	Examples: 1999 or 2003
y 	A two digit representation of a year 	Examples: 99 or 03
Time 	--- 	---
a 	Lowercase Ante meridiem and Post meridiem 	am or pm
A 	Uppercase Ante meridiem and Post meridiem 	AM or PM
B 	Swatch Internet time 	000 through 999
g 	12-hour format of an hour without leading zeros 	1 through 12
G 	24-hour format of an hour without leading zeros 	0 through 23
h 	12-hour format of an hour with leading zeros 	01 through 12
H 	24-hour format of an hour with leading zeros 	00 through 23
i 	Minutes with leading zeros 	00 to 59
s 	Seconds with leading zeros 	00 through 59
u 	Microseconds. Note that date() will always generate 000000 since it takes an int parameter, whereas DateTime::format() does support microseconds if DateTime was created with microseconds. 	Example: 654321
v 	Milliseconds. Same note applies as for u. 	Example: 654
Timezone 	--- 	---
e 	Timezone identifier 	Examples: UTC, GMT, Atlantic/Azores
I (capital i) 	Whether or not the date is in daylight saving time 	1 if Daylight Saving Time, 0 otherwise.
O 	Difference to Greenwich time (GMT) without colon between hours and minutes 	Example: +0200
P 	Difference to Greenwich time (GMT) with colon between hours and minutes 	Example: +02:00
p 	The same as P, but returns Z instead of +00:00 	Example: +02:00
T 	Timezone abbreviation, if known; otherwise the GMT offset. 	Examples: EST, MDT, +05
Z 	Timezone offset in seconds. The offset for timezones west of UTC is always negative, and for those east of UTC is always positive. 	-43200 through 50400
Full Date/Time 	--- 	---
c 	ISO 8601 date 	2004-02-12T15:19:21+00:00
r 	» RFC 2822 formatted date 	Example: Thu, 21 Dec 2000 16:01:07 +0200
U 	Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT) 	See also time()

*/


// TODO Nuke this and just use an associative array?
class CalendarDuration {
	private $_year = 0;
	private $_month = 0;
	private $_day = 0;
	private $_hour = 0;
	private $_minute = 0;
	private $_second = 0;
	
	public const YEAR = 1000;
	public const MONTH = 1001;
	public const DAY = 1002;
	public const HOUR = 1003;
	public const MINUTE = 1004;
	public const SECOND = 1005;

	public function increment( int $field ) {
		switch($field) {
			case CalendarDuration::YEAR:
				$this->_year++;
				break;
			case CalendarDuration::MONTH:
				$this->_month++;
				break;
			case CalendarDuration::DAY:
				$this->_day++;
				break;
			case CalendarDuration::HOUR:
				$this->_hour++;
				break;
			case CalendarDuration::MINUTE:
				$this->_minute++;
				break;
			case CalendarDuration::SECOND:
				$this->_second++;
				break;
		}				
	}
	
	public function get(int $field) {
		switch($field) {
			case CalendarDuration::YEAR:
				return $this->_year;
				break;
			case CalendarDuration::MONTH:
				return $this->_month;
				break;
			case CalendarDuration::DAY:
				return $this->_day;
				break;
			case CalendarDuration::HOUR:
				return $this->_hour;
				break;
			case CalendarDuration::MINUTE:
				return $this->_minute;
				break;
			case CalendarDuration::SECOND:
				return $this->_second;
				break;
		}				
	
	}

	public function toString() {
		$output = $this->toStringYMD();
		if($this->_hour > 0) {
			$output .= (strlen($output) > 0) ? ", " : "";
			$output .= $this->_hour . " hour";
			$output .= ($this->_hour > 1) ? "s" : "";
		}

		if($this->_minute > 0) {
			$output .= (strlen($output) > 0) ? ", " : "";
			$output .= $this->_minute . " minute";
			$output .= ($this->_minute > 1) ? "s" : "";
		}

		if($this->_second > 0) {
			$output .= (strlen($output) > 0) ? ", " : "";
			$output .= $this->_second . " second";
			$output .= ($this->_second > 1) ? "s" : "";
		}

		if(strlen($output) < 1 ) {
			return "0 seconds";
		}
		return $output;
	}

	public function toStringYMD() {
		$output = "";
		if($this->_year > 0) {
			$output .= $this->_year . " year";
			$output .= ($this->_year > 1) ? "s" : "";
		}

		if($this->_month > 0) {
			$output .= (strlen($output) > 0) ? ", " : "";
			$output .= $this->_month . " month";
			$output .= ($this->_month > 1) ? "s" : "";
		}

		if($this->_day > 0) {
			$output .= (strlen($output) > 0) ? ", " : "";
			$output .= $this->_day . " day";
			$output .= ($this->_day > 1) ? "s" : "";
		}
		return $output;
	}
}

?>

