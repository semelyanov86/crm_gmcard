<?php

/*
 ** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *
 */

/**
 * This class provides structured way of accessing details of email.
 */
class Vtiger_MailRecord
{
    // FROM address(es) list
    public $_from;

    // TO address(es) list
    public $_to;
    // var $_replyto;

    // CC address(es) list
    public $_cc;

    // BCC address(es) list
    public $_bcc;

    // DATE
    public $_date;

    // SUBJECT
    public $_subject;

    // BODY (either HTML / PLAIN message)
    public $_body;

    // Name of Mail Sender
    public $_fromname;

    // CHARSET of the body content
    public $_charset;

    // If HTML message was set as body content
    public $_isbodyhtml;

    // PLAIN message of the original email
    public $_plainmessage = false;

    // HTML message of the original email
    public $_htmlmessage = false;

    // ATTACHMENTS list of the email
    public $_attachments = false;

    // INLINE ATTACHMENTS list of the email
    public $_inline_attachments = false;

    // UNIQUEID associated with the email
    public $_uniqueid = false;

    // Flag to avoid re-parsing the email body.
    public $_bodyparsed = false;

    /** DEBUG Functionality. */
    public $debug = false;

    public function log($message = false)
    {
        if (!$message) {
            $message = $this->__toString();
        }

        global $log;
        if ($log && $this->debug) {
            $log->debug($message);
        } elseif ($this->debug) {
            echo var_export($message, true) . "\n";
        }
    }

    /**
     * String representation of the object.
     */
    public function __toString()
    {
        $tostring = '';
        $tostring .= 'FROM: [' . implode(',', $this->_from) . ']';
        $tostring .= ',TO: [' . implode(',', $this->_to) . ']';
        if (!empty($this->_cc)) {
            $tostring .= ',CC: [' . implode(',', $this->_cc) . ']';
        }
        if (!empty($this->_bcc)) {
            $tostring .= ',BCC: [' . implode(',', $this->_bcc) . ']';
        }
        $tostring .= ',DATE: [' . $this->_date . ']';
        $tostring .= ',SUBJECT: [' . $this->_subject . ']';

        return $tostring;
    }

    /**
     * Constructor.
     */
    public function __construct($imap, $messageid, $fetchbody = true)
    {
        $this->__parseHeader($imap, $messageid);
        if ($fetchbody) {
            $this->__parseBody($imap, $messageid);
        }
    }

    /**
     * Get body content as Text.
     */
    public function getBodyText($striptags = true)
    {
        $bodytext = $this->_body;

        if ($this->_plainmessage) {
            $bodytext = $this->_plainmessage;
        } elseif ($this->_isbodyhtml) {
            // TODO This conversion can added multiple lines if
            // content is displayed directly on HTML page
            $bodytext = preg_replace('/<br>/', "\n", $bodytext);
            $bodytext = strip_tags($bodytext);
        }

        return $bodytext;
    }

    /**
     * Get body content as HTML.
     */
    public function getBodyHTML()
    {
        $bodyhtml = $this->_body;
        if (!$this->_isbodyhtml) {
            $bodyhtml = preg_replace(["/\r\n/", "/\n/"], ['<br>', '<br>'], $bodyhtml);
        }
        if ($bodyhtml) {
            $bodyhtml = str_replace("\xc2\xa0", ' ', $bodyhtml);
        }

        return $bodyhtml;
    }

    /**
     * Fetch the mail body from server.
     */
    public function fetchBody($imap, $messageid)
    {
        if (!$this->_bodyparsed) {
            $this->__parseBody($imap, $messageid);
        }
    }

    /**
     * Parse the email id from the mail header text.
     */
    public function __getEmailIdList($inarray)
    {
        if (empty($inarray)) {
            return [];
        }
        $emails = [];
        foreach ($inarray as $emailinfo) {
            $emails[] = $emailinfo->mailbox . '@' . $emailinfo->host;
        }

        return $emails;
    }

    /**
     * Helper function to convert the encoding of input to target charset.
     */
    public static function __convert_encoding($input, $to, $from = false)
    {
        static $mb_function = null;
        static $iconv_function = null;

        if ($mb_function === null) {
            $mb_function = function_exists('mb_convert_encoding');
        }
        if ($iconv_function === null) {
            $iconv_function = function_exists('iconv');
        }

        if ($mb_function) {
            // if source charset is not determined or not-encoded as per imap_mime_decode
            if (!$from || $from == 'default') {
                $from = mb_detect_encoding($input);
            }

            if (strtolower(trim($to)) == strtolower(trim($from))) {
                return $input;
            }

            return mb_convert_encoding($input, $to, $from);

        }

        return $input;
    }

    /**
     * MIME decode function to parse IMAP header or mail information.
     */
    public static function __mime_decode($input, &$words = null, $targetEncoding = 'UTF-8')
    {
        if (is_null($words)) {
            $words = [];
        }
        $returnvalue = $input;
        if (is_null($input)) {
            $input = '';
        }
        preg_match_all('/=\?([^\?]+)\?([^\?]+)\?([^\?]+)\?=/', $input, $matches);
        if ($matches) {
            array_filter($matches);
        }
        if (php7_count($matches[0]) > 0) {
            $decodedArray =  imap_mime_header_decode($input);
            foreach ($decodedArray as $part => $prop) {
                $decodevalue = $prop->text;
                $charset = $prop->charset;
                $value = self::__convert_encoding($decodevalue, $targetEncoding, $charset);
                array_push($words, $value);
            }
        }
        if (!empty($words)) {
            $returnvalue = implode('', $words);
        }

        return $returnvalue;
    }

    /**
     * MIME encode function to prepare input to target charset supported by normal IMAP clients.
     */
    public static function __mime_encode($input, $encoding = 'Q', $charset = 'iso-8859-1')
    {
        $returnvalue = $input;
        $encoded = false;

        if (strtoupper($encoding) == 'B') {
            $returnvalue = self::__convert_encoding($input, $charset);
            $returnvalue = base64_encode($returnvalue);
            $encoded = true;
        } else {
            $returnvalue = self::__convert_encoding($input, $charset);
            if (function_exists('imap_qprint')) {
                $returnvalue = imap_qprint($returnvalue);
                $encoded = true;
            }
            // TODO: Handle case when imap_qprint is not available.

        }
        if ($encoded) {
            $returnvalue = "=?{$charset}?{$encoding}?{$returnvalue}?=";
        }

        return $returnvalue;
    }

    /**
     * Parse header of the email.
     */
    public function __parseHeader($imap, $messageid)
    {
        $this->_from = [];
        $this->_to = [];

        $mailheader = imap_headerinfo($imap, $messageid);

        $this->_uniqueid = $mailheader->message_id;

        $this->_from = $this->__getEmailIdList($mailheader->from);
        $this->_fromname = property_exists($mailheader->from[0], 'personal') ? self::__mime_decode($mailheader->from[0]->personal) : '';

        $this->_to = property_exists($mailheader, 'to') ? $this->__getEmailIdList($mailheader->to) : [];
        $this->_cc = property_exists($mailheader, 'cc') ? $this->__getEmailIdList($mailheader->cc) : [];
        $this->_bcc = property_exists($mailheader, 'bcc') ? $this->__getEmailIdList($mailheader->bcc) : [];

        $this->_date = $mailheader->udate;

        $this->_subject = property_exists($mailheader, 'subject') ? self::__mime_decode($mailheader->subject) : '';
        if (!$this->_subject) {
            $this->_subject = 'Untitled';
        }
    }

    // Modified: http://in2.php.net/manual/en/function.imap-fetchstructure.php#85685
    public function __parseBody($imap, $messageid)
    {
        $structure = imap_fetchstructure($imap, $messageid);

        $this->_plainmessage = '';
        $this->_htmlmessage = '';
        $this->_body = '';
        $this->_isbodyhtml = false;

        if (property_exists($structure, 'parts') && is_array($structure->parts)) { /* multipart */
            foreach ($structure->parts as $partno0 => $p) {
                $this->__getpart($imap, $messageid, $p, $partno0 + 1);
            }
        } else { /* not multipart */
            $this->__getpart($imap, $messageid, $structure, 0);
        }

        // Set the body (either plain or html content)
        if ($this->_htmlmessage != '') {
            $this->_body = $this->_htmlmessage;
            $this->_isbodyhtml = true;
        } else {
            $this->_body = $this->_plainmessage;
        }

        if ($this->_attachments) {
            $this->log('Attachments: ');
            $filename = [];
            $content = [];
            $attachmentKeys = array_keys($this->_attachments);
            for ($i = 0; $i < php7_count($attachmentKeys); ++$i) {
                $filename[$i] = self::__mime_decode($attachmentKeys[$i]);
                $content[$i] = $this->_attachments[$attachmentKeys[$i]];
            }
            unset($this->_attachments);
            for ($i = 0; $i < php7_count($attachmentKeys); ++$i) {
                $this->_attachments[$filename[$i]] = $content[$i];
            }
            $this->log(array_keys($this->_attachments));
        }

        $this->_bodyparsed = true;
    }

    // Modified: http://in2.php.net/manual/en/function.imap-fetchstructure.php#85685
    public function __getpart($imap, $messageid, $p, $partno)
    {
        // $partno = '1', '2', '2.1', '2.1.3', etc if multipart, 0 if not multipart

        // DECODE DATA
        $data = ($partno)
            ? imap_fetchbody($imap, $messageid, $partno) // multipart
            : imap_body($imap, $messageid);               // not multipart

        // Any part may be encoded, even plain text messages, so check everything.
        if ($p->encoding == 4) {
            $data = quoted_printable_decode($data);
        } elseif ($p->encoding == 3) {
            $data = base64_decode($data);
        }
        // no need to decode 7-bit, 8-bit, or binary

        // PARAMETERS
        // get all parameters, like charset, filenames of attachments, etc.
        $params = [];
        if ($p->parameters) {
            foreach ($p->parameters as $x) {
                $params[strtolower($x->attribute)] = $x->value;
            }
        }
        if (property_exists($p, 'dparameters') && $p->dparameters) {
            foreach ($p->dparameters as $x) {
                $params[strtolower($x->attribute)] = $x->value;
            }
        }

        // ATTACHMENT
        // Any part with a filename is an attachment,
        // so an attached text file (type 0) is not mistaken as the message.
        if ((isset($params['filename']) && $params['filename']) || (isset($params['name']) && $params['name'])) {
            // filename may be given as 'Filename' or 'Name' or both
            $filename = ($params['filename']) ? $params['filename'] : $params['name'];
            // filename may be encoded, so see imap_mime_header_decode()
            if (!$this->_attachments) {
                $this->_attachments = [];
            }
            $this->_attachments[$filename] = $data;  // TODO: this is a problem if two files have same name
        }

        // TEXT
        elseif ($p->type == 0 && $data) {
            $this->_charset = $params['charset'];  // assume all parts are same charset
            $data = self::__convert_encoding($data, 'UTF-8', $this->_charset);

            // Messages may be split in different parts because of inline attachments,
            // so append parts together with blank row.
            if (strtolower($p->subtype) == 'plain') {
                $this->_plainmessage .= trim($data) . "\n\n";
            } else {
                $this->_htmlmessage .= $data . '<br><br>';
            }
        }

        // EMBEDDED MESSAGE
        // Many bounce notifications embed the original message as type 2,
        // but AOL uses type 1 (multipart), which is not handled here.
        // There are no PHP functions to parse embedded messages,
        // so this just appends the raw source to the main message.
        elseif ($p->type == 2 && $data) {
            $this->_plainmessage .= trim($data) . "\n\n";
        }

        // SUBPART RECURSION
        if (property_exists($p, 'parts') && $p->parts) {
            foreach ($p->parts as $partno0 => $p2) {
                $this->__getpart($imap, $messageid, $p2, $partno . '.' . ($partno0 + 1));
            }  // 1.2, 1.2.1, etc.
        }
    }
}
