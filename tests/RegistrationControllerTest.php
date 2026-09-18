<?php

namespace App\Tests;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();

        // Build a fresh isolated schema; never remove users from an existing database.
        $container = static::getContainer();

        /** @var EntityManager $em */
        $em = $container->get('doctrine')->getManager();
        $this->userRepository = $container->get(UserRepository::class);

        self::assertTrue($em->getConnection()->getParams()['memory'] ?? false);
        (new \Doctrine\ORM\Tools\SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());
    }

    public function testRegister(): void
    {
        // Register a new user
        $this->client->request('GET', '/register');
        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Register');

        $this->client->submitForm('Register', [
            'registration_form[email]' => 'me@example.com',
            'registration_form[plainPassword]' => 'password',
            'registration_form[agreeTerms]' => true,
        ]);

        self::assertResponseRedirects();

        // Ensure the response redirects after submitting the form, the user exists, and is not verified
        // self::assertResponseRedirects('/');  @TODO: set the appropriate path that the user is redirected to.
        self::assertCount(1, $this->userRepository->findAll());
        self::assertFalse(($user = $this->userRepository->findAll()[0])->isVerified());
        self::assertTrue(\Symfony\Component\Uid\Ulid::isValid($user->getCode()));

        // Ensure the verification email was sent
        // Use either assertQueuedEmailCount() || assertEmailCount() depending on your mailer setup
        // self::assertQueuedEmailCount(1);
        self::assertEmailCount(1);

        self::assertCount(1, $messages = $this->getMailerMessages());
        self::assertEmailAddressContains($messages[0], 'from', 'tacman@gmail.com');
        self::assertEmailAddressContains($messages[0], 'to', 'me@example.com');
        self::assertEmailTextBodyContains($messages[0], 'This link will expire in 1 hour.');

        // Login the new user
        $this->client->followRedirect();
        $this->client->loginUser($user);

        // Get the verification link from the email
        /** @var TemplatedEmail $templatedEmail */
        $templatedEmail = $messages[0];
        $messageBody = $templatedEmail->getHtmlBody();
        self::assertIsString($messageBody);

        preg_match('#(http://localhost/verify/email.+)">#', $messageBody, $resetLink);

        // "Click" the link and see if the user is verified
        $this->client->request('GET', $resetLink[1]);
        $this->client->followRedirect();

        self::assertTrue(static::getContainer()->get(UserRepository::class)->findAll()[0]->isVerified());
    }
}
