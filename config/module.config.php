<?php

return array(
    'router' => array(
        'routes' => array(
            'Grid\OpenId\Authentication\CustomProvider' => array(
                'type'      => 'Zend\Mvc\Router\Http\Segment',
                'options'   => array(
                    'route'     => '/app/:locale/login-with/openid',
                    'defaults'  => array(
                        'controller'    => 'Grid\OpenId\Controller\Authentication',
                        'action'        => 'custom-provider',
                    ),
                ),
            ),
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'Grid\OpenId\Controller\Authentication' => 'Grid\OpenId\Controller\AuthenticationController',
        ),
    ),
    'factory' => array(
        'Grid\User\Model\Authentication\AdapterFactory' => array(
            'adapter'    => array(
                'openId' => 'Grid\OpenId\Authentication\OpenIdAdapter',
            ),
        ),
    ),
    'modules'   => array(
        'Grid\User'  => array(
            'features' => array(
                'loginWith' => array(
                    'Google'    => array(
                        'enabled'   => true,
                        'route'     => 'Grid\User\Authentication\LoginWidth',
                        'options'   => array(
                            'query' => array(
                                'openid_identity' => urlencode( 'https://www.google.com/accounts/o8/id' ),
                            ),
                        ),
                    ),
                    'Yahoo'     => array(
                        'enabled'   => true,
                        'route'     => 'Grid\User\Authentication\LoginWidth',
                        'options'   => array(
                            'query' => array(
                                'openid_identity' => urlencode( 'http://me.yahoo.com' ),
                            ),
                        ),
                    ),
                    'OpenID'    => array(
                        'enabled'   => true,
                        'route'     => 'Grid\OpenId\Authentication\CustomProvider',
                    ),
                ),
            ),
        ),
    ),
    'form' => array(
        'Grid\OpenId\CustomProvider' => array(
            'attributes' => array(
                'method' => 'GET',
            ),
            'elements'  => array(
                'returnUri' => array(
                    'spec'  => array(
                        'type'      => 'Zork\Form\Element\Hidden',
                        'name'      => 'returnUri',
                    ),
                ),
                'openid_identity'  => array(
                    'spec'  => array(
                        'type'      => 'Zork\Form\Element\Url',
                        'name'      => 'openid_identity',
                        'options'   => array(
                            'label' => 'user.form.openId.identity',
                        ),
                    ),
                ),
                'submit' => array(
                    'spec'  => array(
                        'type'      => 'Zork\Form\Element\Submit',
                        'name'      => 'submit',
                        'options'   => array(
                            'text_domain'   => 'default',
                        ),
                        'attributes'    => array(
                            'value'     => 'default.ok',
                        ),
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'template_map' => array(
            'grid/open-id/authentication/custom-provider' => __DIR__ . '/../view/grid/open-id/authentication/custom-provider.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
);
