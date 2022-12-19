<?php

declare(strict_types=1);

namespace Tym\Smart\Http\Server;

/**
 * @author Yvan Tchuente <yvantchuente@gmail.com>
 */
class WebSocketServer
{
    /**
     * Globally Unique Identifier.
     */
    public const GUID = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";

    /**
     * The socket of the connection.
     */
    public readonly \Socket $socket;

    /**
     * The server hostname.
     */
    private string $hostname;

    /**
     * List of endpoints served by the server.
     * 
     * @var string[]
     */
    private array $services = [];

    /**
     * List of origin URIs from which to incoming requests shall be accepted.
     */
    private array $origins = [];

    /**
     * List of connected client sockets.
     *
     * @var \Socket[]
     */
    private array $clients = [];

    /**
     * Initializes the server.
     * 
     * @param string $address The IP address (in dotted-quad notation) to bind to the server.
     * @param int $port The port on which the server shall listen for incoming connections.
     * @param string $hostname The server hostname.
     * @param string[] $services The list of services provided by the server, these are the the endpoints served by the server.
     * @param string[] $origins The list of origin URIs from which incoming requests shall be accepted.
     * 
     * @throws \Exception if an error occurs.
     **/
    public function __construct(
        string $address,
        int $port,
        string $hostname,
        array $services,
        array $origins = null
    ) {
        if (!preg_match('/(\d{1,3}(\b|\.)){4}/', $address)) {
            throw new \InvalidArgumentException("[$address] is not a valid IP address.");
        }
        if ($port >= 1023 && $port <= 65536) {
            throw new \DomainException("Well-known ports are not accepted.");
        }
        if (!$hostname) {
            throw new \LengthException("Empty hostnames are not accepted.");
        }
        if (!$services) {
            throw new \LengthException("The server's services were not provided.");
        }

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($socket, $address, $port);
        socket_listen($socket);

        $this->socket = $socket;
        $this->hostname = $hostname;
        $this->services = $services;

        if ($origins) {
            array_map(function ($origin) {
                if (gettype($origin) !== 'string') {
                    throw new \InvalidArgumentException("All of the origin URIs must be strings.");
                }
            }, $origins);

            $this->origins = $origins;
        }
    }

    /**
     * Adds an origin from which incoming requests shall be accepted.
     */
    public function addOrigin(string $name, string $uri)
    {
        if (!$name) {
            throw new \LengthException("Empty names are not accepted.");
        }
        if (!$uri) {
            throw new \LengthException("Empty URIs are not accepted.");
        }

        $this->origins[$name] = $uri;
    }

    /**
     * Establishes a connection with a given socket as per the provided opening handshake.
     * 
     * Inspects the opening handshake and to decide whether to complete the handshake in order 
     * to establish or not the connection.
     * 
     * @param string $handshake The client opening handshake.
     * @param \Socket $client_socket The client socket.
     * 
     * @return bool
     */
    public function connect(string $client_handshake, \Socket $client_socket)
    {
        if (!$this->acceptHandshake($client_handshake)) {
            $response = "HTTP/1.1 400 Bad Request\r\n" . "Connection: close\r\n";
            socket_write($client_socket, $response, strlen($response));
            return false;
        }

        $endpoint = $this->getRequestLine($client_handshake)['endpoint'];
        if (!$this->isService($endpoint)) {
            $response = "HTTP/1.1 404 Not Found\r\n" . "Connection: close\r\n";
            socket_write($client_socket, $response, strlen($response));
            return false;
        }

        $headers = $this->getHeaders($client_handshake);
        $origin = $headers['Origin'];
        switch (true) {
            case ($this->origins && !$origin):
            case ($this->origins && !in_array($origin, $this->origins, true)):
                $response = "HTTP/1.1 403 Forbidden";
                socket_write($client_socket, $response, strlen($response));
                return false;
                break;
        }

        $secKey = $headers['Sec-WebSocket-Key'];
        $secAccept_key = base64_encode(pack(
            'H*',
            sha1($secKey . self::GUID)
        ));
        $server_upgrade_headers  = "HTTP/1.1 101 Switching Protocols\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Version: 13\r\n" .
            "Sec-WebSocket-Accept:$secAccept_key\r\n\r\n";
        socket_write($client_socket, $server_upgrade_headers, strlen($server_upgrade_headers));

        // Register the client socket
        $this->clients[] = $client_socket;

        return true;
    }

    /**
     * Performs a websocket closing handshake with a given socket.
     *
     * @param int $code The status code.
     * @param string $reason The close reason.
     */
    public function disconnect(\Socket $client_socket, int $code, string $reason = "")
    {
        $message = pack('I', $code) . $reason;
        $this->send($client_socket, 'close', $message);
        $this->close($client_socket);
    }

    /**
     * Gets the list of connected client sockets.
     */
    public function getClients()
    {
        return $this->clients;
    }

    /**
     * Cleanly closes the websocket connection with a given socket.
     */
    public function close(\Socket $client_socket)
    {
        socket_shutdown($client_socket);
        socket_close($client_socket);
    }

    /**
     * Sends a data frame to a given socket.
     * 
     * Masks the data frame according to its type and sends it over the connection wire
     * to a client socket.
     * 
     * @param \Socket $client The client socket.
     * @param string $type The data frame type.
     * @param string|\Stringable $data The data frame.
     * 
     * @return bool
     * 
     * @throws \DomainException If type is not a valid frame type.
     */
    public function send(\Socket $client, string $type, string $data)
    {
        $encoded_data = $this->encode($type, $data);
        return (bool) socket_write($client, $encoded_data, strlen($encoded_data));
    }

    /**
     * Broadcasts a data frame to a given list of connected clients.
     * 
     * @param \Socket[] $clients A list of connected client sockets.
     * @param string $type The data type.
     * @param string $data The data.
     */
    public function broadcast(array $clients, string $type, string $data)
    {
        foreach ($clients as $client) {
            $this->send($client, $type, $data);
        }
    }

    /**
     * Masks a data frame.
     * 
     * @param string $type The data type.
     * @param string|Stringable $data The data.
     * 
     * @throws \DomainException If type is not a valid frame type.
     */
    public function encode(string $type, string|\Stringable $data)
    {
        if (!in_array($type, ['text', 'binary', 'close', 'ping', 'pong'])) {
            throw new \DomainException("Invalid frame type");
        }

        switch ($type) {
            case 'text':
                $byte1 = 0x81; // 1000 0001
                break;
            case 'binary':
                $byte1 = 0x82; // 1000 0010
                break;
            case 'close':
                $byte1 = 0x88; // 1000 1000 
                break;
            case 'ping':
                $byte1 = 0x89; // 1000 1001 
                break;
            case 'pong':
                $byte1 = 0x8A; // 1000 1010
                break;
        }

        $length = strlen($data);
        if ($length <= 125) {
            $header = pack('C*', $byte1, $length);
        } elseif ($length > 125 && $length < 65536) {
            $header = pack('CCn', $byte1, 126, $length);
        } elseif ($length >= 65536) {
            $header = pack('CCN', $byte1, 127, $length);
        }

        return $header . $data;
    }

    /**
     * Unmasks a data frame sent over the socket connection.
     * 
     * @param string $frame The masked data frame.
     */
    public function decode(string $frame)
    {
        $length = ord($frame[1]) & 127;
        if ($length == 126) {
            $masks = substr($frame, 4, 4);
            $data = substr($frame, 8);
        } elseif ($length == 127) {
            $masks = substr($frame, 10, 4);
            $data = substr($frame, 14);
        } else {
            $masks = substr($frame, 2, 4);
            $data = substr($frame, 6);
        }

        $frame = "";
        for ($i = 0; $i < strlen($data); ++$i) {
            $frame .= $data[$i] ^ $masks[$i % 4];
        }

        return $frame;
    }

    /**
     * Retrieves the request line from the given client opening handshake.
     *
     * @return array An array of request method, request endpoint and version.
     * 
     * @throws \RuntimeException If an invalid HTTP method or version is found in the request line.
     */
    public function getRequestLine(string $client_handshake)
    {
        $requestLine = preg_split("/\r\n/", $client_handshake)[0];
        $parts = explode(" ", $requestLine);

        if (!preg_match('/^GET$/', $parts[0])) {
            throw new \RuntimeException("Invalid request: invalid HTTP request method. It must be a GET method");
        } elseif (!preg_match('/^HTTP\/\d\.\d$/', $parts[2])) {
            throw new \RuntimeException("Invalid request: invalid HTTP version");
        }

        return [
            'method' => $parts[0], 'endpoint' => $parts[1], 'version' => $parts[2]
        ];
    }

    /**
     * Retrieves query paramaters if any present in the given client opening handshake.
     * 
     * @return array|null
     */
    public function getQueryParams(string $client_handshake)
    {
        $requestLine = $this->getRequestLine($client_handshake);

        if (preg_match('/\?(\S+)=(.*)/', $requestLine['endpoint'], $matches)) {
            $query[$matches[1]] = $matches[2];
        }
        if (isset($query)) {
            return $query;
        } else {
            return null;
        }
    }

    /**
     * Tells whether the server accepts a given client opening handshake. 
     *
     * @return bool
     */
    private function acceptHandshake(string $client_handshake)
    {
        $headers = $this->getHeaders($client_handshake);

        $host = $headers['Host'];
        $upgrade = $headers['Upgrade'];
        $connection = $headers['Connection'];
        $secKey = $headers['Sec-WebSocket-Key'];
        $secVersion = (int) $headers['Sec-WebSocket-Version'];

        if (empty($host) || !preg_match("/" . $this->hostname . "/", $host)) {
            return false;
        }
        if (empty($upgrade) || !preg_match('/^websocket$/i', $upgrade)) {
            return false;
        }
        if (empty($connection) || !preg_match('/Upgrade/i', $connection)) {
            return false;
        }
        if (empty($secKey) || strlen(base64_decode($secKey)) !== 16) {
            return false;
        }
        if (empty($secVersion) || $secVersion !== 13) {
            return false;
        }

        return true;
    }

    /**
     * Retrieves the header fields of a given client opening handshake.
     *
     * @return string[] A list of header fields.
     */
    private function getHeaders(string $client_handshake)
    {
        $lines = preg_split("/\r\n/", $client_handshake);

        // Client handshake headers
        foreach ($lines as $line) {
            $line = rtrim($line);
            if (preg_match('/(\S+): (.*)/', $line, $matches)) {
                $headers[$matches[1]] = $matches[2];
            }
        }

        return $headers;
    }

    /**
     * Determines whether a given endpoint is served by the server.
     */
    private function isService(string $endpoint)
    {
        $services = array_filter($this->services, function ($service) use ($endpoint) {
            $service = preg_quote($service, '/');
            if (preg_match("/^$service(\?(\w+(=.+)?&?)+)?$/", $endpoint)) {
                return true;
            }
        });

        return boolval(count($services));
    }
}
