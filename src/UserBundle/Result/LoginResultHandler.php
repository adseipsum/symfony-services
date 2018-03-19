<?php

namespace UserBundle\Result;

use AppBundle\Extension\HttpClientExtension;
use AppBundle\Extension\ApiResponse;
use FOS\UserBundle\Model\UserManagerInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Router;
use UserBundle\Entity\CbUser;

class LoginResultHandler implements AuthenticationSuccessHandlerInterface, AuthenticationFailureHandlerInterface {

    //protected $container;

    protected $router;
    protected $authorizationChecker;
    protected $userManager;
    protected $userManipulator;

    protected $httpClientFactory;
    protected $authCookieDomain;
    protected $authFalureRedirect;
    protected $authSucccesRedirect;
    protected $oauthEndpoint;

    protected $oauthClientId;
    protected $oauthSecret;
    protected $oauthCallback;



    public function __construct(Container $container, Router $router, AuthorizationChecker $authorizationChecker,
                                UserManagerInterface $userManager) {
        //$this->container = $container;
        $this->router = $router;
        $this->authorizationChecker = $authorizationChecker;
        $this->userManager = $userManager;
        $this->httpClientFactory = new HttpClientExtension(3, 3, 6);

        $this->authFalureRedirect = $container->getParameter("auth_failure_redirect");
        $this->authSucccesRedirect = $container->getParameter("auth_success_redirect");
        $this->authCookieDomain = $container->getParameter("auth_cookie_domain");

        $this->oauthClientId =  $container->getParameter("internal_oauth_client");
        $this->oauthSecret   =  $container->getParameter("internal_oauth_secret");
        $this->oauthCallback =  $container->getParameter("internal_oauth_callback");
        $this->oauthEndpoint =  $router->generate('fos_oauth_server_token', array(),
            UrlGeneratorInterface::ABSOLUTE_URL);

        $this->userManipulator = $container->get('fos_user.util.user_manipulator');

    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token) {

        //$domain = $this->container->getParameter("auth_cookie_domain");
        //$redirect = $this->container->getParameter("auth_success_redirect");

        $username = $token->getUsername();
        $user =  $this->userManager->findUserByUsername($username);
        //$user = new CbUserAuth();

        /*
         *  Basic idea there, as we treat separate user auth as separate users, we able to generate
         *  1-time password for facebook/google user based on their access token
         */

        //$password = hash("sha11", $this->oauthSecret.$user->getAccessToken());
        $password = sha1($this->oauthSecret.$user->getAccessToken());
        $this->userManipulator->changePassword($username, $password);


        $authRequest = [];
        $authRequest['grant_type'] = 'password';
        $authRequest['client_id'] = $this->oauthClientId;
        $authRequest['client_secret'] = $this->oauthSecret;
        $authRequest['redirect_uri'] = $this->oauthCallback;
        $authRequest['username'] = $username;
        $authRequest['password'] = $password;

        $json_req = json_encode($authRequest);

        $authClient = $this->httpClientFactory->createHttpClientWithRetryHandler(null);

        $headers = ['Content-type' => "application/json;charset=\"utf-8\"",
            'Accept' => 'application/json',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
            'Content-length' => strlen($json_req)
        ];

        $multipart = [];

        foreach($authRequest as $key=>$value)
        {
            $multipart[] = [
                'name'     => $key,
                'contents' => $value
            ];
        }

        $rawResp = $authClient->request('POST', $this->oauthEndpoint,
            [
                'multipart' => $multipart
            ]
        );

        $response = $rawResp->getBody()->getContents();
        $data = base64_encode($response);

        $redirect = $this->authSucccesRedirect;

        return new RedirectResponse($redirect."/".$data);

        #return JsonResponceEdm::resultData($data);

        /*

        $usObj = [];
        $usObj['id'] = $username;
        $usObj['email'] = $user->getEmail();
        $usObj['firstName'] = $user->getFirstName();
        $usObj['lastName'] = $user->getLastName();
        $usObj['registrationDate'] = $user->getLastLogin() != null ? date_format($user->getLastLogin(), 'Y-m-d H:i:s') : null;



        $response = new RedirectResponse($redirect."/".$data);
        $expire = 2147483647;
        $response->headers->setCookie(new Cookie("auth_token", sha1("".time()), $expire, '/', $domain));
        $response->headers->setCookie(new Cookie("auth_object", $data, $expire, '/', $domain));
        */

        #return $response;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {

        $redirect = $this->authFalureRedirect;
        $response = new RedirectResponse($redirect);

        return $response;
    }

}