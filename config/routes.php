<?php
declare(strict_types=1);

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes): void {
    $routes->setRouteClass(DashedRoute::class);

    $routes->scope('/', function (RouteBuilder $builder): void {
        $builder->connect('/', ['controller' => 'Overview', 'action' => 'index']);
        $builder->connect('/sign-in', ['controller' => 'SignIn', 'action' => 'index']);
        $builder->connect('/sign-out', ['controller' => 'SignIn', 'action' => 'signOut']);
        $builder->connect('/invoices', ['controller' => 'Invoices', 'action' => 'index']);
        $builder->connect('/invoices/{id}', ['controller' => 'Invoices', 'action' => 'download'])
            ->setPass(['id'])
            ->setPatterns(['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}']);
        $builder->connect('/details', ['controller' => 'Details', 'action' => 'index']);
        $builder->connect('/details/consents', ['controller' => 'Details', 'action' => 'consents']);
        $builder->connect('/details/change', ['controller' => 'Details', 'action' => 'change']);
        $builder->connect('/password', ['controller' => 'Password', 'action' => 'index']);
        // the logos, icons and colours of the operator's profile, which lives outside the web root
        $builder->connect('/branding/{file}', ['controller' => 'Branding', 'action' => 'asset'])
            ->setPass(['file'])
            ->setPatterns(['file' => '[A-Za-z0-9][A-Za-z0-9._-]*']);

        // the addresses of the old portal, kept in bookmarks and on the operators' websites
        $builder->redirect('/index.php', '/', ['status' => 301]);
        $builder->redirect('/menu.php', '/', ['status' => 301]);
        $builder->redirect('/introduction.php/*', '/', ['status' => 301]);
        $builder->redirect('/reports/customer-data.php', '/details', ['status' => 301]);
        $builder->redirect('/administration/password.php', '/password', ['status' => 301]);
        $builder->connect('/reports/invoices.php', ['controller' => 'Invoices', 'action' => 'legacy']);
    });
};
