<?php

namespace App\Service;

final class FileChecker
{
    /**
     * Basic existence check for uploaded/generated files.
     *
     * @param string $path Absolute or relative path.
     */
    public function exists(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        return is_file($path);
    }
}

