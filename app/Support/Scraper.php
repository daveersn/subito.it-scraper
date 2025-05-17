<?php

namespace App\Support;

use App\Models\BrowserSocket;
use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Exception\BrowserConnectionFailed;
use HeadlessChromium\Page;
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

        if (! isset($params['userAgent'])) {
            // Instead of Chromium User Agent
            $params['userAgent'] = config('scraper.user_agent');
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

            $this->browser = $this->socket->getBrowser();

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
            $this->socket->update([
                'is_currently_active' => false,
            ]);
        } else {
            $this->browser->close();
        }

        $this->browser = null;

        return $this;
    }

    /**
     * @param  callable(Page, Browser, BrowserSocket): mixed  $callback
     */
    public function wrap(callable $callback): mixed
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        $page = $this->getBrowser()->getPages()[0] ?? $this->getBrowser()->createPage();

        $return = $callback($page, $this->getBrowser(), $this->getSocket());

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
        // Can't instance chrome graphics environment in production mode
        if (app()->isProduction() && isset($params['headless'])) {
            unset($params['headless']);
        }

        // The browser was probably closed, start it again
        $factory = new BrowserFactory(config('scraper.chrome_binary'));
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
