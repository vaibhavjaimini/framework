<?php

namespace Illuminate\Foundation;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;

class Mix
{
    /**
     * Get the path to a versioned Mix file.
     *
     * @param  string $path
     * @param  bool $relativePath
     * @param  string $manifestDirectory
     * @return HtmlString|string
     * @throws Exception
     */
    public function __invoke($path, $manifestDirectory = '', $relativePath = false)
    {
        static $manifests = [];

        if (! Str::startsWith($path, '/')) {
            $path = "/{$path}";
        }

        if ($manifestDirectory && ! Str::startsWith($manifestDirectory, '/')) {
            $manifestDirectory = "/{$manifestDirectory}";
        }

        if (file_exists(public_path($manifestDirectory.'/hot'))) {
            $url = rtrim(file_get_contents(public_path($manifestDirectory.'/hot')));

            if (Str::startsWith($url, ['http://', 'https://'])) {
                return new HtmlString(Str::after($url, ':').$path);
            }

            return new HtmlString("//localhost:8080{$path}");
        }

        $manifestPath = public_path($manifestDirectory.'/mix-manifest.json');

        if (! isset($manifests[$manifestPath])) {
            if (! file_exists($manifestPath)) {
                throw new Exception('The Mix manifest does not exist.');
            }

            $manifests[$manifestPath] = json_decode(file_get_contents($manifestPath), true);
        }

        $manifest = $manifests[$manifestPath];

        if (! isset($manifest[$path])) {
            $exception = new Exception("Unable to locate Mix file: {$path}.");

            if (! app('config')->get('app.debug')) {
                report($exception);

                return $path;
            } else {
                throw $exception;
            }
        }

        $html = $manifestDirectory.$manifest[$path];
        if ($relativePath && Str::startsWith($html, ['/'])) {
            $html = Str::substr($html, 1);
        }

        return new HtmlString($html);
    }
}
