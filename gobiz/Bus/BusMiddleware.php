<?php

namespace Gobiz\Bus;

use Closure;

class BusMiddleware
{
    /**
     * @param object $command
     * @param Closure $next
     * @return mixed
     */
    public function handle($command, Closure $next)
    {
        $commandName = is_object($command) ? get_class($command) : (string)$command;
        $middleware = config('bus.listen.'.$commandName, []);

        return BusService::pipeline()
            ->send($command)
            ->through($middleware)
            ->then(function () use ($command, $next) {
                return $next($command);
            });
    }
}
