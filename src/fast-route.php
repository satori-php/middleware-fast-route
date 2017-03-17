<?php

/**
 * @author    Yuriy Davletshin <yuriy.davletshin@gmail.com>
 * @copyright 2017 Yuriy Davletshin
 * @license   MIT
 */

declare(strict_types=1);

namespace Satori\Middleware\FastRoute;

use Satori\Application\ApplicationInterface;
use Satori\Http\Request;

/**
 * Initializes the FastRout middleware.
 *
 * @param ApplicationInterface  $app   The application.
 * @param string                $id    The unique name of the middleware.
 * @param array<string, string> $names
 *    The array with names `['router' => 'router']`.
 */
function init(ApplicationInterface $app, string $id, array $names)
{
    $app[$id] = function (\Generator $next) use ($app, $names) {
        $app->notify('start_routing');
        $capsule = yield;
        $router = $app->{$names['router']};
        $route = $router->dispatch(Request\getMethod(), Request\getPath());
        switch ($route[0]) {
            case \FastRoute\Dispatcher::FOUND:
                $capsule['http.status'] = 200;
                $capsule['action'] = $route[1];
                $capsule['uri.parameters'] = $route[2];
                break;

            case \FastRoute\Dispatcher::NOT_FOUND:
                $capsule['http.status'] = 404;
                break;

            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $capsule['http.status'] = 405;
                break;
        }
        $app->notify('finish_routing');
        $next->send($capsule);

        return $next->getReturn();
    };
}
