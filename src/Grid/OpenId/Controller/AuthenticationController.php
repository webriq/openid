<?php

namespace Grid\OpenId\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zork\Session\ContainerAwareTrait as SessionContainerAwareTrait;

/**
 * Authentication
 *
 * @author David Pozsar <david.pozsar@megaweb.hu>
 */
class AuthenticationController extends AbstractActionController
{

    use SessionContainerAwareTrait;

    /**
     * @var string
     */
    const DEFAULT_RETURN_URI = '/';

    /**
     * @var string
     */
    protected $returnUri;

    /**
     * Get return-uri
     *
     * @return string
     */
    public function getReturnUri()
    {
        if ( null === $this->returnUri )
        {
            $request    = $this->getRequest();
            $session    = $this->getSessionContainer();
            $returnUri  = $request->getQuery( 'returnUri' );

            if ( empty( $returnUri ) )
            {
                $returnUri = $request->getPost( 'returnUri' );
            }

            if ( empty( $returnUri ) )
            {
                if ( isset( $session['returnUri'] ) )
                {
                    $returnUri = $session['returnUri'];
                }
                else
                {
                    $returnUri = self::DEFAULT_RETURN_URI;
                }
            }
            else
            {
                $session['returnUri'] = $returnUri;
            }

            if ( empty( $returnUri ) )
            {
                return '/';
            }

            $this->returnUri = $returnUri;
        }

        return $this->returnUri;
    }

    /**
     * Authentication: custom-provider
     */
    public function customProviderAction()
    {
        $auth = $this->getServiceLocator()
                     ->get( 'Zend\Authentication\AuthenticationService' );

        if ( $auth->hasIdentity() )
        {
            return $this->redirect()
                        ->toRoute( 'Grid\User\Authentication\Logout', array(
                            'locale' => (string) $this->locale(),
                        ) );
        }

        /* @var $form \Zend\Form\Form */
        $return  = $this->getReturnUri();
        $form    = $this->getServiceLocator()
                        ->get( 'Form' )
                        ->create( 'Grid\OpenId\CustomProvider', array(
                            'returnUri' => $return,
                        ) );

        $this->plugin( 'layout' )
             ->setMiddleLayout( 'layout/middle/center' );

        $form->setAttribute(
            'action',
            $this->url()
                 ->fromRoute( 'Grid\User\Authentication\LoginWidth', array(
                     'locale' => (string) $this->locale(),
                 ) )
        );

        return array(
            'form' => $form,
        );
    }

}
