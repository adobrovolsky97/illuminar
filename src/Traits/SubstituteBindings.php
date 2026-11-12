<?php

namespace Adobrovolsky97\Illuminar\Traits;

use BackedEnum;
use DateTimeInterface;
use Stringable;
use UnitEnum;

/**
 * Trait SubstituteBindings
 */
trait SubstituteBindings
{
    /**
     * Replace the placeholders with the actual bindings.
     */
    protected function replaceBindings(string $sql, array $bindings): string
    {
        if (empty($bindings)) {
            return $sql;
        }

        return array_is_list($bindings)
            ? $this->replacePositionalBindings($sql, $bindings)
            : $this->replaceNamedBindings($sql, $bindings);
    }

    /**
     * Replace "?" placeholders, ignoring question marks inside quoted strings / identifiers
     * and PDO escaped "??" placeholders.
     */
    private function replacePositionalBindings(string $sql, array $bindings): string
    {
        $result = '';
        $index = 0;
        $quote = null;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];

            if ($quote !== null) {
                $result .= $char;

                if ($char === '\\' && $i + 1 < $length) {
                    $result .= $sql[++$i];
                } elseif ($char === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $quote = $char;
                $result .= $char;
                continue;
            }

            if ($char === '?') {
                if (($sql[$i + 1] ?? '') === '?') {
                    $result .= '?';
                    $i++;
                    continue;
                }

                $result .= array_key_exists($index, $bindings) ? $this->quoteBinding($bindings[$index]) : '?';
                $index++;
                continue;
            }

            $result .= $char;
        }

        return $result;
    }

    private function replaceNamedBindings(string $sql, array $bindings): string
    {
        // Longer names first, so ":name" does not break ":name_full"
        uksort($bindings, function ($a, $b) {
            return strlen((string)$b) <=> strlen((string)$a);
        });

        foreach ($bindings as $key => $value) {
            $pattern = '/:' . preg_quote(ltrim((string)$key, ':'), '/') . '(?![A-Za-z0-9_])/';
            $quoted = $this->quoteBinding($value);

            $sql = (string)preg_replace_callback($pattern, function () use ($quoted) {
                return $quoted;
            }, $sql);
        }

        return $sql;
    }

    /**
     * Quote the given value so the query can be copied and executed.
     *
     * @param mixed $binding
     */
    protected function quoteBinding($binding): string
    {
        switch (true) {
            case $binding === null:
                return 'null';
            case is_bool($binding):
                return $binding ? 'true' : 'false';
            case is_int($binding) || is_float($binding):
                return (string)$binding;
            case $binding instanceof DateTimeInterface:
                return "'" . $binding->format('Y-m-d H:i:s') . "'";
            case $binding instanceof BackedEnum:
                return $this->quoteBinding($binding->value);
            case $binding instanceof UnitEnum:
                return $this->quoteBinding($binding->name);
            case is_array($binding):
                return implode(', ', array_map([$this, 'quoteBinding'], $binding));
            case $binding instanceof Stringable:
                $binding = (string)$binding;
                break;
            case !is_string($binding):
                return '?';
        }

        if (!mb_check_encoding($binding, 'UTF-8')) {
            return '0x' . bin2hex(strlen($binding) > 64 ? substr($binding, 0, 64) : $binding)
                . (strlen($binding) > 64 ? '...' : '');
        }

        return "'" . str_replace("'", "''", $binding) . "'";
    }
}
