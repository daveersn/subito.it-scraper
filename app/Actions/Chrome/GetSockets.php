<?php

namespace App\Actions\Chrome;

use App\Actions\Concerns\PrintsPrettyJson;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Lorisleiva\Actions\Concerns\AsAction;

class GetSockets
{
    use AsAction, PrintsPrettyJson;

    public $commandSignature = 'chrome:sockets';

    public function handle(): array
    {
        $socketUris = Process::run("ss -lntp | grep -i chrome | awk '{ match($0, /pid=([0-9]+)/, arr); print arr[1] \" \" $4 }'")->output();

        return collect(explode("\n", $socketUris))
            ->filter()
            ->map(fn (string $socket) => trim($socket))
            ->mapWithKeys(function (string $socket) {
                [$pid, $uri] = explode(' ', $socket);

                return [$pid => $uri];
            })
            ->all();
    }

    public function asCommand(Command $command): void
    {
        $this->printPrettyJson($this->handle(), $command);
    }
}
