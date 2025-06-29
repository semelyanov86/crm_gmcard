<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

require_once 'include/Webservices/VTQL_Lexer.php';
require_once 'include/Webservices/VTQL_Parser.php';

class Parser
{
    private $query = '';

    private $out;

    private $meta;

    private $hasError;

    private $error;

    private $user;

    public function __construct($user, $q)
    {
        $this->query = $q;
        $this->out = [];
        $this->hasError = false;
        $this->user = $user;
    }

    public function Parser($user, $q)
    {
        // PHP4-style constructor.
        // This will NOT be invoked, unless a sub-class that extends `foo` calls it.
        // In that case, call the new-style constructor to keep compatibility.
        self::__construct($user, $q);
    }

    public function parse()
    {

        $lex = new VTQL_Lexer($this->query);
        $parser = new VTQL_Parser($this->user, $lex, $this->out);

        while ($lex->yylex()) {
            $parser->doParse($lex->token, $lex->value);
        }
        $parser->doParse(0, 0);

        if ($parser->isSuccess()) {
            $this->hasError = false;
            $this->query = $parser->getQuery();
            $this->meta = $parser->getObjectMetaData();
        } else {
            $this->hasError = true;
            $this->error = $parser->getErrorMsg();
        }

        return $this->hasError;

    }

    public function getSql()
    {
        return $this->query;
    }

    public function getObjectMetaData()
    {
        return $this->meta;
    }

    public function getError()
    {
        return $this->error;
    }
}
