<?php

namespace App\Service;

final class InputFieldValidation
{
    /**
     * Very small helper for input validation/sanitization.
     *
     * Note: This does not replace proper parameterized queries or ORM usage.
     */
    public function isSafeText(?string $value, int $maxLength = 500): bool
    {
        if ($value === null) {
            return false;
        }

        $value = trim($value);

        if ($value === '') {
            return false;
        }

        if (mb_strlen($value) > $maxLength) {
            return false;
        }

        // Block common SQL-injection primitives.
        // Keep intentionally conservative.
        $blacklist = [
            "--",
            "/*",
            "*/",
            "'",
            '"',
            ';',
            "xp_",
            "sleep(",
            "benchmark(",
            "union",
            "select",
            "insert",
            "update",
            "delete",
            "drop",
            "truncate",
            " or ",
            " and ",
        ];

        $lower = mb_strtolower($value);
        foreach ($blacklist as $needle) {
            if (mb_strpos($lower, mb_strtolower($needle)) !== false) {
                return false;
            }
        }

        return true;
    }
}

