<?php

namespace Adobrovolsky97\Illuminar\Support;

/**
 * Builds "open in editor" links for callers.
 */
class EditorLink
{
    private const EDITORS = [
        'phpstorm' => 'phpstorm://open?file=%file&line=%line',
        'idea'     => 'idea://open?file=%file&line=%line',
        'vscode'   => 'vscode://file/%file:%line',
        'cursor'   => 'cursor://file/%file:%line',
        'windsurf' => 'windsurf://file/%file:%line',
        'sublime'  => 'subl://open?url=file://%file&line=%line',
        'zed'      => 'zed://file/%file:%line',
        'atom'     => 'atom://core/open/file?filename=%file&line=%line',
    ];

    /**
     * Build URL for "path/to/file.php:123" caller string
     */
    public static function forCaller(?string $caller): ?string
    {
        if (empty($caller)) {
            return null;
        }

        $line = 1;
        $file = $caller;

        if (preg_match('/^(.*):(\d+)$/', $caller, $matches)) {
            $file = $matches[1];
            $line = (int)$matches[2];
        }

        return self::forFile($file, $line);
    }

    public static function forFile(string $file, int $line = 1): ?string
    {
        $template = self::template();

        if ($template === null) {
            return null;
        }

        return str_replace(['%file', '%line'], [self::mapPath($file), max(1, $line)], $template);
    }

    /**
     * Link format understood by Symfony VarDumper (closures, exceptions in dumps)
     */
    public static function fileLinkFormat(): ?string
    {
        $template = self::template();

        if ($template === null) {
            return null;
        }

        $format = str_replace(['%file', '%line'], ['%f', '%l'], $template);

        $remote = config('illuminar.editor_remote_path');
        $local = config('illuminar.editor_local_path');

        if (!empty($remote) && !empty($local)) {
            $format .= '&' . rtrim($remote, '/') . '/>' . rtrim($local, '/') . '/';
        }

        return $format;
    }

    private static function template(): ?string
    {
        $editor = config('illuminar.editor');

        if (empty($editor)) {
            return null;
        }

        if (isset(self::EDITORS[$editor])) {
            return self::EDITORS[$editor];
        }

        return str_contains($editor, '%file') ? $editor : null;
    }

    /**
     * Map path inside Docker / VM to the path on the host machine
     */
    private static function mapPath(string $file): string
    {
        $remote = config('illuminar.editor_remote_path');
        $local = config('illuminar.editor_local_path');

        if (!empty($remote) && !empty($local) && str_starts_with($file, rtrim($remote, '/') . '/')) {
            return rtrim($local, '/') . substr($file, strlen(rtrim($remote, '/')));
        }

        return $file;
    }
}
