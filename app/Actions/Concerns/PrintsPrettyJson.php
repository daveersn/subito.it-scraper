<?php

namespace App\Actions\Concerns;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Pipeline;

trait PrintsPrettyJson
{
    public function printPrettyJson(mixed $value, Command $command): void
    {
        $command->line(Pipeline::send($value)
            ->through([
                fn (mixed $value, Closure $next) => $next(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)),
                fn (mixed $value, Closure $next) => $next(preg_replace_callback('/(".*?"|\b\d+\b|null|true|false)/', function ($matches) {
                    $match = $matches[0];
                    if (str_starts_with($match, '"')) { // Strings
                        return "<fg=green>$match</>";
                    } elseif (preg_match('/\b(true|false|null)\b/', $match)) { // Bool and null
                        return "<fg=yellow>$match</>";
                    } else { // Numbers
                        return "<fg=blue>$match</>";
                    }
                }, $value)),
                fn (mixed $value, Closure $next) => $next(preg_replace('/^(\s*)(".*?"):/m', '$1<fg=cyan>$2</>: ', $value)),
            ])
            ->thenReturn());
    }
}
