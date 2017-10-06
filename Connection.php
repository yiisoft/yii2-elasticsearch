<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\elasticsearch;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\helpers\Json;

/**
 * elasticsearch Connection is used to connect to an elasticsearch cluster version 0.20 or higher
 *
 * @property string $driverName Name of the DB driver. This property is read-only.
 * @property boolean $isActive Whether the DB connection is established. This property is read-only.
 * @property QueryBuilder $queryBuilder This property is read-only.
 *
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 */
class Connection extends Component
{
    /**
     * @event Event an event that is triggered after a DB connection is established
     */
    const EVENT_AFTER_OPEN = 'afterOpen';

    /**
     * @var boolean whether to autodetect available cluster nodes on [[open()]]
     */
    public $autodetectCluster = true;
    /**
     * @var array The elasticsearch cluster nodes to connect to.
     *
     * This is populated with the result of a cluster nodes request when [[autodetectCluster]] is true.
     *
     * Additional special options:
     *
     *  - `auth`: overrides [[auth]] property. For example:
     *
     * ```php
     * [
     *  'http_address' => 'inet[/127.0.0.1:9200]',
     *  'auth' => ['username' => 'yiiuser', 'password' => 'yiipw'], // Overrides the `auth` property of the class with specific login and password
     *  //'auth' => ['username' => 'yiiuser', 'password' => 'yiipw'], // Disabled auth regardless of `auth` property of the class
     * ]
     * ```
     *
     *  - `protocol`: explicitly sets the protocol for the current node (useful when manually defining a HTTPS cluster)
     *
     * @see http://www.elastic.co/guide/en/elasticsearch/reference/current/cluster-nodes-info.html#cluster-nodes-info
     */
    public $nodes = [
        ['http_address' => 'inet[/127.0.0.1:9200]'],
    ];
    /**
     * @var string the active node. Key of one of the [[nodes]]. Will be randomly selected on [[open()]].
     */
    public $activeNode;
    /**
     * @var array Authentication data used to connect to the ElasticSearch node.
     *
     * Array elements:
     *
     *  - `username`: the username for authentication.
     *  - `password`: the password for authentication.
     *
     * Array either MUST contain both username and password on not contain any authentication credentials.
     * @see http://www.elasticsearch.org/guide/en/elasticsearch/client/php-api/current/_configuration.html#_example_configuring_http_basic_auth
     */
    public $auth = [];
    /**
     * Elasticsearch has no knowledge of protocol used to access its nodes. Specifically, cluster autodetection request
     * returns node hosts and ports, but not the protocols to access them. Therefore we need to specify a default protocol here,
     * which can be overridden for specific nodes in the [[nodes]] property.
     * If [[autodetectCluster]] is true, all nodes received from cluster will be set to use the protocol defined by [[defaultProtocol]]
     * @var string Default protocol to connect to nodes
     * @since 2.0.5
     */
    public $defaultProtocol = 'http';
    /**
     * @var float timeout to use for connecting to an elasticsearch node.
     * This value will be used to configure the curl `CURLOPT_CONNECTTIMEOUT` option.
     * If not set, no explicit timeout will be set for curl.
     */
    public $connectionTimeout = null;
    /**
     * @var float timeout to use when reading the response from an elasticsearch node.
     * This value will be used to configure the curl `CURLOPT_TIMEOUT` option.
     * If not set, no explicit timeout will be set for curl.
     */
    public $dataTimeout = null;

    /**
     * @var resource the curl instance returned by [curl_init()](http://php.net/manual/en/function.curl-init.php).
     */
    private $_curl;


    public function init()
    {
        foreach ($this->nodes as &$node) {
            if (!isset($node['http_address'])) {
                throw new InvalidConfigException('Elasticsearch node needs at least a http_address configured.');
            }
            if (!isset($node['protocol'])) {
                $node['protocol'] = $this->defaultProtocol;
            }
            if (!in_array($node['protocol'], ['http', 'https'])) {
                throw new InvalidConfigException('Valid node protocol settings are "http" and "https".');
            }
        }
    }

    /**
     * Closes the connection when this component is being serialized.
     * @return array
     */
    public function __sleep()
    {
        $this->close();

        return array_keys(get_object_vars($this));
    }

    /**
     * Returns a value indicating whether the DB connection is established.
     * @return boolean whether the DB connection is established
     */
    public function getIsActive()
    {
        return $this->activeNode !== null;
    }

    /**
     * Establishes a DB connection.
     * It does nothing if a DB connection has already been established.
     * @throws Exception if connection fails
     */
    public function open()
    {
        if ($this->activeNode !== null) {
            return;
        }
        if (empty($this->nodes)) {
            throw new InvalidConfigException('elasticsearch needs at least one node to operate.');
        }
        $this->_curl = curl_init();
        if ($this->autodetectCluster) {
            $this->populateNodes();
        }
        $this->selectActiveNode();
        Yii::trace('Opening connection to elasticsearch. Nodes in cluster: ' . count($this->nodes)
            . ', active node: ' . $this->nodes[$this->activeNode]['http_address'], __CLASS__);
        $this->initConnection();
    }

    /**
     * Populates [[nodes]] with the result of a cluster nodes request.
     * @throws Exception if no active node(s) found
     * @since 2.0.4
     */
    protected function populateNodes()
    {
        $node = reset($this->nodes);
        $host = $node['http_address'];
        $protocol = isset($node['protocol']) ? $node['protocol'] : $this->defaultProtocol;
        if (strncmp($host, 'inet[/', 6) === 0) {
            $host = substr($host, 6, -1);
        }
        $response = $this->httpRequest('GET', "$protocol://$host/_nodes/_all/http");
        if (!empty($response['nodes'])) {
            $nodes = $response['nodes'];
        } else {
            $nodes = [];
        }

        foreach ($nodes as $key => &$node) {
            // Make sure that nodes have an 'http_address' property, which is not the case if you're using AWS
            // Elasticsearch service (at least as of Oct., 2015). - TO BE VERIFIED
            // Temporary workaround - simply ignore all invalid nodes
            if (!isset($node['http']['publish_address'])) {
                unset($nodes[$key]);
            }
            $node['http_address'] = $node['http']['publish_address'];

            //Protocol is not a standard ES node property, so we add it manually
            $node['protocol'] = $this->defaultProtocol;
        }

        if (!empty($nodes)) {
            $this->nodes = array_values($nodes);
        } else {
            curl_close($this->_curl);
            throw new Exception('Cluster autodetection did not find any active nodes.');
        }
    }

    /**
     * select active node randomly
     */
    protected function selectActiveNode()
    {
        $keys = array_keys($this->nodes);
        $this->activeNode = $keys[rand(0, count($keys) - 1)];
    }

    /**
     * Closes the currently active DB connection.
     * It does nothing if the connection is already closed.
     */
    public function close()
    {
        if ($this->activeNode === null) {
            return;
        }
        Yii::trace('Closing connection to elasticsearch. Active node was: '
            . $this->nodes[$this->activeNode]['http']['publish_address'], __CLASS__);
        $this->activeNode = null;
        if ($this->_curl) {
            curl_close($this->_curl);
            $this->_curl = null;
        }
    }

    /**
     * Initializes the DB connection.
     * This method is invoked right after the DB connection is established.
     * The default implementation triggers an [[EVENT_AFTER_OPEN]] event.
     */
    protected function initConnection()
    {
        $this->trigger(self::EVENT_AFTER_OPEN);
    }

    /**
     * Returns the name of the DB driver for the current [[dsn]].
     * @return string name of the DB driver
     */
    public function getDriverName()
    {
        return 'elasticsearch';
    }

    /**
     * Creates a command for execution.
     * @param array $config the configuration for the Command class
     * @return Command the DB command
     */
    public function createCommand($config = [])
    {
        $this->open();
        $config['db'] = $this;
        $command = new Command($config);

        return $command;
    }

    /**
     * Creates a bulk command for execution.
     * @param array $config the configuration for the [[BulkCommand]] class
     * @return BulkCommand the DB command
     * @since 2.0.5
     */
    public function createBulkCommand($config = [])
    {
        $this->open();
        $config['db'] = $this;
        $command = new BulkCommand($config);

        return $command;
    }

    /**
     * Creates new query builder instance
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return new QueryBuilder($this);
    }

    /**
     * Performs GET HTTP request
     *
     * @param string|array $url URL
     * @param array $options URL options
     * @param string $body request body
     * @param boolean $raw if response body contains JSON and should be decoded
     * @return mixed response
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function get($url, $options = [], $body = null, $raw = false)
    {
        $this->open();
        return $this->httpRequest('GET', $this->createUrl($url, $options), $body, $raw);
    }

    /**
     * Performs HEAD HTTP request
     *
     * @param string|array $url URL
     * @param array $options URL options
     * @param string $body request body
     * @return mixed response
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function head($url, $options = [], $body = null)
    {
        $this->open();
        return $this->httpRequest('HEAD', $this->createUrl($url, $options), $body);
    }

    /**
     * Performs POST HTTP request
     *
     * @param string|array $url URL
     * @param array $options URL options
     * @param string $body request body
     * @param boolean $raw if response body contains JSON and should be decoded
     * @return mixed response
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function post($url, $options = [], $body = null, $raw = false)
    {
        $this->open();
        return $this->httpRequest('POST', $this->createUrl($url, $options), $body, $raw);
    }

    /**
     * Performs PUT HTTP request
     *
     * @param string|array $url URL
     * @param array $options URL options
     * @param string $body request body
     * @param boolean $raw if response body contains JSON and should be decoded
     * @return mixed response
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function put($url, $options = [], $body = null, $raw = false)
    {
        $this->open();
        return $this->httpRequest('PUT', $this->createUrl($url, $options), $body, $raw);
    }

    /**
     * Performs DELETE HTTP request
     *
     * @param string|array $url URL
     * @param array $options URL options
     * @param string $body request body
     * @param boolean $raw if response body contains JSON and should be decoded
     * @return mixed response
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function delete($url, $options = [], $body = null, $raw = false)
    {
        $this->open();
        return $this->httpRequest('DELETE', $this->createUrl($url, $options), $body, $raw);
    }

    /**
     * Creates URL
     *
     * @param string|array $path path
     * @param array $options URL options
     * @return array
     */
    private function createUrl($path, $options = [])
    {
        if (!is_string($path)) {
            $url = implode('/', array_map(function ($a) {
                return urlencode(is_array($a) ? implode(',', $a) : $a);
            }, $path));
            if (!empty($options)) {
                $url .= '?' . http_build_query($options);
            }
        } else {
            $url = $path;
            if (!empty($options)) {
                $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($options);
            }
        }

        $node = $this->nodes[$this->activeNode];
        $protocol = isset($node['protocol']) ? $node['protocol'] : $this->defaultProtocol;
        $host = $node['http_address'];

        return [$protocol, $host, $url];
    }

    /**
     * Performs HTTP request
     *
     * @param string $method method name
     * @param string $url URL
     * @param string $requestBody request body
     * @param boolean $raw if response body contains JSON and should be decoded
     * @return mixed if request failed
     * @throws Exception if request failed
     * @throws InvalidConfigException
     */
    protected function httpRequest($method, $url, $requestBody = null, $raw = false)
    {
        $method = strtoupper($method);

        // response body and headers
        $headers = [];
        $headersFinished = false;
        $body = '';

        $options = [
            CURLOPT_USERAGENT      => 'Yii Framework ' . Yii::getVersion() . ' ' . __CLASS__,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_HEADER         => false,
            // http://www.php.net/manual/en/function.curl-setopt.php#82418
            CURLOPT_HTTPHEADER     => [
                'Expect:',
                'Content-Type: application/json',
            ],

            CURLOPT_WRITEFUNCTION  => function ($curl, $data) use (&$body) {
                $body .= $data;
                return mb_strlen($data, '8bit');
            },
            CURLOPT_HEADERFUNCTION => function ($curl, $data) use (&$headers, &$headersFinished) {
                if ($data === '') {
                    $headersFinished = true;
                } elseif ($headersFinished) {
                    $headersFinished = false;
                }
                if (!$headersFinished && ($pos = strpos($data, ':')) !== false) {
                    $headers[strtolower(substr($data, 0, $pos))] = trim(substr($data, $pos + 1));
                }
                return mb_strlen($data, '8bit');
            },
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_FORBID_REUSE   => false,
        ];

        if (!empty($this->auth) || isset($this->nodes[$this->activeNode]['auth']) && $this->nodes[$this->activeNode]['auth'] !== false) {
            $auth = isset($this->nodes[$this->activeNode]['auth']) ? $this->nodes[$this->activeNode]['auth'] : $this->auth;
            if (empty($auth['username'])) {
                throw new InvalidConfigException('Username is required to use authentication');
            }
            if (empty($auth['password'])) {
                throw new InvalidConfigException('Password is required to use authentication');
            }

            $options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
            $options[CURLOPT_USERPWD] = $auth['username'] . ':' . $auth['password'];
        }

        if ($this->connectionTimeout !== null) {
            $options[CURLOPT_CONNECTTIMEOUT] = $this->connectionTimeout;
        }
        if ($this->dataTimeout !== null) {
            $options[CURLOPT_TIMEOUT] = $this->dataTimeout;
        }
        if ($requestBody !== null) {
            $options[CURLOPT_POSTFIELDS] = $requestBody;
        }
        if ($method == 'HEAD') {
            $options[CURLOPT_NOBODY] = true;
            unset($options[CURLOPT_WRITEFUNCTION]);
        } else {
            $options[CURLOPT_NOBODY] = false;
        }

        if (is_array($url)) {
            list($protocol, $host, $q) = $url;
            if (strncmp($host, 'inet[', 5) == 0) {
                $host = substr($host, 5, -1);
                if (($pos = strpos($host, '/')) !== false) {
                    $host = substr($host, $pos + 1);
                }
            }
            $profile = "$method $q#$requestBody";
            $url = "$protocol://$host/$q";
        } else {
            $profile = false;
        }

        Yii::trace("Sending request to elasticsearch node: $method $url\n$requestBody", __METHOD__);
        if ($profile !== false) {
            Yii::beginProfile($profile, __METHOD__);
        }

        $this->resetCurlHandle();
        curl_setopt($this->_curl, CURLOPT_URL, $url);
        curl_setopt_array($this->_curl, $options);
        if (curl_exec($this->_curl) === false) {
            throw new Exception('Elasticsearch request failed: ' . curl_errno($this->_curl) . ' - ' . curl_error($this->_curl), [
                'requestMethod' => $method,
                'requestUrl' => $url,
                'requestBody' => $requestBody,
                'responseHeaders' => $headers,
                'responseBody' => $this->decodeErrorBody($body),
            ]);
        }

        $responseCode = curl_getinfo($this->_curl, CURLINFO_HTTP_CODE);

        if ($profile !== false) {
            Yii::endProfile($profile, __METHOD__);
        }

        if ($responseCode >= 200 && $responseCode < 300) {
            if ($method === 'HEAD') {
                return true;
            } else {
                if (isset($headers['content-length']) && ($len = mb_strlen($body, '8bit')) < $headers['content-length']) {
                    throw new Exception("Incomplete data received from elasticsearch: $len < {$headers['content-length']}", [
                        'requestMethod' => $method,
                        'requestUrl' => $url,
                        'requestBody' => $requestBody,
                        'responseCode' => $responseCode,
                        'responseHeaders' => $headers,
                        'responseBody' => $body,
                    ]);
                }
                if (isset($headers['content-type']) && (!strncmp($headers['content-type'], 'application/json', 16) || !strncmp($headers['content-type'], 'text/plain', 10))) {
                    return $raw ? $body : Json::decode($body);
                }
                throw new Exception('Unsupported data received from elasticsearch: ' . $headers['content-type'], [
                    'requestMethod' => $method,
                    'requestUrl' => $url,
                    'requestBody' => $requestBody,
                    'responseCode' => $responseCode,
                    'responseHeaders' => $headers,
                    'responseBody' => $this->decodeErrorBody($body),
                ]);
            }
        } elseif ($responseCode == 404) {
            return false;
        } else {
            throw new Exception("Elasticsearch request failed with code $responseCode. Response body:\n{$body}", [
                'requestMethod' => $method,
                'requestUrl' => $url,
                'requestBody' => $requestBody,
                'responseCode' => $responseCode,
                'responseHeaders' => $headers,
                'responseBody' => $this->decodeErrorBody($body),
            ]);
        }
    }

    private function resetCurlHandle()
    {
        // these functions do not get reset by curl automatically
        static $unsetValues = [
            CURLOPT_HEADERFUNCTION => null,
            CURLOPT_WRITEFUNCTION => null,
            CURLOPT_READFUNCTION => null,
            CURLOPT_PROGRESSFUNCTION => null,
            CURLOPT_POSTFIELDS => null,
        ];
        curl_setopt_array($this->_curl, $unsetValues);
        if (function_exists('curl_reset')) { // since PHP 5.5.0
            curl_reset($this->_curl);
        }
    }

    /**
     * Try to decode error information if it is valid json, return it if not.
     * @param $body
     * @return mixed
     */
    protected function decodeErrorBody($body)
    {
        try {
            $decoded = Json::decode($body);
            if (isset($decoded['error']) && !is_array($decoded['error'])) {
                $decoded['error'] = preg_replace('/\b\w+?Exception\[/', "<span style=\"color: red;\">\\0</span>\n               ", $decoded['error']);
            }
            return $decoded;
        } catch(InvalidParamException $e) {
            return $body;
        }
    }

    public function getNodeInfo()
    {
        return $this->get([]);
    }

    public function getClusterState()
    {
        return $this->get(['_cluster', 'state']);
    }
}
