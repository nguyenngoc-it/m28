<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Modules\Service;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param \Throwable $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        if (env('SENTRY_ENABLE') && app()->bound('sentry') && $this->shouldReport($exception)) {
            app('sentry')->captureException($exception);
        }

        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Throwable $exception
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        if (config('app.debug')) {
            return parent::render($request, $exception);
        }

        return Service::app()->response()->error('EXCEPTION', [
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
        ], $this->getHttpCodeByException($exception));
    }

    /**
     * @param Throwable $exception
     * @return int
     */
    protected function getHttpCodeByException(Throwable $exception)
    {
        if ($exception instanceof ModelNotFoundException) {
            return 404;
        }

        return $this->isHttpException($exception) ? $exception->getStatusCode() : 400;
    }
}
