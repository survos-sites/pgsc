<?php

namespace App\Tests;

use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Browser\Test\HasBrowser;

class PantherTest extends PantherTestCase
{
    use HasBrowser;

//    public function testSomething(): void
//    {
//        $client = static::createPantherClient();
//        $crawler = $client->request('GET', '/');
//        $this->assertSelectorTextContains('h1', 'Hello World');
//    }

    public function testBatsi(): void
    {
        // the home page that browses projects (not fw7)
        $browser = $this->pantherBrowser()
            ->visit('/')
            ->assertOn('/')
            ->takeScreenshot('home.png');

        $browser = $this->pantherBrowser()
            ->visit('/ez/admin/artist')
            ->assertOn('/ez/admin/artist')
            ->takeScreenshot('artist.png');

        return;

        $browser
            ->visit('/en/batsi#tab-locations')
            ->takeScreenshot('basti-locations.png');

        $browser
//            ->waitUntilSeeIn('body', '#tab-artists')
            ->click('#tab-artists') // click on the artists
            ->takeScreenshot('artists.png');

        $browser->click('Artwork')
            ->wait(200) // @todo: wait for the tab 'obras' to be visible in the dom, or the tab to be marked as selected.
            ->takeScreenshot('artwork.png')
        ;

    }

    public function testEz(): void
    {

        // the home page that browses projects (not fw7)
        $browser = $this->pantherBrowser()
            ->visit('/ez/admin')
            ->assertOn('/login')
            ->fillField('Email', 'admin@test.com')
            ->fillField('Password', 'admin')
            ->click('Sign in');
        $browser
//            ->assertAuthenticated()
            ->assertSee('Batsi')
            ->takeScreenshot('ez.dashboard.png');

        $browser->click('Artistas')
            ->takeScreenshot('artists.png');
    }

}
