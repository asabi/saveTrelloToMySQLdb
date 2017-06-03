# php-trello

Home page: [http://mattzuba.bitbucket.org/php-trello/](http://mattzuba.bitbucket.org/php-trello/)

php-trello is a PHP-based wrapper for the Trello API.  It's functionality is very very similar
to the Trello-made client.js library.  It also supports OAuth authorization.

This is a basic Trello PHP wrapper that is used very similar to the Trello-made client.js
library.  The method calls are the same (ie: Trello->post() or Trello->boards->get()).  See
[https://trello.com/docs/gettingstarted/clientjs.html](https://trello.com/docs/gettingstarted/clientjs.html) for detailed information.

Some differences - you cannot specify callbacks for success or error.  If they're requested
I may add them in, but it's not really my style to pass callbacks like that around PHP when
I can simply return the data instead.

Trello::authorize here does OAuth authentication, so you must pass your Secret Key to the
constructor or set it after instantiation before calling the authorize method.  Some parameters
are the same as client.js (name, scope, expiration) and there is one extra (redirect_uri) for
the OAuth callback.

Go to [https://trello.com/1/appKey/generate](https://trello.com/1/appKey/generate) to get your API and OAuth keys

## Example Usage

### Basic Usage

Read a public board (Trello)

    :::php
        <?php
        $key = 'yourkey';
        $trello = new \Trello\Trello($key);
        var_dump($trello->boards->get('4d5ea62fd76aa1136000000c'));

Pre-existing key/token combo and read your boards

    :::php
        <?php
        $key = 'yourkey';
        $token = 'yourjavascripttoken';
        $trello = new \Trello\Trello($key, null, $token);
        var_dump($trello->members->get('my/boards')));

### OAuth Usage

Authorize and get your boards

    :::php
        <?php
        $key = 'yourkey';
        $secret = 'yoursecret';
        $trello = new \Trello\Trello($key, $secret);
        $trello->authorize(array(
            'expiration' => '1hour',
            'scope' => array(
                'read' => true,
            ),
            'name' => 'My Test App'
        ));
        var_dump($trello->members->get('my/boards'));

Pre-existing OAuth authorization and get your boards

    :::php
        <?php
        $key = 'yourkey';
        $secret = 'yoursecret';
        $oauth_token = 'youroauthtoken';
        $oauth_secret = 'youroauthsecret';
        $trello = new \Trello\Trello($key, $secret, $oauth_token, $oauth_secret);
        var_dump($trello->members->get('my/boards'));

