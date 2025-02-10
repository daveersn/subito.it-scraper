<?php

namespace App\Scraper;

use App\Models\BrowserSocket;
use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Exception\BrowserConnectionFailed;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Scraper
{
    private ?BrowserSocket $socket = null;

    private ?Browser $browser = null;

    private array $params;

    public static bool $keepAlive = true;

    public function __construct(array $params = [])
    {
        if (! isset($params['keepAlive']) && self::$keepAlive) {
            $params['keepAlive'] = true;
        }

        $this->params = $params;
    }

    public static function make(array $params = []): self
    {
        return new self($params);
    }

    public function connect(): self
    {
        if ($this->isConnected()) {
            return $this;
        }

        try {
            // Get the first available browser
            $this->socket = BrowserSocket::query()
                ->whereIsCurrentlyActive(false)
                ->whereParams(json_encode($this->getParams()))
                ->firstOrFail();

            $this->browser = BrowserFactory::connectToBrowser($this->socket->uri);

            // Set browser as currently active
            $this->socket->update([
                'is_currently_active' => true,
            ]);
        } catch (BrowserConnectionFailed) {
            $this->browser = $this->createBrowser($this->getParams());

            // At this point delete the old unused socket
            $this->socket->delete();
        } catch (ModelNotFoundException) {
            $this->browser = $this->createBrowser($this->getParams());
        }

        return $this;
    }

    public function disconnect(): self
    {
        if (! $this->isConnected()) {
            return $this;
        }

        if (self::$keepAlive) {
            $this->browser->close();
        } else {
            $this->socket->update([
                'is_currently_active' => false,
            ]);
        }

        $this->browser = null;

        return $this;
    }

    public function wrap(callable $callback): mixed
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        $return = $callback($this->getBrowser(), $this->getSocket());

        $this->disconnect();

        return $return;
    }

    public function getBrowser(): ?Browser
    {
        return $this->browser;
    }

    public function getSocket(): ?BrowserSocket
    {
        return $this->socket;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function setParams(array $params): self
    {
        $this->params = $params;
    }

    public function isConnected(): bool
    {
        return (bool) $this->getBrowser();
    }

    private function createBrowser(array $params = []): Browser
    {
        // The browser was probably closed, start it again
        $factory = new BrowserFactory;
        $browser = $factory->createBrowser($params);

        // Save the uri to be able to connect again to browser
        $this->socket = BrowserSocket::create([
            'uri' => $browser->getSocketUri(),
            'is_currently_active' => true,
            'params' => $params,
        ]);

        return $browser;
    }
}
