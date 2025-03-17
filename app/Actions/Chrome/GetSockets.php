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
        $socketUris = Process::run("ss -lntp | grep -i chrome | awk '{print $4}'")->output();

        return array_filter(array_map('trim', explode("\n", $socketUris)));
    }

    public function asCommand(Command $command): void
    {
        $this->printPrettyJson($this->handle(), $command);
    }
}
