<?php
namespace App\Handlers;

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
     * Your routes.ini file should have following route
     *      GET /combine/@hash.@type = App\Handlers\AssetsHandler::combineRoute, 3600 
     *
     * @param array $files
     * @return string
     */
    public static function getCombineLink($files)
    {
        $result = [];
        $mtime = static::cleanFiles($files, $result);
       
        if (!$result) {
            return '';
        }

        $result = array_values($result);

        preg_match('/\w+$/', $result[0], $ext);
        $type = $ext[0];

        if (!in_array($type, ['css', 'js'])) {
            return 'You can pass only CSS and JS files to combine() function';
        }

        $result[] = $mtime;
        $result[] = md5(\Base::instance()->SEED . implode(',', $result));

        return BASE_URL . '/combine/' . urlencode(base64_encode(implode(',', $result))) . '.' . $type;
    }

    /**
     * Helper function for AssetsHandler::getCombineLink()
     *
     * @param array $files
     * @param array $result
     * @return int
     */
    protected static function cleanFiles(array $files, &$result)
    {
        $mtime = 0;
        
        // Remove non-existent files and get biggest file modification time
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

                    if (is_file($file)) {
                        $result[$fileName] = $fileName;
                        if (filemtime($file) > $mtime) {
                            $mtime = filemtime($file);
                        }
                    }
                }
            }
        }

        return $mtime;
    }

    /**
     * Route for getCombineLink()
     * Hash check used to prevent cache overflow (route caching create new chache when any param is changed)
     * 
     * Your routes.ini file should have following route
     *      GET /combine/@hash.@type = App\Handlers\AssetsHandler::combineRoute, 3600 
     *
     * @param \Base $fw
     */
    public static function combineRoute(\Base $fw)
    {
        $files = explode(',', urldecode(base64_decode($fw->{'PARAMS.hash'})));
        $hash = array_pop($files);

        if (md5($fw->SEED . implode(',', $files)) !== $hash) {
            $fw->error(404);
        }
        array_pop($files); // remove mtime

        if (!$files) {
            $fw->error(404);
        }

        echo \Web::instance()->minify($files, null, true, WEB_DIR . '/');
    }
}