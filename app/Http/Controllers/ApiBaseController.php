<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiBaseController extends Controller
{

    const OK_STATUS = 200;
    const NG_STATUS = 209;
    const NOT_FOUND_STATUS = 404;
    const ERROR_STATUS = 500;

    protected $model;

    public function __construct()
    {
        $this->initialize();
    }

    // 初期設定
    protected function initialize()
    {
        try {

            // DBの接続確認
            \DB::select('SHOW TABLES');

        } catch (\Exception $e) {
            throw new \Exception('Database Connection Error.');
        }
    }

    protected function getGitRevision()
    {
        $revision = exec('git rev-parse --short HEAD');

        if (strlen($revision) >= 8) {
            return 'unknown';
        }
    }

    public function responseJson($response, $status = 200)
    {

        if (isset($_SERVER['SERVER_ADDR'])) {
            $server = $_SERVER['SERVER_ADDR'];
        } else {
            $server = config('app.env');
        }

        $git_revision = $this->getGitRevision();

        return [
            'status' => $status,
            'response' => $response,
            'server' => $server,
            'revision' => $git_revision,
        ];

    }

    public function exceptionJson($exception, $status = 500)
    {

        $message = $exception->getMessage();
        $file = $exception->getFile();
        $line = $exception->getLine();

        $message = "{$file}($line):{$message}";

        error_log($message);

        $inquiryCode = $this->createInquiryCode($message);

        $isDebug = config('app.debug');
        if ($isDebug) {
            $response = ['reason_code' => $inquiryCode, 'debug_info' => $message];
        } else {
            $response = ['reason_code' => $inquiryCode];
        }

        if (isset($_SERVER['SERVER_ADDR'])) {
            $server = $_SERVER['SERVER_ADDR'];
        } else {
            $server = config('app.env');
        }

        $git_revision = $this->getGitRevision();

        return [
            'status' => $status,
            'response' => $response,
            'server' => $server,
            'revision' => $git_revision,
        ];

    }

    private function createInquiryCode($message)
    {

        //使用する文字
        $char = 'ABCDEFGHJKLMNPQRSTUVWXYZ';

        $code = '';

        for ($i = 1; $i <= 6; $i++) {
            $index = mt_rand(0, mb_strlen($char) - 1);
            $code .= mb_substr($char, $index, 1);
        }

        $ymd = date('Ymd');

        $code = 'IC-' . $ymd . '-' . $code;

        \Log::warning('InquiryCode:' . $code . ' - ' . $message);

        return $code;
    }

}
