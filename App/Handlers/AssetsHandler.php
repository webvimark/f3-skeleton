<?php
namespace App\Handlers;
use Exception;

class AssetsHandler
{
    /**
     * Combine CSS or JS files to the one file and add timestamp to the url.
     * Your assets should be located in WEB_DIR (by default it's - "www/")
     * 
     * This function returns URL to combine route (see it after this function).
     * 
     * Basic example:
     *      <link rel="stylesheet" href="{{ combine('css/bootstrap.min.css', 'css/main.css') }}">
     *      <script src="{{ combine(array('js/query.js', 'js/some-lib/main.js')) }}"></script>
     * 
     * Example with injection (e.g. set "v.js" variable and use it like this):
     *      <script src="{{ combine('js/query.js', @v.js, 'js/some-lib/main.js') }}"></script>
     *
     * @param array $files
     * @return string
     */
    public static function getCombineLink($files)
    {
        $result = [];
        try {
            $mtime = static::cleanFiles($files, $result);
        } catch (Exception $e) {
            return 'Missing file - ' . $e->getMessage();
        }

        if (!$result) {
            return '';
        }

        $result = array_values($result);

        preg_match('/\w+$/', $result[0], $ext);
        $type = $ext[0];

        if (!in_array($type, ['css', 'js'])) {
            return 'You can pass only CSS and JS files to combine() function';
        }

        $fileName = md5(implode(',', $result)) . '.' . $type;
        $file = WEB_DIR . '/combine/' . $fileName;

        if ((!is_file($file) || filemtime($file) !== $mtime) && php_sapi_name() !== 'cli') {
            file_put_contents($file, \Web::instance()->minify($result, null, false, WEB_DIR . '/'));
            chmod($file, 0777);
            touch($file, $mtime);
        }

        return BASE_URL . '/combine/' . $fileName . '?t=' . $mtime;
    }

    /**
     * Helper function for AssetsHandler::getCombineLink()
     *
     * @param array $files
     * @param array $result
     * @throws Exception
     * @return int
     */
    protected static function cleanFiles(array $files, &$result)
    {
        $mtime = 0;
        
        // Check that files exists and get biggest file modification time
        foreach ($files as $fileName) {
            if (is_array($fileName)) {
                $mt = static::cleanFiles($fileName, $result);
                if ($mt > $mtime) {
                    $mtime = $mt;
                }
            } else {
                 // In case you pass some argument without quoting "script1.js, script2.js"
                $parts = explode(',', $fileName);

                foreach ($parts as $fileName) {
                    $fileName = preg_replace('/\.\./', '', trim($fileName));
                    $file = WEB_DIR . '/' . $fileName;

                    if (!is_file($file)) {
                        throw new Exception($fileName);
                    }
                    $result[$fileName] = $fileName;
                    if (filemtime($file) > $mtime) {
                        $mtime = filemtime($file);
                    }
                }
            }
        }

        return $mtime;
    }
}