<?php
function prepareResult($error, $data, $msg)
{
    return ['error' => $error, 'data' => $data, 'message' => $msg];
}


function generateRandomNumber($len = 12) {
    return Str::random($len);
}
function getUser() {
    return auth('api')->user();
}


function getWhereRawFromRequest(Request $request) {
    

}
