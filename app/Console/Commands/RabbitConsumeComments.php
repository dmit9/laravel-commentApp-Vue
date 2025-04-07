<?php

namespace App\Console\Commands;

use App\Services\RabbitReserveCommentServices;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use WebSocket\Client;

class RabbitConsumeComments extends Command
{
    protected $signature = 'ws:consume-comments';
    protected $description = 'Consume messages from RabbitMQ over WebSocket';

    public function handle()
    {
        $wsUrl = 'wss://rabbitmq-ws.prototypecodetest.site/ws';
        $login = env('RABBITMQ_USER');
        $password = env('RABBITMQ_PASSWORD');
        $queueName = 'comment';
        $subscriptionId = "sub-1";

        while (true) { // 🔄 Вечный цикл
            try {
                $client = new Client($wsUrl, ['timeout' => 30]);
                $client->send("CONNECT\nlogin:$login\npasscode:$password\nheart-beat:15000,15000\n\n\x00");
                $destination = "/queue/$queueName";
                $client->send("SUBSCRIBE\ndestination:$destination\nid:$subscriptionId\nack:auto\nreceipt:$subscriptionId\n\n\x00");

                while (true) {
                    try {
                        $message = $client->receive();
                        if (preg_match('/\{(.*?)\}/s', $message, $matches)) {
                            $data = json_decode($matches[0], true);
                            (new RabbitReserveCommentServices())->createComment($data);
                        } else {
                            continue;
                        }
                    } catch (\Exception $e) {
                        break;
                    }
                }
            } catch (\Exception $e) {
            }
            sleep(5);
        }
    }
}
