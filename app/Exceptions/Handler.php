<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {

        $this->renderable(function (Throwable $e, $request) {
            if ($request->is('api/*')) {

                if($e instanceof PostTooLargeException){
                    return response()->json([
                        "errors" => "Size of attached file should be less ".ini_get("upload_max_filesize")."b",
                        'code' => 413
                    ], 413);
                }

                $code = $e->getCode();
                $code = $code ?: 500;
                return response()->json([
                    'errors' => isset($e->validator) && $e->validator ? $e->validator->getMessageBag() : $e->getMessage() . (env('APP_ENV') != 'production' ? sprintf(' in line: %d of file: %s', $e->getLine(), $e->getFile()): ''),
                    'code' => $code,
                    'trace' => env('APP_ENV') != 'production' ? $e->getTrace() : ''
                ], $code);
            }
        });

        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
