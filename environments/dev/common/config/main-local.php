<?php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=88.198.205.122;dbname=bukerdb',
            'username' => 'bookerdb',
            'password' => 'NLUdH9AQga76cNy8bMA6',
            'charset' => 'utf8',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'phantom' => [
            'pathToPhantomJS' => ROOT_DIR.'/bin/phantomjs'
        ],
        'nodeSocket' => [
            'host' => 'b.oldbk.local',
            'allowedServerAddresses' => [
                "localhost",
                "127.0.0.1",
                "b.oldbk.local",
                "::ffff:127.0.0.1"
            ],
            'origin' => '*:*, b.oldbk.local:*, localhost:*',
            'sessionVarName' => 'BUKER_OLDBK',
            'port' => 3001,
            'socketLogFile' => ROOT_DIR.'/tmp/node-socket.log',
        ],
    ],
];
