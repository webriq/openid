<?php

namespace Grid\OpenId\Authentication;

use Zork\Stdlib\String;
use Zend\Authentication\Result;
use Zork\Model\ModelAwareTrait;
use Zork\Model\ModelAwareInterface;
use Zork\Model\Structure\StructureAbstract;
use Grid\User\Model\User\Structure as UserStructure;
use Zork\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zork\Factory\AdapterInterface as FactoryAdapterInterface;
use Zend\Authentication\Adapter\AdapterInterface as AuthAdapterInterface;

/**
 * AutoLoginAdapter
 *
 * @author David Pozsar <david.pozsar@megaweb.hu>
 */
class OpenIdAdapter extends StructureAbstract
                 implements ModelAwareInterface,
                            AuthAdapterInterface,
                            FactoryAdapterInterface,
                            ServiceLocatorAwareInterface
{

    use ModelAwareTrait,
        ServiceLocatorAwareTrait;

    /**
     * @var string
     */
    protected $openid_mode;

    /**
     * @var string
     */
    protected $openid_identity;

    /**
     * Return true if and only if $options accepted by this adapter
     * If returns float as likelyhood the max of these will be used as adapter
     *
     * @param  array $options;
     * @return float
     */
    public static function acceptsOptions( array $options )
    {
        return isset( $options['openid_identity'] );
    }

    /**
     * Return a new instance of the adapter by $options
     *
     * @param  array $options;
     * @return Grid\MultisitePlatform\Authentication\AutoLoginAdapter
     */
    public static function factory( array $options = null )
    {
        return new static( $options );
    }

    /**
     * Is registration enabled
     *
     * @return bool
     */
    protected function isRegistrationEnabled()
    {
        $config = $this->getServiceLocator()
                       ->get( 'Config'  )
                            [ 'modules' ]
                            [ 'Grid\User'    ];

        return ! empty( $config['features']['registrationEnabled'] );
    }

    /**
     * Performs an authentication attempt
     *
     * @return \Zend\Authentication\Result
     * @throws \Zend\Authentication\Adapter\Exception\ExceptionInterface
     *         If authentication cannot be performed
     */
    public function authenticate()
    {
        $registered = false;
        $model      = $this->getModel();
        $mode       = $this->openid_mode;
        $openId     = $this->openid_identity;
        $consumer   = new Consumer\FederatedConsumer;
        $ax         = new Extension\Ax( array(
            'email'     => true,
            'firstname' => false,
            'lastname'  => false,
            'language'  => false,
        ) );

        $consumer->setHttpClient(
            $this->getServiceLocator()
                 ->get( 'Zend\Http\Client' )
        );

        $success = ( $mode == 'id_res' ) ?
            $consumer->verify( (array) $this->getOptions(), $openId, $ax ) :
            $consumer->login( $openId, null, null, $ax,
                              $this->getServiceLocator()
                                   ->get( 'Response' ) );

        if ( ! $success )
        {
            return new Result(
                Result::FAILURE_CREDENTIAL_INVALID,
                null,
                array(
                    (string) $consumer->getError(),
                )
            );
        }

        $data = $ax->getProperties();

        if ( empty( $data['email'] ) )
        {
            return new Result(
                Result::FAILURE_CREDENTIAL_INVALID,
                null
            );
        }

        $user = $model->findByEmail( $data['email'] );

        if ( empty( $user ) )
        {
            if ( ! $this->isRegistrationEnabled() )
            {
                return new Result(
                    Result::FAILURE_IDENTITY_NOT_FOUND,
                    null
                );
            }

            $displayName = null;

            if ( ! empty( $data['firstname'] ) && ! empty( $data['lastname'] ) )
            {
                $displayName = $data['firstname'] . ' ' .
                               $data['lastname'];
            }
            else if ( ! empty( $data['firstname'] ) )
            {
                $displayName = $data['firstname'];
            }
            else if ( ! empty( $data['lastname'] ) )
            {
                $displayName = $data['lastname'];
            }
            else
            {
                $displayName = preg_replace( '/@.*$/', '', $data['email'] );
            }

            $i = 1;
            $displayName    = UserStructure::trimDisplayName( $displayName );
            $originalName   = $displayName;

            while ( ! $model->isDisplayNameAvailable( $displayName ) )
            {
                $displayName = $originalName . ' ' . ++$i;
            }

            $user = $model->create( array(
                'confirmed'     => true,
                'status'        => 'active',
                'displayName'   => $displayName,
                'email'         => $data['email'],
                'locale'        => ! empty( $data['language'] )
                                   ? $data['language']
                                   : (string) $this->getServiceLocator()
                                                   ->get( 'Locale' ),
                'password'      => String::generateRandom( 10 ),
            ) );

            if ( $user->save() )
            {
                $registered = true;
            }
            else
            {
                return new Result(
                    Result::FAILURE_UNCATEGORIZED,
                    null
                );
            }
        }

        if ( empty( $user->id ) || $user->isBanned() )
        {
            return new Result(
                Result::FAILURE_CREDENTIAL_INVALID,
                null
            );
        }
        else if ( $user->isInactive() )
        {
            $user->makeActive();

            if ( ! $user->save() )
            {
                return new Result(
                    Result::FAILURE_UNCATEGORIZED,
                    null
                );
            }
        }

        $model->associateIdentity( $user->id, $openId );

        return new Result(
            Result::SUCCESS,
            $user,
            array(
                'loginWith'     => 'openid',
                'registered'    => $registered,
            )
        );
    }

}
