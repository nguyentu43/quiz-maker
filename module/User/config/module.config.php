<?php

namespace User;

use Zend\Router\Http\Literal;
use Zend\Router\Http\Segment;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\Authentication\AuthenticationService;
use User\Controller\UserController;

return [
    'router' => [
        'routes' => [
            'login' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/login',
                    'defaults' => [
                        'controller' => UserController::class,
                        'action'     => 'login',
                    ],
                ],
            ],
            'register' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/register',
                    'defaults' => [
                        'controller' => UserController::class,
                        'action'     => 'register',
                    ],
                ],
            ],
            'logout' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/logout',
                    'defaults' => [
                        'controller' => UserController::class,
                        'action' => 'logout'
                    ]
                ]
            ],
            'active' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/active[/:id]',
                    'defaults' => [
                        'controller' => UserController::class,
                        'action' => 'activeuser'
                    ]
                ]
            ],
            'forgot' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/forgotpassword[/:id]',
                    'defaults' => [
                        'controller' => UserController::class,
                        'action' => 'forgot'
                    ]
                ]
            ],
            'edit_info' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/edit_info',
                    'defaults' => [
                        'controller' => UserController::class,
                        'action' => 'editinfo'
                    ]
                ]
            ],
            'user_manager' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/user',
                    'defaults' => [
                        'controller' => UserController::class,
                        'action' => 'index'
                    ]
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'edit' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/edit[/:id]',
                            'defaults' => [
                                'action' => 'edituser'
                            ]
                        ]
                    ],
                    'create' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/create',
                            'defaults' => [
                                'action' => 'create'
                            ]
                        ]
                    ]
                ]
            ],
            
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
