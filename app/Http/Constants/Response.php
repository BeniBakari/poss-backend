<?php
namespace App\Http\Constants;

class Response{
    public static function response($message, $data, $httpCode = 200){
        return response()->json([
            "success"=> true,
            "message" => $message. " successfully.",
            "data" => $data
        ],$httpCode);
    }
    public static function getUnauthorizedResponse()
    {
        return response()->json([
            "success"=> false,
            "message" => "You can't perform this action.",
        ],403);
    }
    public static function getResourceNotFoundResponse($resource)
    {
        return response()->json([
            "success"=> false,
            "message" => $resource." does not exist.",
        ],404);
    }
    public static function getResourceCreatedResponse($resource,$data)
    {
        return response()->json([
            "success"=> true,
            "message" => $resource." created successfuly.",
            "data"=>$data
        ],201);
    }
    public static function getNotValidResponse($errors)
    {
        return response()->json([
            "success"=> false,
            "message" => "Please check your input(s).",
            "data"=>["error(s)" => $errors]
        ],400);
    }
    public static function getServerErrorResponse()
    {
        return response()->json([
            "success"=> false,
            "message" => "Internal server error occured.",
        ],500);
    }
    public static function getResponseMessage($success, $message, $httpCode = 200){
        return response()->json([
            "success"=> $success,
            "message" =>$message
        ],$httpCode);
    }
    public static function getNoDataAvailable($message){
        return response()->json([
            "success"=> false,
            "message" =>$message
        ],200);
    }

    public static function notValidTime($message){
        return response()->json([
            "success" => false,
            "message" => $message,
            "data" => null
        ],200);
    }

}