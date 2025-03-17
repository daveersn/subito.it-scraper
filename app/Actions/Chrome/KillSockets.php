<?php

namespace App\Actions\Chrome;

use App\Models\BrowserSocket;
use HeadlessChromium\Exception\BrowserConnectionFailed;
use Lorisleiva\Actions\Concerns\AsAction;

class KillSockets
{
    use AsAction;

    public $commandSignature = 'chrome:kill-sockets';

    public function handle(): array
    {
        BrowserSocket::get(['id', 'uri'])->each(function (BrowserSocket $socket) {
            try {
                $socket->getBrowser()->close();
            } catch (BrowserConnectionFailed) {
            }

            $socket->delete();
        });
    }
}
