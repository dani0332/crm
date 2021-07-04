<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiController extends Controller
{

    /**
     * return response
     *
     * @param  [type] $data
     * @param  [type] $statusCode
     * @return [type]
     */
    public function respond($data, $statusCode = Response::HTTP_OK)
    {
        return response()->json($data, $statusCode, []);
    }

    /**
     * response for error
     * @param  Exception $e
     * @return Response Json
     */
    public function respondFatalError($e)
    {
        $message = $e->getMessage();
        $errorCode = $e->getCode();

        if ($errorCode === 0 || $errorCode === 1) {
            $errorCode = 422;
        }

        if($e instanceof \Illuminate\Validation\ValidationException) {
            return $this->respondValidationError($e->validator, $errorCode);
        }

        if($e instanceof ValidationException) {
            $exception = $e->getPrevious();
            if($exception) {
                $error = json_decode($exception->getResponse()->getBody(), true);
                $message = arrayKeyExists('message', $error);
            }
        }

        return $this->respondError($message, $errorCode, $e);
    }

    public function respondSuccess($message="Success", $data=[])
    {
        $response = [
            'data' => $data,
            'message' => $message,
        ];

        return response()->json($response, Response::HTTP_OK, []);
    }

    /**
     * return response
     *
     * @param  [type] $data
     * @param  [type] $statusCode
     * @return [type]
     */
    public function respondData($data, $statusCode = Response::HTTP_OK)
    {
        $response = [
            'data' => $data,
        ];

        return response()->json($response, $statusCode, []);
    }

    /**
     * Format error response in json.
     *
     * @param  $e: Exception
     * @return json.
     */
    public function getExceptionErrors($e)
    {
        $message = $e->getMessage();

        $status = ($e->getCode() == 0) ? Response::HTTP_UNPROCESSABLE_ENTITY : $e->getCode();
        $error = [
            'message' => $message,
            'status'    => $e->getCode(),
            'line'    => $e->getLine(),
        ];

        return response()->json($error, $status);
    }
}
