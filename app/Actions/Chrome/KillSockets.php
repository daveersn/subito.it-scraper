<?php

namespace App\Actions\Chrome;

use App\Models\BrowserSocket;
use HeadlessChromium\Exception\BrowserConnectionFailed;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Lorisleiva\Actions\Concerns\AsAction;

class KillSockets
{
    use AsAction;

    public $commandSignature = 'chrome:kill-sockets {--f|force}';

    public function handle(bool $force = false): void
    {
        BrowserSocket::all(['id', 'uri'])
            ->each(function (BrowserSocket $socket) use ($force) {
                if (! $force && $socket->refresh()->is_currently_active) {
                    return;
                }

                try {
                    $socket->getBrowser()->close();
                } catch (BrowserConnectionFailed) {
                }

                $socket->delete();
            });

        if ($force) {
            foreach (array_keys(GetSockets::run()) as $socket) {
                Process::run("kill -9 $socket");
            }

            $unkillableSockets = GetSockets::run();

            if (count($unkillableSockets)) {
                throw new \Exception('Error killing process pids: '.implode(', ', array_keys($unkillableSockets)));
            }
        }
    }

    public function asCommand(Command $command): void
    {
        $this->handle((bool) $command->argument('force'));
    }
}
