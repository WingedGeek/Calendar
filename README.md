# Basic Usage Example

```
<?php
require "Calendar.php";

$parse_string = "11/15/2022";
$today = new Calendar();
$due = Calendar::parse( $parse_string );
$twenty_one_days_from_today = $today->clone();
$twenty_one_days_from_today->add( Calendar::DAY, 21);
print "Today is " . $today->toString() . "\n";
print "21 days from today is " . $twenty_one_days_from_today->toString() . "\n";
print "$parse_string is " . $due->toString() . "\n";


$text = ($today->beforeDate( $due )) ? " until due" : " past due";
$duration = Calendar::calculateDuration( $today, $due, Calendar::DAY, Calendar::DAY );

print $duration->toString()
    . $text
    . " (due date: the "
    . $due->toString('d')
    . $due::ordinal( (int)$due->toString('d') )
    . " of "
    . $due->toString( "F, Y")
    . ")\n";


```
