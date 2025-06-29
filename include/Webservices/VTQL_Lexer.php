<?php

global $where_col,$orderby,$in_started,$count;
$where_col = false;
$orderby = false;
$in_started = false;
$count = false;
function incrementN($lexer, $count)
{
    $i = 0;
    for (; $i < $count; ++$i) {
        incState($lexer);
    }
}
function incState($lexer)
{
    ++$lexer->current_state;
    if ($lexer->current_state === php7_sizeof($lexer->mandatory_states)) {
        $lexer->mandatory = false;
    }
}
function handleselect($lexer, $val)
{
    if ($lexer->mandatory) {
        if (strcasecmp($val, $lexer->mandatory_states[$lexer->current_state]) === 0) {
            incState($lexer);

            return VTQL_Parser::SELECT;
        }
    }
}
function handlecolumn_list($lexer, $val)
{
    global $count;
    if ($lexer->mandatory) {
        if (!(strcasecmp($val, $lexer->mandatory_states[2]) === 0)) {
            if (strcmp($val, '*') === 0) {
                if (!$count) {
                    incrementN($lexer, 1);
                }

                return VTQL_Parser::ASTERISK;
            }
            if (strcmp($val, '(') === 0) {
                return VTQL_Parser::PARENOPEN;
            }
            if (strcmp($val, ')') === 0) {
                return VTQL_Parser::PARENCLOSE;
            }
            if (strcasecmp($val, 'count') === 0) {
                $count = true;

                return VTQL_Parser::COUNT;
            }
            if (strcmp($val, ',') === 0) {
                return VTQL_Parser::COMMA;
            }

            return VTQL_Parser::COLUMNNAME;

        }
        incrementN($lexer, 2);

        return VTQL_Parser::FRM;

    }
}
function handlefrom($lexer, $val)
{
    if (strcasecmp($val, $lexer->mandatory_states[$lexer->current_state]) === 0) {
        incState($lexer);

        return VTQL_Parser::FRM;
    }
}
function handletable($lexer, $val)
{
    if ($lexer->mandatory) {
        $lexer->current_state = 0;
        $lexer->mandatory = false;
        if (!(strcasecmp($val, $lexer->optional_states[$lexer->current_state]) === 0)) {
            return VTQL_Parser::TABLENAME;
        }
    }
}
function handlewhere($lexer, $val)
{
    global $where_col,$in_started;
    $val = trim($val);
    if (strcmp($val, '=') === 0) {
        return VTQL_Parser::EQ;
    }
    if (strcasecmp($val, $lexer->optional_states[$lexer->current_state]) === 0) {
        return VTQL_Parser::WHERE;
    }
    if (strcmp($val, '<') === 0) {
        return VTQL_Parser::LT;
    }
    if (strcmp($val, '<=') === 0) {
        return VTQL_Parser::LTE;
    }
    if (strcmp($val, '>=') === 0) {
        return VTQL_Parser::GTE;
    }
    if (strcmp($val, '!=') === 0) {
        return VTQL_Parser::NE;
    }
    if (strcmp($val, '>') === 0) {
        return VTQL_Parser::GT;
    }
    if (strcmp($val, '(') === 0) {
        return VTQL_Parser::PARENOPEN;
    }
    if (strcmp($val, ')') === 0) {
        if ($in_started) {
            $in_started = false;
            $where_col = false;
        }

        return VTQL_Parser::PARENCLOSE;
    }
    if (strcasecmp($val, 'and') === 0) {
        return VTQL_Parser::LOGICAL_AND;
    }
    if (strcasecmp($val, 'or') === 0) {
        return VTQL_Parser::LOGICAL_OR;
    }
    if (!$where_col) {
        $where_col = true;

        return VTQL_Parser::COLUMNNAME;
    }
    if (strcasecmp($val, 'in') === 0) {
        $in_started = true;

        return VTQL_Parser::IN;
    }
    if (strcmp($val, ',') === 0) {
        return VTQL_Parser::COMMA;
    }
    if (strcasecmp($val, 'like') === 0) {
        return VTQL_Parser::LIKE;
    }
    if ($where_col) {
        if (!$in_started) {
            $where_col = false;
        }

        return VTQL_Parser::VALUE;
    }
}
function handleorderby($lexer, $val)
{
    global $orderby;
    if (!$orderby) {
        $orderby = true;

        return VTQL_Parser::ORDERBY;
    }
    if (strcmp($val, ',') === 0) {
        return VTQL_Parser::COMMA;
    }
    if (strcasecmp($val, 'asc') === 0) {
        return VTQL_Parser::ASC;
    }
    if (strcasecmp($val, 'desc') === 0) {
        return VTQL_Parser::DESC;
    }

    return VTQL_Parser::COLUMNNAME;

}
function handlelimit($lexer, $val)
{
    if (strcasecmp($val, 'limit') === 0) {
        return VTQL_Parser::LIMIT;
    }
    if (strcmp($val, '(') === 0) {
        return VTQL_Parser::PARENOPEN;
    }
    if (strcmp($val, ')') === 0) {
        return VTQL_Parser::PARENCLOSE;
    }
    if (strcmp($val, ',') === 0) {
        return VTQL_Parser::COMMA;
    }

    return VTQL_Parser::VALUE;

}
function handleend($lexer, $val)
{
    return VTQL_Parser::SEMICOLON;
}
class VTQL_Lexer
{
    private $index;

    public $token;

    public $value;

    public $linenum;

    public $state = 1;

    private $data;

    public $mandatory_states = ['select', 'column_list', 'from', 'table'];

    public $optional_states = ['where', 'orderby', 'limit'];

    public $mandatory;

    public $current_state;

    public function __construct($data)
    {
        $this->index = 0;
        $this->data = $data;
        $this->linenum = 1;
        $this->mandatory = true;
        $this->current_state = 0;
    }

    public function __toString()
    {
        return $this->token . '';
    }

    private $_yy_state = 1;

    private $_yy_stack = [];

    public function yylex()
    {
        return $this->{'yylex' . $this->_yy_state}();
    }

    public function yypushstate($state)
    {
        array_push($this->_yy_stack, $this->_yy_state);
        $this->_yy_state = $state;
    }

    public function yypopstate()
    {
        $this->_yy_state = array_pop($this->_yy_stack);
    }

    public function yybegin($state)
    {
        $this->_yy_state = $state;
    }

    public function yylex1()
    {
        $tokenMap =  [
            1 => 2,
            4 => 0,
        ];
        if ($this->index >= strlen($this->data)) {
            return false; // end of input
        }
        $yy_global_pattern = "/^((\\w+|'(?:[^']|'')+'|\\(|\\)|(\\+|-)?\\d+|,|\\*|(?!<|>)=|<(?!=)|>(?!=)|<=|>=|!=|;))|^([ \t\r\n]+)/";

        while (true) {
            if (preg_match($yy_global_pattern, substr($this->data, $this->index), $yymatches)) {
                $yysubmatches = $yymatches;
                $yymatches = array_filter($yymatches, 'strlen'); // remove empty sub-patterns
                if (!php7_count($yymatches)) {
                    throw new Exception('Error: lexing failed because a rule matched'
                        . 'an empty string.  Input "' . substr(
                            $this->data,
                            $this->index,
                            5,
                        ) . '... state INITR');
                }
                next($yymatches); // skip global match
                $this->token = key($yymatches); // token number
                if ($tokenMap[$this->token]) {
                    // extract sub-patterns for passing to lex function
                    $yysubmatches = array_slice(
                        $yysubmatches,
                        $this->token + 1,
                        $tokenMap[$this->token],
                    );
                } else {
                    $yysubmatches = [];
                }
                $this->value = current($yymatches); // token value
                $r = $this->{'yy_r1_' . $this->token}($yysubmatches);
                if ($r === null) {
                    $this->index += strlen($this->value);
                    $this->linenum += substr_count("\n", $this->value);

                    // accept this token
                    return true;
                }
                if ($r === true) {
                    // we have changed state
                    // process this token in the new state
                    return $this->yylex();
                }
                if ($r === false) {
                    $this->index += strlen($this->value);
                    $this->linenum += substr_count("\n", $this->value);
                    if ($this->index >= strlen($this->data)) {
                        return false; // end of input
                    }

                    // skip this token
                    continue;
                }                      $yy_yymore_patterns = [
                    1 => "^([ \t\r\n]+)",
                    4 => '',
                ];

                // yymore is needed
                do {
                    if (!strlen($yy_yymore_patterns[$this->token])) {
                        throw new Exception('cannot do yymore for the last token');
                    }
                    if (preg_match(
                        $yy_yymore_patterns[$this->token],
                        substr($this->data, $this->index),
                        $yymatches,
                    )) {
                        $yymatches = array_filter($yymatches, 'strlen'); // remove empty sub-patterns
                        next($yymatches); // skip global match
                        $this->token = key($yymatches); // token number
                        $this->value = current($yymatches); // token value
                        $this->linenum = substr_count("\n", $this->value);
                    }
                } while ($this->{'yy_r1_' . $this->token}() !== null);
                // accept
                $this->index += strlen($this->value);
                $this->linenum += substr_count("\n", $this->value);

                return true;

            }

            throw new Exception('Unexpected input at line' . $this->linenum
                . ': ' . $this->data[$this->index]);

            break;
        }
    } // end function

    public const INITR = 1;

    public function yy_r1_1($yy_subpatterns)
    {

        global $orderby;
        // echo "<br> ql state: ",$this->current_state," ",$this->value,"<br>";
        if ($this->mandatory) {
            // echo "<br> ql state: ",$this->current_state," ",$this->value,"<br>";
            $handler = 'handle' . $this->mandatory_states[$this->current_state];
            $this->token = $handler($this, $this->value);
        } else {
            $str = $this->value;
            if (strcmp($this->value, ';') === 0) {
                $this->token = handleend($this, $this->value);

                return;
            }
            if (strcasecmp($this->value, 'order') === 0) {
                $orderby = true;

                return false;
            }
            if (strcasecmp($this->value, 'by') === 0 && $orderby === true) {
                $orderby = false;
                $this->current_state = 1;
            }
            $index = array_search(strtolower($str), $this->optional_states, true);
            if ($index !== false) {
                $this->current_state = $index;
            }
            $handler = 'handle' . $this->optional_states[$this->current_state];
            $this->token = $handler($this, $this->value);
        }// $this->yypushstate($this->value);
    }

    public function yy_r1_4($yy_subpatterns)
    {

        return false;
    }
}
