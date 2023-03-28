<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Throwable;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Models\User;
use Mail;
use App\Mail\TooManyAttemptMail;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        \League\OAuth2\Server\Exception\OAuthServerException::class
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
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

    public function render($request, Throwable $exception)
    {
        // $this->renderable(function (ThrottleRequestsException $e, $request) {
        //     $user = User::first();
        //     $content = [
        //         "name" => $user->name,
        //         "body" => 'User with email address '.$request->email.' is trying brute force.',
        //     ];

        //     if (env('IS_MAIL_ENABLE', false) == true) {
               
        //         $recevier = Mail::to($user->email)->send(new TooManyAttemptMail($content));
        //     }
        //     return response()->json(prepareResult(true, ["account_locked"=> true,"time"=>date('Y-m-d H:i:s')], trans('translate.too_many_attempts')), config('httpcodes.not_found'));
        // });
        
        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response(['success' => false, 'message' =>'Record not found.', "code" => 404], 404);
            }
        });
        
        $this->renderable(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
            if ($request->is('api/*')) {
                return response(['success' => false, 'message' =>'permission not defined. Please contact to admin.', "code" => 403], 403);
            }
        });

        if($exception instanceof \Illuminate\Auth\AuthenticationException ) {
            return response(['success' => false, 'message' =>'Unauthenticated', "code" => 401], 401);
        }

        if ($exception instanceof \League\OAuth2\Server\Exception\OAuthServerException && $exception->getCode() == 9) {
            return response(['success' => false, 'message' =>'Unauthenticated', "code" => 401], 401);
        }
        
        return parent::render($request, $exception);
    }
}
