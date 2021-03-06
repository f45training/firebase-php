<?php

namespace Kreait\Firebase\Auth;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Kreait\Firebase\Exception\Auth\CredentialsMismatch;
use Kreait\Firebase\Exception\Auth\EmailNotFound;
use Kreait\Firebase\Exception\Auth\InvalidCustomToken;
use Kreait\Firebase\Exception\Auth\InvalidPassword;
use Kreait\Firebase\Exception\Auth\UserDisabled;
use Kreait\Firebase\Exception\AuthException;
use Lcobucci\JWT\Token;
use Psr\Http\Message\ResponseInterface;
class ApiClient
{
    /**
     * @var ClientInterface
     */
    private $client;
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }
    /**
     * Takes a custom token and exchanges it with an ID token.
     *
     * @param Token $token
     *
     * @see https://firebase.google.com/docs/reference/rest/auth/#section-verify-custom-token
     *
     * @throws InvalidCustomToken
     * @throws CredentialsMismatch
     *
     * @return ResponseInterface
     */
    public function exchangeCustomTokenForIdAndRefreshToken(Token $token)
    {
        return $this->request('verifyCustomToken', ['token' => (string) $token, 'returnSecureToken' => true]);
    }
    /**
     * Creates a new user with the given email address and password.
     *
     * @param string $email
     * @param string $password
     *
     * @see https://firebase.google.com/docs/reference/rest/auth/#section-create-email-password
     *
     * @return ResponseInterface
     */
    public function signupNewUser($email = null, $password = null)
    {
        return $this->request('signupNewUser', array_filter(['email' => $email, 'password' => $password, 'returnSecureToken' => true]));
    }
    /**
     * Returns a user for the given email address and password.
     *
     * @param string $email
     * @param string $password
     *
     * @see https://firebase.google.com/docs/reference/rest/auth/#section-sign-in-email-password
     *
     * @throws EmailNotFound
     * @throws InvalidPassword
     * @throws UserDisabled
     *
     * @return ResponseInterface
     */
    public function getUserByEmailAndPassword($email, $password)
    {
        return $this->request('verifyPassword', array_filter(['email' => $email, 'password' => $password, 'returnSecureToken' => true]));
    }
    public function deleteUser(User $user)
    {
        return $this->request('deleteAccount', ['idToken' => (string) $user->getIdToken()]);
    }
    public function changeUserPassword(User $user, $newPassword)
    {
        return $this->request('setAccountInfo', ['idToken' => (string) $user->getIdToken(), 'password' => $newPassword, 'returnSecureToken' => true]);
    }
    public function changeUserEmail(User $user, $newEmail)
    {
        return $this->request('setAccountInfo', ['idToken' => (string) $user->getIdToken(), 'email' => $newEmail, 'returnSecureToken' => true]);
    }
    public function sendEmailVerification(User $user)
    {
        return $this->request('getOobConfirmationCode', ['requestType' => 'VERIFY_EMAIL', 'idToken' => (string) $user->getIdToken()]);
    }
    public function request($uri, array $data)
    {
        try {
            return $this->client->request(RequestMethod::METHOD_POST, $uri, ['json' => $data]);
        } catch (RequestException $e) {
            throw AuthException::fromRequestException($e);
        }
    }
}