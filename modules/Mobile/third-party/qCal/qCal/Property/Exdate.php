<?php

/**
 * Exception Date/Times Property.
 * @copyright Luke Visinoni (luke.visinoni@gmail.com)
 * @author Luke Visinoni (luke.visinoni@gmail.com)
 * @license GNU Lesser General Public License
 * @todo Make sure allowedComponents is correct. The RFC isn't dead clear.
 *       Perhaps this means that it can be included in any component that
 *       includes an rdate or rrule property?
 *
 * RFC 2445 Definition
 *
 * Property Name: EXDATE
 *
 * Purpose: This property defines the list of date/time exceptions for a
 * recurring calendar component.
 *
 * Value Type: The default value type for this property is DATE-TIME.
 * The value type can be set to DATE.
 *
 * Property Parameters: Non-standard, value data type and time zone
 * identifier property parameters can be specified on this property.
 *
 * Conformance: This property can be specified in an iCalendar object
 * that includes a recurring calendar component.
 *
 * Description: The exception dates, if specified, are used in computing
 * the recurrence set. The recurrence set is the complete set of
 * recurrence instances for a calendar component. The recurrence set is
 * generated by considering the initial "DTSTART" property along with
 * the "RRULE", "RDATE", "EXDATE" and "EXRULE" properties contained
 * within the iCalendar object. The "DTSTART" property defines the first
 * instance in the recurrence set. Multiple instances of the "RRULE" and
 * "EXRULE" properties can also be specified to define more
 * sophisticated recurrence sets. The final recurrence set is generated
 * by gathering all of the start date-times generated by any of the
 * specified "RRULE" and "RDATE" properties, and then excluding any
 * start date and times which fall within the union of start date and
 * times generated by any specified "EXRULE" and "EXDATE" properties.
 * This implies that start date and times within exclusion related
 * properties (i.e., "EXDATE" and "EXRULE") take precedence over those
 * specified by inclusion properties (i.e., "RDATE" and "RRULE"). Where
 * duplicate instances are generated by the "RRULE" and "RDATE"
 * properties, only one recurrence is considered. Duplicate instances
 * are ignored.
 *
 * The "EXDATE" property can be used to exclude the value specified in
 * "DTSTART". However, in such cases the original "DTSTART" date MUST
 * still be maintained by the calendaring and scheduling system because
 * the original "DTSTART" value has inherent usage dependencies by other
 * properties such as the "RECURRENCE-ID".
 *
 * Format Definition: The property is defined by the following notation:
 *
 *   exdate     = "EXDATE" exdtparam ":" exdtval *("," exdtval) CRLF
 *
 *   exdtparam  = *(
 *
 *              ; the following are optional,
 *              ; but MUST NOT occur more than once
 *
 *              (";" "VALUE" "=" ("DATE-TIME" / "DATE")) /
 *           (";" tzidparam) /
 *
 *              ; the following is optional,
 *              ; and MAY occur more than once
 *
 *              (";" xparam)
 *
 *              )
 *
 *   exdtval    = date-time / date
 *   ;Value MUST match value type
 *
 * Example: The following is an example of this property:
 *
 *   EXDATE:19960402T010000Z,19960403T010000Z,19960404T010000Z
 */
class qCal_Property_Exdate extends qCal_Property_MultiValue
{
    protected $type = 'DATE-TIME';

    protected $allowedComponents = ['VEVENT', 'VTODO', 'VJOURNAL', 'VTIMEZONE'];
}
