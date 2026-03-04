<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Recursive-descent expression parser.
 *
 * Supports: +, -, *, /, ^, sqrt(), parentheses, negation, decimals.
 * Does NOT use eval().
 *
 * Grammar:
 *   expr    = term (('+' | '-') term)*
 *   term    = power (('*' | '/') power)*
 *   power   = unary ('^' unary)*
 *   unary   = '-' unary | primary
 *   primary = number | 'sqrt' '(' expr ')' | '(' expr ')'
 */
class ExpressionParser
{
    private string $input;
    private int $pos;
    private int $len;

    public function evaluate(string $expression): float
    {
        // Normalise: remove whitespace, replace × ÷ shortcuts
        $this->input = preg_replace('/\s+/', '', $expression);
        $this->input = str_replace(['×', '÷', '**'], ['*', '/', '^'], $this->input);
        $this->pos = 0;
        $this->len = strlen($this->input);

        $result = $this->parseExpr();

        if ($this->pos < $this->len) {
            throw new InvalidArgumentException(
                "Unexpected character '{$this->input[$this->pos]}' at position {$this->pos}"
            );
        }

        return $result;
    }

    // expr = term (('+' | '-') term)*
    private function parseExpr(): float
    {
        $value = $this->parseTerm();

        while ($this->pos < $this->len && in_array($this->current(), ['+', '-'])) {
            $op = $this->consume();
            $right = $this->parseTerm();
            $value = $op === '+' ? $value + $right : $value - $right;
        }

        return $value;
    }

    // term = power (('*' | '/') power)*
    private function parseTerm(): float
    {
        $value = $this->parsePower();

        while ($this->pos < $this->len && in_array($this->current(), ['*', '/'])) {
            $op = $this->consume();
            $right = $this->parsePower();

            if ($op === '/') {
                if ($right == 0) {
                    throw new InvalidArgumentException('Division by zero');
                }
                $value = $value / $right;
            } else {
                $value = $value * $right;
            }
        }

        return $value;
    }

    // power = unary ('^' unary)*  — right-associative
    private function parsePower(): float
    {
        $base = $this->parseUnary();

        if ($this->pos < $this->len && $this->current() === '^') {
            $this->consume(); // consume '^'
            $exp = $this->parsePower(); // right-associative
            return pow($base, $exp);
        }

        return $base;
    }

    // unary = '-' unary | primary
    private function parseUnary(): float
    {
        if ($this->pos < $this->len && $this->current() === '-') {
            $this->consume();
            return -$this->parseUnary();
        }

        return $this->parsePrimary();
    }

    // primary = number | 'sqrt' '(' expr ')' | '(' expr ')'
    private function parsePrimary(): float
    {
        if ($this->pos < $this->len && $this->current() === '(') {
            $this->consume(); // '('
            $value = $this->parseExpr();
            $this->expect(')');
            return $value;
        }

        // Function: sqrt
        if (substr($this->input, $this->pos, 4) === 'sqrt') {
            $this->pos += 4;
            $this->expect('(');
            $inner = $this->parseExpr();
            $this->expect(')');
            if ($inner < 0) {
                throw new InvalidArgumentException('sqrt of a negative number is not defined');
            }
            return sqrt($inner);
        }

        return $this->parseNumber();
    }

    private function parseNumber(): float
    {
        $start = $this->pos;

        if ($this->pos < $this->len && $this->current() === '-') {
            $this->pos++;
        }

        if ($this->pos >= $this->len || !ctype_digit($this->current()) && $this->current() !== '.') {
            throw new InvalidArgumentException(
                "Expected number at position {$this->pos}" .
                ($this->pos < $this->len ? ", got '{$this->input[$this->pos]}'" : '')
            );
        }

        while ($this->pos < $this->len && (ctype_digit($this->current()) || $this->current() === '.')) {
            $this->pos++;
        }

        return (float) substr($this->input, $start, $this->pos - $start);
    }

    private function current(): string
    {
        return $this->input[$this->pos];
    }

    private function consume(): string
    {
        return $this->input[$this->pos++];
    }

    private function expect(string $char): void
    {
        if ($this->pos >= $this->len || $this->input[$this->pos] !== $char) {
            $got = $this->pos < $this->len ? "'{$this->input[$this->pos]}'" : 'end of expression';
            throw new InvalidArgumentException("Expected '$char' but got $got at position {$this->pos}");
        }
        $this->pos++;
    }
}
