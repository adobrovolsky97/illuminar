<?php

namespace Adobrovolsky97\Illuminar\Traits;

use DateTimeInterface;

/**
 * Trait SubstituteBindings
 */
trait SubstituteBindings
{
    /**
     * Replace the placeholders with the actual bindings.
     *
     * @param string $sql
     * @param array $bindings
     * @return string
     */
    protected function replaceBindings(string $sql, array $bindings): string
    {
        if (empty($bindings)) {
            return $sql;
        }

        if (strpos($sql, ':') !== false) {
            foreach ($bindings as $key => $value) {
                $value = $this->quoteBinding($value);
                $sql = str_replace(':' . $key, $value, $sql);
            }

            return $sql;
        }

        $sql = str_replace(['%', '?'], ['%%', '%s'], $sql);
        return vsprintf($sql, array_map([$this, 'quoteBinding'], $bindings));
    }

    /**
     * Quote the given string literal.
     *
     * @param mixed $binding
     * @return string
     */
    protected function quoteBinding($binding): string
    {
        if ($binding instanceof DateTimeInterface) {
            return "'" . $binding->format('Y-m-d H:i:s') . "'";
        }

        if (is_array($binding)) {
            return implode(', ', array_map([$this, 'quoteBinding'], $binding));
        }

        if (is_string($binding)) {
            return "'$binding'";
        }

        return is_bool($binding)
            ? var_export($binding, true)
            : var_export($binding ?? 'null', true);
    }
}
