<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. A "local" driver, as well as a variety of cloud
    | based drivers are available for your choosing. Just store away!
    |
    | Supported: "local", "ftp", "s3", "rackspace"
    |
    */

    'default' => 'uploads',

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => 's3',

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    */

    'disks' => [

//        'local' => [
//            'driver' => 'local',
//            'root' => storage_path('app'),
//        ],

        'uploads' => [
            'driver' => 'local',
            'root' => public_path('uploads'),
            'visibility' => 'public',
        ],

        'thumbnails' => [
            'driver' => 'local',
            'root' => public_path('thumbnails'),
            'visibility' => 'public',
        ],

//        's3' => [
//            'driver' => 's3',
//            'key' => env('AWSS3_KEY'),
//            'secret' => env('AWSS3_SECRET'),
//            'region' => env('AWSS3_REGION'),
//            'bucket' => env('AWSS3_BUCKET'),
//            'version' => 'latest',
//            'visibility' => 'public',
//            'ACL' => 'public-read',
//        ],

    ],

];
