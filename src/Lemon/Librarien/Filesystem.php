<?php

namespace Lemon\Librarien;

class Filesystem
{
   /**
     * Deletes given file/directory.
     */
    public static function delete(string $file): void
    {
        if (is_file($file)) {
            unlink($file);
        }

        if (is_dir($file)) {
            foreach (scandir($file) as $sub) {
                if (!in_array($sub, ['.', '..'])) {
                    self::delete($file.'/'.$sub);
                }
            }
            rmdir($file);
        }
    }
}
