<?php

/**
 * Exception Rule Property.
 * @copyright Luke Visinoni (luke.visinoni@gmail.com)
 * @author Luke Visinoni (luke.visinoni@gmail.com)
 * @license GNU Lesser General Public License
 * @todo Make sure allowedCompoents is correct. The RFC isn't dead clear
 * See the todo for EXDATE
 *
 * RFC 2445 Definition
 *
 * Property Name: EXRULE
 *
 * Purpose: This property defines a rule or repeating pattern for an
 * exception to a recurrence set.
 *
 * Value Type: RECUR
 *
 * Property Parameters: Non-standard property parameters can be
 * specified on this property.
 *
 * Conformance: This property can be specified in "VEVENT", "VTODO" or
 * "VJOURNAL" calendar components.
 *
 * Description: The exception rule, if specified, is used in computing
 * the recurrence set. The recurrence set is the complete set of
 * recurrence instances for a calendar component. The recurrence set is
 * generated by considering the initial "DTSTART" property along with
 * the "RRULE", "RDATE", "EXDATE" and "EXRULE" properties contained
 * within the iCalendar object. The "DTSTART" defines the first instance
 * in the recurrence set. Multiple instances of the "RRULE" and "EXRULE"
 * properties can also be specified to define more sophisticated
 * recurrence sets. The final recurrence set is generated by gathering
 * all of the start date-times generated by any of the specified "RRULE"
 * and "RDATE" properties, and excluding any start date and times which
 * fall within the union of start date and times generated by any
 * specified "EXRULE" and "EXDATE" properties. This implies that start
 * date and times within exclusion related properties (i.e., "EXDATE"
 * and "EXRULE") take precedence over those specified by inclusion
 *
 * properties (i.e., "RDATE" and "RRULE"). Where duplicate instances are
 * generated by the "RRULE" and "RDATE" properties, only one recurrence
 * is considered. Duplicate instances are ignored.
 *
 * The "EXRULE" property can be used to exclude the value specified in
 * "DTSTART". However, in such cases the original "DTSTART" date MUST
 * still be maintained by the calendaring and scheduling system because
 * the original "DTSTART" value has inherent usage dependencies by other
 * properties such as the "RECURRENCE-ID".
 *
 * Format Definition: The property is defined by the following notation:
 *
 *   exrule     = "EXRULE" exrparam ":" recur CRLF
 *
 *   exrparam   = *(";" xparam)
 *
 * Example: The following are examples of this property. Except every
 * other week, on Tuesday and Thursday for 4 occurrences:
 *
 *   EXRULE:FREQ=WEEKLY;COUNT=4;INTERVAL=2;BYDAY=TU,TH
 *
 * Except daily for 10 occurrences:
 *
 *   EXRULE:FREQ=DAILY;COUNT=10
 *
 * Except yearly in June and July for 8 occurrences:
 *
 *   EXRULE:FREQ=YEARLY;COUNT=8;BYMONTH=6,7
 */
class qCal_Property_Exrule extends qCal_Property
{
    protected $type = 'RECUR';

    protected $allowedComponents = ['VEVENT', 'VTODO', 'VJOURNAL'];

    protected $allowMultiple = true;
}
