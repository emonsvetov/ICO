<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;

class CronJobsController extends Controller
{

    /**
     * @return array
     */
    public function readList( $response = TRUE )
    {
        switch (env('APP_ENV')) {
            case 'production':
                $command = 'sudo -S -u ubuntu crontab -l 2>&1';
            break;

            case 'local':
                $command = 'sudo -S -u root crontab -l 2>&1';
            break;

            default:
                $command = 'sudo -S -u ubuntu crontab -l 2>&1';

        }

        exec($command, $output, $return_var);
        $cron_jobs = [];
        if (!empty($output)) {
            $comment = '';
            foreach ($output as $k => $item) {
                $val = [];
                $value = trim($item);
                if ((!empty($value) && $value[0] !== '#' ) || $this->isCommand($value)) {
                    if ($comment) {
                        $val['command'] = false;
                        $val['value'] = str_replace('#', '', $comment);
                        array_push($cron_jobs, $val);
                    }
                    $comment = '';
                    $val['command'] = true;
                    $val['value'] = $value;
                    $val['color'] = $value[0] == '#' ? 'gray' : 'black';
                    array_push($cron_jobs, $val);
                    continue;
                } elseif (!empty($value)) {
                    $comment .= $value . PHP_EOL . '<br/>';
                }
            }
        }
        $skip_keys = [];
        $cron_comment = '';
        foreach ($cron_jobs as $key => $cron_job) {
            if ($cron_job['command'] !== true) {
                while (true) {
                    if($cron_jobs[$key]['command'] !== true) {
                        $skip_keys[] = $key;
                        $cron_comment .= "{$cron_jobs[$key]['value']}<br>";
                        $key++;
                        continue;
                    }
                    break;
                }
            }
            if ($cron_job['command'] === true) {
                $cron_jobs[$key]['comment'] = $cron_comment;
                $cron_comment = '';
            }

        }

        return $response ? response(['data' => $cron_jobs]) : $cron_jobs;
    }

    /**
     * @param $value
     * @return bool
     */
    private function isCommand($value)
    {
        $value = ltrim($value, '#');
        $value = trim($value);
        $time = '[\*|\,|\-|\d|\/)]+\s';
        $pattern = "/^{$time}{$time}{$time}{$time}{$time}.*/";
        preg_match($pattern, $value, $matches);
        if (isset($matches[0]) && !empty($matches[0])) {
            return true;
        }
    }

    /**
     * @param $key
     * @return mixed
     */
    public function run($key)
    {
        $key--;
        $crons = $this->readList($response = FALSE);
        $cron = $crons[$key]['value'];
        $time = '[\*|\,|\-|\d|\/)]+\s';
        $pattern = "/^[\#\s]*{$time}{$time}{$time}{$time}{$time}(.*)/";
        preg_match($pattern, $cron, $matches);
        if (isset($matches[1])){
            $command = $matches[1] . ' 2>&1';
            exec($command, $output, $return_var);
        }
        else {
            $output = 'Error parse command';
        }

        return response(compact('output'));
    }
}
