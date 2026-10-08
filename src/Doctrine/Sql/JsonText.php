<?php

namespace App\Doctrine\Sql;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * DQL: JSON_TEXT(field) -> CAST(field AS TEXT)
 *
 * Used together with a LIKE parameter to test whether a JSON array column
 * contains a given value, e.g. JSON_TEXT(p.sizes) LIKE '%"M"%'.
 */
class JsonText extends FunctionNode
{
    public Node|string|null $field = null;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->field = $parser->StringPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'CAST('.$sqlWalker->walkStringPrimary($this->field).' AS TEXT)';
    }
}