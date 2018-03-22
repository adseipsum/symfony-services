<?php

namespace  AppBundle\Controller\FrontApi;

use AppBundle\Extension\ApiResponse;
use AppBundle\Extension\JSonBody;
use FOS\UserBundle\Util\TokenGenerator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Security\Core\User\UserInterface;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use UserBundle\Entity\CbUser;
use UserBundle\Entity\Validation\UserRegistrationRequest;
use UserBundle\Repository\UserModel;


class AuthController extends Controller
{
    /**
     * @Route("/v1/getuserinfo", name="frontapi_get_user_info")
     * @Method("GET")
     * @param UserInterface $user
     * @return JsonResponse
     */
    public function actionGetUserInfo(UserInterface $user){
        return ApiResponse::resultValue($user);
    }

    /**
     * @Route("/v1/register", name="auth_api_register")
     * @Method("POST")
     *
     * JSON
     *{
     *   email:
     *   password:
     *   title:
     *   firstName:
     *   lastName:
     *   countryId:
     *   challenge:
     *   }
     *
     * @return JsonResponse
     */
    public function actionRegister(Request $request)
    {
        try {
            $ip = $request->getClientIp();
            $cb = $this->get('couchbase.connector');
            $validator = $this->get('validator');
            $userManager = $this->get('user_bundle.couchbase_user_manager');

            $json = new JSonBody($request);


            $userReg = new UserRegistrationRequest();
            $userReg->email = $json->get('email');
            $userReg->password = $json->get('password');
            $userReg->title = $json->get('title');
            $userReg->firstName = $json->get('firstName');
            $userReg->lastName = $json->get('lastName');
            $userReg->countryId = $json->get('countryId');
            $userReg->challenge = $json->get('challenge');
            $userReg->challengeGenerated = self::genChallengeForIp($request->getClientIp());


            $errors = $validator->validate($userReg);

            if (count($errors) > 0)
            {
                /*
                 * Uses a __toString method on the $errors variable which is a
                 * ConstraintViolationList object. This gives us a nice string
                 * for debugging.
                 */
                $ret = [];
                foreach ($errors as $error)
                {
                    $line = $error->getPropertyPath().":".$error->getMessage();
                    $ret[] = $line;
                }

//                return ApiResponse::resultErrorMultiple(422, $ret);
                return ApiResponse::resultErrorMultiple(422, "WTF?");
            }



            $mdUserAuth = new UserModel($cb);
            $oldUser = $mdUserAuth->getUserByEmailInternal($userReg->email);
            if($oldUser != null)
            {
                return ApiResponse::resultError(422,
                    "User with this email already exists");
            }

            $user = new CbUser();
            //I have set all requested data with the user's username
            //modify here with relevant data
            $user->setUsername($mdUserAuth->generateUsernameInternal($userReg->email));
            $user->setEmail($userReg->email);
            $user->setPlainPassword($userReg->password);
            $user->setEnabled(true);
 //           $user->addRole(CbUser::ROLE_USER);
 //           $user->setEmailVerificationStatus(CbUser::EMAIL_STATUS_EMAIL_WAIT_VERIFICATION);


            $tokenGenerator = new TokenGenerator();
            $urlConfirmBase = $this->getParameter('auth_confirm_url');
            $token = $tokenGenerator->generateToken();
            $email = $userReg->email;
            $urlConfirm = "$urlConfirmBase?email=$email&code=$token";





            return ApiResponse::resultOk();
        }
        catch(\Exception $e)
        {
            return ApiResponse::resultException($e);
        }
    }

    /**
     * @Route("/v1/email/confirm", name="auth_api_confirm_email")
     * @Method("POST")
     *
     * {
     *  "email":"alexey.kruchenok@gmail.com",
     *  "token":"kpsppaY66uxqtVsz2e5vCdHHJAMIj2DLh1FCAGjaGHQ"
     *  }
     */
    public function actionConfirmEmail(Request $request)
    {
        $cb = $this->get('couchbase.connector');
        $mdUserAuth = new UserModel($cb);

        try {
            $json = new JSonBody($request);
            $email = $json->getReq('email');
            $token = $json->getReq('token');

            $user = $mdUserAuth->getUserByEmail($email);

            if($user == null)
            {
                return ApiResponse::resultNotFound();
            }
            else if ($user->getConfirmationToken() != $token)
            {
                return ApiResponse::resultError(422, 'Token not match');
            }

            $user->setEmailVerificationStatus(CbUserAuth::EMAIL_STATUS_EMAIL_VERIFIED);
            $mdUserAuth->update($user);

            return ApiResponse::resultOk();

        }
        catch(\Exception $e)
        {
            return ApiResponse::resultException($e);
        }

    }

}