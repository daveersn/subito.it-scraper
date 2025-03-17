<?php

namespace App\Actions\Chrome;

use App\Actions\Concerns\PrintsPrettyJson;
use App\Models\BrowserSocket;
use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsAction;

class GetOrphanSockets
{
    use AsAction, PrintsPrettyJson;

    public $commandSignature = 'chrome:orphan';

    /**
     * @return array<BrowserSocket>
     */
    public function handle(): array
    {
        $sockets = array_values(GetSockets::run());

        $orphanSockets = BrowserSocket::all(['id', 'uri'])
            ->filter(function (BrowserSocket $socket) use ($sockets) {
                $uri = str($socket->uri)
                    ->after('ws://')
                    ->before('/')
                    ->toString();

                return ! in_array($uri, $sockets);
            });

        return $orphanSockets->all();
    }

    public function asCommand(Command $command): void
    {
        $this->printPrettyJson($this->handle(), $command);
    }
}
