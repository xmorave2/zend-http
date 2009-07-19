<?php

require_once realpath(dirname(__FILE__) . '/../../../') . '/TestHelper.php';

require_once 'Zend/Http/Client.php';

/**
 * This Testsuite includes all Zend_Http_Client tests that do not rely
 * on performing actual requests to an HTTP server. These tests can be
 * executed once, and do not need to be tested with different servers /
 * client setups.
 *
 * @category   Zend
 * @package    Zend_Http_Client
 * @subpackage UnitTests
 * @version    $Id$
 * @copyright
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Http_Client_StaticTest extends PHPUnit_Framework_TestCase
{
    /**
     * Common HTTP client
     *
     * @var Zend_Http_Client
     */
    protected $_client = null;

    /**
     * Set up the test suite before each test
     *
     */
    public function setUp()
    {
        $this->_client = new Zend_Http_Client('http://www.example.com');
    }

    /**
     * Clean up after running a test
     * 
     */
    public function tearDown()
    {
        $this->_client = null;
    }
    
    /**
     * URI Tests
     */

    /**
     * Test we can SET and GET a URI as string
     *
     */
    public function testSetGetUriString()
    {
        $uristr = 'http://www.zend.com:80/';

        $this->_client->setUri($uristr);

        $uri = $this->_client->getUri();
        $this->assertTrue($uri instanceof Zend_Uri_Http, 'Returned value is not a Uri object as expected');
        $this->assertEquals($uri->__toString(), $uristr, 'Returned Uri object does not hold the expected URI');

        $uri = $this->_client->getUri(true);
        $this->assertTrue(is_string($uri), 'Returned value expected to be a string, ' . gettype($uri) . ' returned');
        $this->assertEquals($uri, $uristr, 'Returned string is not the expected URI');
    }

    /**
     * Test we can SET and GET a URI as object
     *
     */
    public function testSetGetUriObject()
    {
        $uriobj = Zend_Uri::factory('http://www.zend.com:80/');

        $this->_client->setUri($uriobj);

        $uri = $this->_client->getUri();
        $this->assertTrue($uri instanceof Zend_Uri_Http, 'Returned value is not a Uri object as expected');
        $this->assertEquals($uri, $uriobj, 'Returned object is not the excepted Uri object');
    }

    /**
     * Test that passing an invalid URI string throws an exception
     *
     * @expectedException Zend_Uri_Exception
     */
    public function testInvalidUriStringException()
    {
        $this->_client->setUri('httpp://__invalid__.com');
    }

    /**
     * Test that passing an invalid URI object throws an exception
     *
     */
    public function testInvalidUriObjectException()
    {
        try {
            $uri = Zend_Uri::factory('mailto:nobody@example.com');
            $this->_client->setUri($uri);
            $this->fail('Excepted invalid URI object exception was not thrown');
        } catch (Zend_Http_Client_Exception $e) {
            // We're good
        } catch (Zend_Uri_Exception $e) {
            // URI is currently unimplemented
            $this->markTestIncomplete('Zend_Uri_Mailto is not implemented yet');
        }
    }

    /**
     * Header Tests
     */

    /**
     * Make sure an exception is thrown if an invalid header name is used
     *
     * @expectedException Zend_Http_Client_Exception
     */
    public function testInvalidHeaderExcept()
    {
        $this->_client->setHeaders('Ina_lid* Hea%der', 'is not good');
    }

    /**
     * Make sure non-strict mode disables header name validation
     *
     */
    public function testInvalidHeaderNonStrictMode()
    {
        // Disable strict validation
        $this->_client->setConfig(array('strict' => false));

        try {
            $this->_client->setHeaders('Ina_lid* Hea%der', 'is not good');
        } catch (Zend_Http_Client_Exception $e) {
            $this->fail('Invalid header names should be allowed in non-strict mode');
        }
    }

    /**
     * Test we can get already set headers
     *
     */
    public function testGetHeader()
    {
        $this->_client->setHeaders(array(
            'Accept-encoding' => 'gzip,deflate',
            'Accept-language' => 'en,de,*',
        ));

        $this->assertEquals($this->_client->getHeader('Accept-encoding'), 'gzip,deflate', 'Returned value of header is not as expected');
        $this->assertEquals($this->_client->getHeader('X-Fake-Header'), null, 'Non-existing header should not return a value');
    }

    public function testUnsetHeader()
    {
        $this->_client->setHeaders('Accept-Encoding', 'gzip,deflate');
        $this->_client->setHeaders('Accept-Encoding', null);
        $this->assertNull($this->_client->getHeader('Accept-encoding'), 'Returned value of header is expected to be null');
    }

    /**
     * Authentication tests
     */

    /**
     * Test setAuth (dynamic method) fails when trying to use an unsupported
     * authentication scheme
     *
     * @expectedException Zend_Http_Client_Exception
     */
    public function testExceptUnsupportedAuthDynamic()
    {
        $this->_client->setAuth('shahar', '1234', 'SuperStrongAlgo');
    }

    /**
     * Test encodeAuthHeader (static method) fails when trying to use an
     * unsupported authentication scheme
     *
     * @expectedException Zend_Http_Client_Exception
     */
    public function testExceptUnsupportedAuthStatic()
    {
        Zend_Http_Client::encodeAuthHeader('shahar', '1234', 'SuperStrongAlgo');
    }

    /**
     * Cookie and Cookie Jar tests
     */

    /**
     * Test we can properly set a new cookie jar
     *
     */
    public function testSetNewCookieJar()
    {
        $this->_client->setCookieJar();
        $this->_client->setCookie('cookie', 'value');
        $this->_client->setCookie('chocolate', 'chips');
        $jar = $this->_client->getCookieJar();

        // Check we got the right cookiejar
        $this->assertTrue($jar instanceof Zend_Http_CookieJar, '$jar is not an instance of Zend_Http_CookieJar as expected');
        $this->assertEquals(count($jar->getAllCookies()), 2, '$jar does not contain 2 cookies as expected');
    }

    /**
     * Test we can properly set an existing cookie jar
     *
     */
    public function testSetReadyCookieJar()
    {
        $jar = new Zend_Http_CookieJar();
        $jar->addCookie('cookie=value', 'http://www.example.com');
        $jar->addCookie('chocolate=chips; path=/foo', 'http://www.example.com');

        $this->_client->setCookieJar($jar);

        // Check we got the right cookiejar
        $this->assertEquals($jar, $this->_client->getCookieJar(), '$jar is not the client\'s cookie jar as expected');
    }

    /**
     * Test we can unset a cookie jar
     *
     */
    public function testUnsetCookieJar()
    {
        // Set the cookie jar just like in testSetNewCookieJar
        $this->_client->setCookieJar();
        $this->_client->setCookie('cookie', 'value');
        $this->_client->setCookie('chocolate', 'chips');
        $jar = $this->_client->getCookieJar();

        // Try unsetting the cookiejar
        $this->_client->setCookieJar(null);

        $this->assertNull($this->_client->getCookieJar(), 'Cookie jar is expected to be null but it is not');
    }

    /**
     * Make sure using an invalid cookie jar object throws an exception
     *
     * @expectedException Zend_Http_Client_Exception
     */
    public function testSetInvalidCookieJar()
    {
        $this->_client->setCookieJar('cookiejar');
    }

    /**
     * Other Tests
     */

    /**
     * Check we get an exception when trying to send a POST request with an
     * invalid content-type header
     * 
     * @expectedException Zend_Http_Client_Exception
     */
    public function testInvalidPostContentType()
    {
        $this->_client->setEncType('x-foo/something-fake');
        $this->_client->setParameterPost('parameter', 'value');

        // This should throw an exception
        $this->_client->request('POST');
    }

    /**
     * Check we get an exception if there's an error in the socket
     *
     * @expectedException Zend_Http_Client_Adapter_Exception
     */
    public function testSocketErrorException() 
    {
        // Try to connect to an invalid host
        $this->_client->setUri('http://255.255.255.255');
        
        // Reduce timeout to 3 seconds to avoid waiting
        $this->_client->setConfig(array('timeout' => 3));

        // This call should cause an exception
        $this->_client->request();
    }

    /**
     * Check that we can set methods which are not documented in the RFC.
     * 
     * @dataProvider validMethodProvider
     */
    public function testSettingExtendedMethod($method)
    {
        try {
            $this->_client->setMethod($method);
        } catch (Exception $e) {
            $this->fail("An unexpected exception was thrown when setting request method to '{$method}'");
        }
    }

    /**
     * Check that an exception is thrown if non-word characters are used in 
     * the request method.
     *
     * @dataProvider invalidMethodProvider
     * @expectedException Zend_Http_Client_Exception
     */
    public function testSettingInvalidMethodThrowsException($method)
    {
        $this->_client->setMethod($method);
    }

    /**
     * Test that configuration options are passed to the adapter after the
     * adapter is instantiated
     *
     * @link http://framework.zend.com/issues/browse/ZF-4557
     */
    public function testConfigPassToAdapterZF4557()
    {
        require_once 'Zend/Http/Client/Adapter/Test.php';
        $adapter = new Zend_Http_Client_Adapter_Test();

        // test that config passes when we set the adapter
        $this->_client->setConfig(array('param' => 'value1'));
        $this->_client->setAdapter($adapter);
        $adapterCfg = $this->getObjectAttribute($adapter, 'config');
        $this->assertEquals('value1', $adapterCfg['param']);

        // test that adapter config value changes when we set client config
        $this->_client->setConfig(array('param' => 'value2'));
        $adapterCfg = $this->getObjectAttribute($adapter, 'config');
        $this->assertEquals('value2', $adapterCfg['param']);
    }

    /**
     * Data providers 
     */
    
    /**
     * Data provider of valid non-standard HTTP methods 
     * 
     * @return array
     */
    static public function validMethodProvider()
    {
        return array(
            array('OPTIONS'),
            array('POST'),
            array('DOSOMETHING'),
            array('PROPFIND'),
            array('Some_Characters'),
            array('X-MS-ENUMATTS')
        );
    }
    
    /**
     * Data provider of invalid HTTP methods
     * 
     * @return array
     */
    static public function invalidMethodProvider()
    {
        return array(
            array('N@5TYM3T#0D'),
            array('TWO WORDS'),
            array('GET http://foo.com/?'),
            array("Injected\nnewline")
        );
    }
}
