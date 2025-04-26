<?php

namespace App\Http\Controllers\Api;

use Intervention\Image\Facades\Image;
use Illuminate\Http\Request;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use WebSocket\Client;


class RabbitController extends Controller
{
    public function sendmessage(Request $request)
    {
        $data = $request->validated();
        $fileData = null;

        if ($request->hasFile('image')) {
            $image = $request->file('image');

            $originalName = $image->getClientOriginalName();
            $originalMime = $image->getMimeType();

            $resizedImage = Image::make($image->getPathname())
                ->fit(70, 70, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })
                ->encode('jpg', 90); // Кодируем в JPG с качеством 90

            $fileData = [
                'filename' => $originalName,
                'mime_type' => 'image/jpeg', // Так как encode('jpg', 90) изменяет формат
                'content' => base64_encode($resizedImage->getEncoded())
            ];
        }

        $messageBody =  json_encode([
            'name' => $data['name'],
            'text' => $data['text'],
            'fileData' => $fileData,
        ]);

        $wsUrl = "wss://rabbitmq-ws.prototypecodetest.site/ws";
        $login = env('RABBITMQ_USER');
        $password = env('RABBITMQ_PASSWORD');
        $exchange = "lara-send-comment";
        $routingKey = "comment";

        $client = new Client($wsUrl, [
            'timeout' => 10,
            'headers' => [] // Без стандартных заголовков, RabbitMQ не всегда их любит
        ]);

        try {
            echo "Подключение к WebSocket...\n";

            // 1. Авторизация STOMP
            $connectFrame = "CONNECT\nlogin:$login\npasscode:$password\n\n\x00";
            $client->send($connectFrame);
            echo "Отправлен CONNECT\n";

            // 2. Ожидание ответа от сервера
            $response = $client->receive();
            echo "Ответ на CONNECT: $response\n";

            $sendFrame = "SEND\ndestination:/exchange/$exchange/$routingKey\n\n$messageBody\x00";
            $client->send($sendFrame);
            echo "Сообщение отправлено: $messageBody\n";

            // 4. Закрытие соединения
            $client->close();
            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

}
