<?php

namespace App\Services\Upwork\Api;

/**
 * The last gate before anything is sent to Upwork's GraphQL API: only a
 * single allowlisted, named query operation passes. Mutations,
 * subscriptions, anonymous or multi-operation documents are refused,
 * whatever their origin.
 */
class ReadOnlyGraphQl
{
    /**
     * @param string[] $allowedOperations
     * @throws UpworkWriteRefused
     */
    public static function assert(string $document, array $allowedOperations): string
    {
        // Ignore comments and string literals, so neither can hide or fake a keyword.
        $code = preg_replace('/"""[\s\S]*?"""|"(?:\\\\.|[^"\\\\])*"/', '""', $document);
        $code = preg_replace('/#[^\n]*/', '', $code);

        if (preg_match('/\b(mutation|subscription)\b/i', $code)) {
            throw new UpworkWriteRefused('Refused: GraphQL mutations and subscriptions are never sent to Upwork.');
        }
        if (preg_match_all('/(?:^|[}\s])query\s+([A-Za-z_][A-Za-z0-9_]*)/', $code, $m) !== 1) {
            throw new UpworkWriteRefused('Refused: exactly one named query operation is allowed.');
        }
        if (!preg_match('/^\s*query\s/', $code)) {
            throw new UpworkWriteRefused('Refused: the document must start with its query operation.');
        }
        $name = $m[1][0];
        if (!in_array($name, $allowedOperations, true)) {
            throw new UpworkWriteRefused("Refused: '{$name}' is not an allowlisted read-only operation.");
        }

        return $name;
    }
}
