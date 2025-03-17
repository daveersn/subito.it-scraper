<?php

namespace App\Models;

use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Exception\BrowserConnectionFailed;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrowserSocket extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'params' => 'json',
        ];
    }

    /**
     * @throws BrowserConnectionFailed
     */
    public function getBrowser(): Browser
    {
        return BrowserFactory::connectToBrowser($this->uri);
    }
}
