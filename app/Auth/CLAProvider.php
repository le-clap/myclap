<?php

namespace App\Auth;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User;
use LogicException;
use UnexpectedValueException;

/** OIDC (authorization code + PKCE) against CLA. Endpoints per its discovery document. */
class CLAProvider extends AbstractProvider
{
    protected $scopes = ['openid', 'profile', 'email', 'cla'];

    protected $scopeSeparator = ' ';

    protected $usesPKCE = true;

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->issuer().'/authorize/', $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->issuer().'/token/';
    }

    /** Claims come from the signed ID token returned by the token endpoint (no userinfo call). */
    public function user(): User
    {
        if ($this->hasInvalidState()) {
            throw new InvalidStateException;
        }

        $response = $this->getAccessTokenResponse($this->getCode());

        // Verifies signature (RS256), exp, nbf and iat; iss and aud are checked below.
        $claims = (array) JWT::decode($response['id_token'], JWK::parseKeySet($this->jwks(), 'RS256'));

        if ($claims['iss'] !== $this->issuer() || ! in_array($this->clientId, (array) $claims['aud'], true)) {
            throw new UnexpectedValueException('Invalid ID token issuer or audience');
        }

        return $this->userInstance($response, $claims);
    }

    private function jwks(): array
    {
        return Cache::remember('cla.jwks', 3600, fn () => Http::get($this->issuer().'/.well-known/jwks.json')->throw()->json());
    }

    protected function getUserByToken($token): array
    {
        throw new LogicException('Claims are read from the ID token.');
    }

    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map(['id' => $user['sub']]);
    }

    /** No id_token_hint, so CLA asks the user to confirm; then it sends them back to the site. */
    public function logoutUrl(): string
    {
        return $this->issuer().'/logout/?'.http_build_query([
            'client_id' => $this->clientId,
            'post_logout_redirect_uri' => url('/'),
        ]);
    }

    private function issuer(): string
    {
        return rtrim(config('services.cla.issuer'), '/');
    }
}
