<?php

namespace App\Doctrine\Sql;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * DQL: COALESCE(expr1, expr2, ...) -> COALESCE(expr1, expr2, ...)
 */
class Coalesce extends FunctionNode
{
    public Node|string|null $value = null;

    /** @var array<Node|string> */
    public array $values = [];

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->value = $parser->StringPrimary();

        while (!$parser->getLexer()->isNextToken(TokenType::T_CLOSE_PARENTHESIS)) {
            $parser->match(TokenType::T_COMMA);
            $this->values[] = $parser->StringPrimary();
        }

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        $parts = [];

        if (null !== $this->value) {
            $parts[] = $sqlWalker->walkStringPrimary($this->value);
        }
        foreach ($this->values as $value) {
            $parts[] = $sqlWalker->walkStringPrimary($value);
        }

        return 'COALESCE('.implode(', ', $parts).')';
    }
}