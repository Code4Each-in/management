<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use App\Models\ProjectLog;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
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
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function report(Throwable $exception)
    {
        try {

            // Ignore common 404 errors (prevents spam)
            if ($exception instanceof NotFoundHttpException) {
                return parent::report($exception);
            }

            // Short readable message
            $message = Str::limit($exception->getMessage(), 150);

            // Check if same error logged in last 2 minutes
            $exists = ProjectLog::where('type', 'error')
                ->where('message', $message)
                ->where('logged_at', '>=', now()->subMinutes(2))
                ->exists();

            if (!$exists) {

                ProjectLog::create([
                    'project_id' => config('app.project_log_id'),
                    'type' => 'error',
                    'module' => 'system',
                    'message' => $exception->getMessage(),
                    'context' => [
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                        'trace' => $exception->getTraceAsString()
                    ],
                    'logged_at' => now()
                ]);

            }

        } catch (\Throwable $e) {
            // Prevent logger crash loop
        }

        parent::report($exception);
    }
}
