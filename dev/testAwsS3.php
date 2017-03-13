<?php


require '../vendor/autoload.php';

$key = '<key here>';
$secret = '<secret key here>';
$region = 'us-east-1';
$bucket = 'spotxchange-reports';

$s3 = new Aws\S3\S3Client([
    'credentials' => [
        'key' => $key,
        'secret' => $secret,
    ],
    'region' => $region,
    'version' => 'latest',
]);

// http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#listobjects
// look at Prefix option
$objects = $s3->listObjects([
    'Bucket' => $bucket
]);

var_dump($objects);
