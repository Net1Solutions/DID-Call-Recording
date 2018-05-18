<?php

function sms_curl_post($url, $data)
{
    file_put_contents('sms_curl_post.txt', $url."\n", FILE_APPEND);
    $basicToken = base64_encode('eden_admin:Tria7aNsmrTq4OZKBhlPOAuE2hiRHHZWSPYplsFHKygLUeczGsr424yK2EDnCgnc');

    $headers = ['authorization:Basic ' . $basicToken];
    $ch      = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $output = curl_exec($ch);
    $info   = curl_getinfo($ch);
    file_put_contents('sms_curl_post.txt', $output."\n", FILE_APPEND);
    file_put_contents('sms_curl_post.txt', var_export($info,true)."\n", FILE_APPEND);
    curl_close($ch);

    return ['result' => $output, 'info' => $info, 'http_code' => $info['http_code']];


}

function sms_curl($url,$data,$method='POST'){

    file_put_contents('sms_curl_post.txt', $url."\n", FILE_APPEND);
    $basicToken = base64_encode('eden_admin:Tria7aNsmrTq4OZKBhlPOAuE2hiRHHZWSPYplsFHKygLUeczGsr424yK2EDnCgnc');

    $headers = ['authorization:Basic ' . $basicToken];
    $ch      = curl_init();

    switch($method) {
        case 'GET':
            break;
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data); //设置请求体，提交数据包
            break;
        case 'PUT':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data); //设置请求体，提交数据包
            break;
        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $output = curl_exec($ch);
    $info   = curl_getinfo($ch);
    file_put_contents('sms_curl_post.txt', $output."\n", FILE_APPEND);
    file_put_contents('sms_curl_post.txt', var_export($info,true)."\n", FILE_APPEND);
    curl_close($ch);

    return ['result' => $output, 'info' => $info, 'http_code' => $info['http_code']];
}

function sms_curl_get($url)
{
    $basicToken = base64_encode('eden_admin:Tria7aNsmrTq4OZKBhlPOAuE2hiRHHZWSPYplsFHKygLUeczGsr424yK2EDnCgnc');

    $headers = ['authorization:Basic ' . $basicToken];
    $ch      = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $output = curl_exec($ch);
    curl_close($ch);

    return $output;


}