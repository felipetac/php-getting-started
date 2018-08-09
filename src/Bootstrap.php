<?php
namespace MyApp;

use \Silex\Application;
use \Silex\Provider\MonologServiceProvider;
use \Silex\Provider\TwigServiceProvider;
use \Silex\Provider\SessionServiceProvider;
use \Silex\Provider\SecurityServiceProvider;
use \Silex\Provider\AssetServiceProvider;
use \Symfony\Component\HttpFoundation\Request;

class Bootstrap
{
    public static function run()
    {
        $app = new Application();
        $app['debug'] = true;

        // Register the monolog logging service
        $app->register(
            new MonologServiceProvider(),
            array('monolog.logfile' => 'php://stderr')
        );

        // Register view rendering
        $app->register(
            new TwigServiceProvider(),
            array('twig.path' => '../web/views')
        );

        $app->register(
            new AssetServiceProvider(),
            array(
                'assets.version' => 'v1',
                'assets.version_format' => '%s?version=%s',
                'assets.named_packages' => array(
                    'css' => array(
                        'version' => 'css2',
                        'base_path' => '/stylesheets'
                    ),
                    'images' => array('base_path' => '/images')
                )
            )
        );

        $app->register(new SessionServiceProvider());

        $app->register(
            new SecurityServiceProvider(),
            array(
                'security.firewalls' => array(
                    'login' => array(
                        'pattern' => '^/login$',
                    ),
                    'secured' => array(
                        'pattern'   => '^.*$',
                        'form'      => array(
                            'login_path' => '/login',
                            'check_path' => '/login_check'
                        ),
                        'logout'    => array(
                            'logout_path' => '/logout',
                            'invalidate_session' => true
                        ),
                        'users'     => array(
                            // raw password is foo
                            'admin' => array(
                                'ROLE_ADMIN',
                                '$2y$10$3i9/lVd8UOFIJ6PAMFt8gu3/r5g0qeCJvoSlLCsvMTythye19F77a'
                            ),
                        )
                    )
                )
            )
        );

        // Our web handlers

        $app->get(
            '/',
            function () use ($app) {
                $app['monolog']->addDebug('logging output.');
                return $app['twig']->render('index.twig');
            }
        );

        $app->get(
            '/login',
            function (Request $request) use ($app) {
                return $app['twig']->render(
                    'login.twig',
                    array(
                        'error'         => $app['security.last_error']($request),
                        'last_username' => $app['session']->get(
                            '_security.last_username'
                        ),
                    )
                );
            }
        )->bind(login);

        $app->run();
    }
}
