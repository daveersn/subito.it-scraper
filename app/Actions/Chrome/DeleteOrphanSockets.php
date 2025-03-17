<?php

namespace App\Actions\Chrome;

use Lorisleiva\Actions\Concerns\AsAction;

class DeleteOrphanSockets
{
    use AsAction;

    public $commandSignature = 'chrome:delete-orphan';

    public function handle(): void
    {
        foreach (GetOrphanSockets::run() as $browserSocket) {
            $browserSocket->delete();
        }
    }
}
