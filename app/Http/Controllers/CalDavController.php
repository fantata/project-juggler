<?php

namespace App\Http\Controllers;

use App\Services\CalDav\LaravelAuthBackend;
use Sabre\CalDAV;
use Sabre\DAV;
use Sabre\DAVACL;

class CalDavController extends Controller
{
    public function handle()
    {
        $pdo = \DB::connection()->getPdo();

        // Backends
        $authBackend = new LaravelAuthBackend();
        $principalBackend = new DAVACL\PrincipalBackend\PDO($pdo);
        $calendarBackend = new CalDAV\Backend\PDO($pdo);

        // Directory tree
        $tree = [
            new DAVACL\PrincipalCollection($principalBackend),
            new CalDAV\CalendarRoot($principalBackend, $calendarBackend),
        ];

        // Build sabre/dav's own Request from PHP globals
        $sabreRequest = \Sabre\HTTP\Sapi::getRequest();
        $sabreResponse = new \Sabre\HTTP\Response();

        $server = new DAV\Server($tree);
        $server->setBaseUri('/dav/');

        // Plugins
        $server->addPlugin(new DAV\Auth\Plugin($authBackend));
        $server->addPlugin(new CalDAV\Plugin());
        $server->addPlugin(new DAVACL\Plugin());
        $server->addPlugin(new CalDAV\Schedule\Plugin());
        $server->addPlugin(new DAV\Sync\Plugin());
        $server->addPlugin(new DAV\Browser\Plugin());
        $server->addPlugin(new CalDAV\ICSExportPlugin());
        $server->addPlugin(new DAV\PropertyStorage\Plugin(
            new DAV\PropertyStorage\Backend\PDO($pdo)
        ));

        $server->httpRequest = $sabreRequest;
        $server->httpResponse = $sabreResponse;

        $server->exec();

        // Send sabre/dav's response back through Laravel
        return response($sabreResponse->getBody(), $sabreResponse->getStatus())
            ->withHeaders($sabreResponse->getHeaders());
    }
}
