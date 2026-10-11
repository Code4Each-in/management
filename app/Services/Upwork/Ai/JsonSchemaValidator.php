<?php

namespace App\Services\Upwork\Ai;

/**
 * Small validator for the JSON-schema subset used by our AI schemas: type
 * (incl. ["string","null"]), properties, required, additionalProperties:false,
 * items, enum, minimum, maximum. AI output is validated before it is stored.
 */
class JsonSchemaValidator
{
    /** @return string[] problems; empty when valid */
    public static function validate($value, array $schema, string $path = '$'): array
    {
        $errors = [];

        if (isset($schema['type'])) {
            $types = (array) $schema['type'];
            $matched = false;
            foreach ($types as $type) {
                if (self::isType($value, $type)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                return ["{$path}: expected " . implode('|', $types) . ', got ' . gettype($value)];
            }
            if ($value === null) {
                return [];
            }
        }

        if (isset($schema['enum']) && !in_array($value, $schema['enum'], true)) {
            $errors[] = "{$path}: not one of " . implode(', ', array_map('json_encode', $schema['enum']));
        }
        if (isset($schema['minimum']) && is_numeric($value) && $value < $schema['minimum']) {
            $errors[] = "{$path}: below minimum {$schema['minimum']}";
        }
        if (isset($schema['maximum']) && is_numeric($value) && $value > $schema['maximum']) {
            $errors[] = "{$path}: above maximum {$schema['maximum']}";
        }

        if (is_array($value) && self::isAssoc($value, $schema)) {
            foreach ($schema['required'] ?? [] as $key) {
                if (!array_key_exists($key, $value)) {
                    $errors[] = "{$path}.{$key}: missing";
                }
            }
            foreach ($value as $key => $child) {
                if (isset($schema['properties'][$key])) {
                    $errors = array_merge($errors, self::validate($child, $schema['properties'][$key], "{$path}.{$key}"));
                } elseif (($schema['additionalProperties'] ?? true) === false) {
                    $errors[] = "{$path}.{$key}: not allowed";
                }
            }
        } elseif (is_array($value) && isset($schema['items'])) {
            foreach (array_values($value) as $i => $child) {
                $errors = array_merge($errors, self::validate($child, $schema['items'], "{$path}[{$i}]"));
            }
        }

        return $errors;
    }

    private static function isType($value, string $type): bool
    {
        switch ($type) {
            case 'null': return $value === null;
            case 'string': return is_string($value);
            case 'boolean': return is_bool($value);
            case 'integer': return is_int($value);
            case 'number': return is_int($value) || is_float($value);
            case 'array': return is_array($value) && ($value === [] || array_keys($value) === range(0, count($value) - 1));
            case 'object': return is_array($value) && ($value === [] || array_keys($value) !== range(0, count($value) - 1));
        }

        return false;
    }

    private static function isAssoc(array $value, array $schema): bool
    {
        return in_array('object', (array) ($schema['type'] ?? []), true) || isset($schema['properties']);
    }
}
