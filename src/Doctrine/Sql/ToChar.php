<?php

namespace App\Doctrine\Sql;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * DQL: TO_CHAR(dateField, 'YYYY-MM-DD') -> TO_CHAR(dateField, 'YYYY-MM-DD')
 */
class ToChar extends FunctionNode
{
    public Node|string|null $date = null;

    public string $format = '';

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->date = $parser->StringPrimary();
        $parser->match(TokenType::T_COMMA);
        $literal = $parser->Literal();
        $this->format = $literal->value;

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            "TO_CHAR(%s, '%s')",
            $sqlWalker->walkStringPrimary($this->date),
            $this->format,
        );
    }
}