<?php

namespace App\Actions;

use App\Models\BrowserSocket;
use HeadlessChromium\Exception\BrowserConnectionFailed;
use Illuminate\Support\Facades\Process;
use Lorisleiva\Actions\Concerns\AsAction;

class CheckChromeProcesses
{
    use AsAction;

    public $commandSignature = 'chrome:check';

    public function handle()
    {
        $socketUris = Process::run("ss -lntp | grep -i chrome | awk '{print $4}'")->output();
        dd($socketUris);

        $socketsUri = BrowserSocket::pluck('uri')
            ->map(function (BrowserSocket $browserSocket) {
                try {
                    $browserSocket->getBrowser();

                    return $browserSocket;
                } catch (BrowserConnectionFailed) {
                    return null;
                }
            })
            ->filter();
    }
}
