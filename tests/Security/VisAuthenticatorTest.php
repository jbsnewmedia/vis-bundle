<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Security;

use JBSNewMedia\VisBundle\Security\VisAuthenticator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Contracts\Translation\TranslatorInterface;

class VisAuthenticatorTest extends TestCase
{
    private UrlGeneratorInterface $urlGenerator;
    private TranslatorInterface $translator;
    private VisAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->authenticator = new VisAuthenticator($this->urlGenerator, $this->translator);
    }

    public function testSupports(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'vis_login');
        $request->setMethod('POST');
        $this->assertTrue($this->authenticator->supports($request));

        $request->setMethod('GET');
        $this->assertFalse($this->authenticator->supports($request));

        $request->setMethod('POST');
        $request->attributes->set('_route', 'other_route');
        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testAuthenticate(): void
    {
        $request = $this->createMock(Request::class);
        $session = $this->createMock(SessionInterface::class);
        $payload = new InputBag([
            '_username' => 'test@example.com',
            '_password' => 'password123',
            '_csrf_token' => 'token123'
        ]);

        $request->method('getPayload')->willReturn($payload);
        $request->method('getSession')->willReturn($session);

        $passport = $this->authenticator->authenticate($request);

        $this->assertEquals('test@example.com', $passport->getBadge(UserBadge::class)->getUserIdentifier());
        $this->assertInstanceOf(PasswordCredentials::class, $passport->getBadge(PasswordCredentials::class));
        $this->assertInstanceOf(CsrfTokenBadge::class, $passport->getBadge(CsrfTokenBadge::class));
        $this->assertInstanceOf(RememberMeBadge::class, $passport->getBadge(RememberMeBadge::class));
    }

    public function testOnAuthenticationSuccess(): void
    {
        $request = $this->createMock(Request::class);
        $session = $this->createMock(SessionInterface::class);
        $token = $this->createMock(TokenInterface::class);
        $firewallName = 'main';

        $request->method('getSession')->willReturn($session);
        $this->urlGenerator->method('generate')->with('vis')->willReturn('/vis');
        $this->translator->method('trans')->willReturn('Success');

        $response = $this->authenticator->onAuthenticationSuccess($request, $token, $firewallName);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('/vis', $data['redirect']);
    }

    public function testOnAuthenticationFailure(): void
    {
        $request = $this->createMock(Request::class);
        $exception = $this->createMock(AuthenticationException::class);

        $exception->method('getMessageKey')->willReturn('Invalid credentials.');
        $exception->method('getMessageData')->willReturn([]);
        $this->translator->method('trans')->willReturn('Error');

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('invalid', $data);
    }

    public function testGetLoginUrl(): void
    {
        $request = new Request();
        $this->urlGenerator->method('generate')->with('vis_login')->willReturn('/login');

        $method = new \ReflectionMethod(VisAuthenticator::class, 'getLoginUrl');
        $method->setAccessible(true);
        $url = $method->invoke($this->authenticator, $request);

        $this->assertEquals('/login', $url);
    }
    public function testOnAuthenticationSuccessWithTargetPath(): void
    {
        $request = $this->createMock(Request::class);
        $session = $this->createMock(SessionInterface::class);
        $token = $this->createMock(TokenInterface::class);
        $firewallName = 'main';

        $request->method('getSession')->willReturn($session);
        // TargetPathTrait uses session to store target path
        $session->method('get')->with('_security.main.target_path')->willReturn('/target');
        $this->translator->method('trans')->willReturn('Success');

        $response = $this->authenticator->onAuthenticationSuccess($request, $token, $firewallName);

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('/target', $data['redirect']);
    }
}
