<?php

use \Trello\Trello;

class TrelloTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \Trello\Trello
     */
    protected static $trello;

    public static function setUpBeforeClass() {
        global $keys;

        if (empty($keys->token)) {
            $key = empty($keys->key) ? 'YOURKEY' : $keys->key;
            fwrite(STDOUT, "Please visit https://trello.com/1/authorize?response_type=token&key=$key&scope=read,write,account&expiration=never&name=php-trello+Testing to obtain a token.\n");
        }
    }

    public function setUp() {
        global $keys;

        if (empty($keys->key) || empty($keys->token)) {
            $this->markTestSkipped('Missing key or token in keys.json file');
        }

        self::$trello = new Trello($keys->key, null, $keys->token);
    }

    public function testBuildRequestUrl() {
        $this->assertEquals('https://api.trello.com/1/boards?param=value',
                             self::$trello->buildRequestUrl('GET', 'boards', array(
                                 'param' => 'value'
                             )));

        $this->assertEquals('https://api.trello.com/1/boards',
                             self::$trello->buildRequestUrl('PUT', 'boards', array(
                                 'param' => 'value'
                             )));
    }

    /**
     * https://bitbucket.org/mattzuba/php-trello/issue/2/passing-params-while-using-oauth-causes
     */
    public function testIssue2() {
        $actions = self::$trello->lists->get('4d5ea62fd76aa1136000001d/actions', array('since' => '2013-07-30'));
        $this->assertInternalType('array', $actions, self::$trello->error());
    }

    /**
     * https://bitbucket.org/mattzuba/php-trello/issue/4/put-requests-failing
     */
    public function testIssue4() {
        $card = '7uDI46kM';
        $result = self::$trello->put("cards/$card/labels", array('value' => 'green,red'));
        $this->assertInternalType('object', $result, self::$trello->error());
    }

    /**
     * https://bitbucket.org/mattzuba/php-trello/issue/5/expand-collection-class
     */
    public function testIssue5() {
        $card = '7uDI46kM';
        $result = self::$trello->cards->put("$card/labels", array('value' => 'blue'));
        $this->assertInternalType('object', $result, self::$trello->error());
    }

    public function testOauth() {
        global $keys;
        // We can only do oauth if secret is present
        if (empty($keys->secret)) {
            $this->markTestSkipped('testOauth: Missing oAuth secret');
        }

        // oAuth test messes with trello object, create a new one for testing here
        $trello = new Trello($keys->key, $keys->secret);

        // Get an authorize URL (or true if we're already auth'd)
        $authorizeUrl = $trello->authorize(array(
            'scope' => array(
                'read' => true,
                'write' => true,
                'account' => true,
            ),
            'redirect_uri' => 'http://127.0.0.1:23456',
            'name' => 'php-trello Testing',
            'expiration' => '1hour',
        ), true);

        // Need to get authorized
        if ($authorizeUrl !== true) {
            $server = stream_socket_server('tcp://127.0.0.1:23456', $errno, $errmsg);
            if (!$server) {
                $this->markTestIncomplete("testOauth: Could not create a socket at 127.0.0.1:23456: $errmsg");
            }

            // Fire off the browser
            `xdg-open "$authorizeUrl" >/dev/null 2>&1 &`;

            //echo "Waiting for authorization from Trello...\n";
            $client = @stream_socket_accept($server, -1);
            if (!$client) {
                $this->markTestIncomplete('testOauth: Failed to receive Trello response.');
            }
            $query = fgets($client, 1024);

            // Received a response, let's send back a message
            $msg = "Please close this browser window and return to the test to continue.";
            fputs($client, "HTTP/1.1 200 OK\r\n");
            fputs($client, "Connection: close\r\n");
            fputs($client, "Content-Type: text/html; charset=UTF-8\r\n");
            fputs($client, "Content-Length: " . strlen($msg) . "\r\n\r\n");
            fputs($client, $msg);
            fclose($client);

            // Wait to continue the test until the browser returns.
            //readline("Press [Enter] to continue...");

            // Lets parse the query and pull out the oauth stuff
            if (!preg_match('~GET (.*?) HTTP~', $query, $match)) {
                $this->markTestIncomplete('testOauth: Could not read response from Trello.');
            }

            // Parse the query portion of the URL into the GET variable
            parse_str(parse_url($match[1], PHP_URL_QUERY), $_GET);

            $this->assertTrue(self::$trello->authorize());
        }
    }
}
