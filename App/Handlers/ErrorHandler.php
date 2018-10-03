<?php

namespace App\Handlers;

use Base;

class ErrorHandler
{
    /**
     * Handles ONERROR event by triggering report() and showing error page
     *
     * @param Base $fw
     */
    public function handle(Base $fw)
    {
        // Report only 5xx server errors
        if (substr($fw->get('ERROR.code'), 0, 1) == 5) {
            $this->report($fw);
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        if (php_sapi_name() === 'cli') {
            dd($fw->get('ERROR'));
        } elseif ($fw->ajax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => $fw->{'ERROR.text'},
            ]);
        } else {
            echo view('error.html');
        }
    }

    /**
     * You can write logs, send to your email or to some aggregator
     * Only 5xx server errors are reported
     *
     * @param Base $fw
     */
    protected function report(Base $fw)
    {
        // Log file looks like this: "error_500_2018-07-22.log"
        $logNameParts = ['error', $fw->get('ERROR.code'), date('Y-m-d')];

        $logger = new \Log(implode('_', $logNameParts) . '.log');
        $logger->write($fw->get('ERROR.text') . PHP_EOL . $fw->get('ERROR.trace'));
    }
}
