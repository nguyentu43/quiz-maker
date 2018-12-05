<?php

namespace Quiz;

use Zend\Router\Http\Segment;
use Zend\Router\Http\Literal;
use Quiz\Controller\IndexController;
use Quiz\Controller\TestController;
use Quiz\Controller\ResultController;
use Quiz\Controller\QuestionController;
use Quiz\Controller\CategoryController;

return [

	'router' => [
		'routes' => [
			'home' => [
				'type' => Literal::class,
				'options' => [
					'route' => '/',
					'defaults' => [
						'controller' => IndexController::class,
						'action' => 'index'
					]
				]
			],
			'test' => [
				'type' => Literal::class,
				'options' => [
					'route' => '/test',
					'defaults' => [
						'controller' => TestController::class,
						'action' => 'index'
					],
				],
				'may_terminate' => true,
				'child_routes' => [
					'create' => [
						'type' => Literal::class,
						'options' =>[
							'route' => '/create',
							'defaults' => [
								'action' => 'create'
							]
						]
					],
					'edit' => [
						'type' => Segment::class,
						'options' =>[
							'route' => '/edit[/:id]',
							'constraints' => [
								'id' => '[0-9]*'
							],
							'defaults' => [
								'action' => 'edit'
							]
						]
					],
					'delete' => [
						'type' => Literal::class,
						'options' =>[
							'route' => '/delete',
							'defaults' => [
								'action' => 'delete'
							]
						]
					],
					'start' => [
						'type' => Segment::class,
						'options' =>[
							'route' => '/start[/:id]',
							'constraints' => [
								'id' => '[0-9]*',
							],
							'defaults' => [
								'action' => 'start'
							]
						]
					],
					'submit' => [
						'type' => Literal::class,
						'options' => [
							'route' => '/submit',
							'defaults' => [
								'action' => 'submit'
							]
						]
					],
					'public' => [
						'type' => Literal::class,
						'options' => [
							'route' => '/public',
							'defaults' => [
								'action' => 'publictest'
							]
						]
					],
					'ajax' => [
						'type' => Literal::class,
						'options' =>[
							'route' => '/ajax',
							'defaults' => [
								'action' => 'ajax'
							]
						]
					],
					'mark' => [
						'type' => Literal::class,
						'options' =>[
							'route' => '/mark',
							'defaults' => [
								'action' => 'mark'
							]
						]
					],
					'resume' => [
						'type' => Literal::class,
						'options' =>[
							'route' => '/resume',
							'defaults' => [
								'action' => 'resume'
							]
						]
					]
				]
			],
			'question' => [
				'type' => Segment::class,
				'options' => [
					'route' => '/question[/:action[/:id]]',
					'defaults' => [
						'controller' => QuestionController::class,
						'action' => 'index'
					]
				]
			],
			'st' => [
				'type' => Literal::class,
				'options' => [
					'route' => '/st',
					'defaults' => [
						'controller' => IndexController::class,
					]
				],
				'may_terminate' => true,
				'child_routes' => [
					'result' => [
						'type' => Literal::class,
						'options' =>[
							'route' => '/result',
							'defaults' => [
								'action' => 'result'
							]
						]
					],
					'enterid' => [
						'type' => Literal::class,
						'options' =>[
							'route' => '/enterid',
							'defaults' => [
								'action' => 'enterid'
							]
						]
					]
				]
			],
			'category' => [
				'type' => Literal::class,
				'options' => [
					'route' => '/category',
					'defaults' => [
						'controller' => CategoryController::class,
						'action' => 'index'
					]
				]
			],
			'result' => [
				'type' => Literal::class,
				'options' => [
					'route' => '/result',
					'defaults' => [
						'controller' => ResultController::class,
					]
				],
				'may_terminate' => true,
				'child_routes' => [
					'list' => [
							'type' => Segment::class,
							'options' =>[
								'route' => '/list[/:test_id]',
								'defaults' => [
									'action' => 'index'
								]
							]
						],
					'detail' => [
						'type' => Segment::class,
						'options' =>[
							'route' => '/detail[/:result_id]',
							'defaults' => [
								'action' => 'detail'
							]
						]
					],
					'delete' => [
							'type' => Literal::class,
							'options' =>[
								'route' => '/delete',
								'defaults' => [
									'action' => 'delete'
								]
							]
						],
					'upload' => [
							'type' => Literal::class,
							'options' =>[
								'route' => '/upload',
								'defaults' => [
									'action' => 'upload'
								]
							]
						],
					'download' => [
							'type' => Literal::class,
							'options' =>[
								'route' => '/download',
								'defaults' => [
									'action' => 'download'
								]
							]
						],
				],
			]
		]
	],
	'view_manager' => [
		'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
		'template_path_stack' => [
			__DIR__ . '/../view'
		],
		'strategies' => [
			'ViewJsonStrategy'
		]
	],
];