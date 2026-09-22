<?php

namespace App\Services\Mikrotik;

use App\Services\Mikrotik\Exceptions\MikrotikConnectionException;

/**
 * Minimal client for the RouterOS binary API protocol (TCP 8728 / TLS 8729).
 *
 * Wire format: length-prefixed "words" grouped into "sentences" terminated
 * by a zero-length word. Supports the modern plain-text login (RouterOS
 * >= 6.43) with a fallback to the legacy MD5 challenge-response login used
 * by older versions.
 *
 * Reference: https://help.mikrotik.com/docs/display/ROS/API
 */
class RouterOsApiClient
{
    /** @var resource|null */
    private $socket = null;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly bool $ssl,
        private readonly int $connectTimeoutSeconds,
        private readonly int $readTimeoutSeconds,
    ) {
    }

    /**
     * @throws MikrotikConnectionException
     */
    public function connect(): void
    {
        $transport = $this->ssl ? 'ssl' : 'tcp';

        $context = stream_context_create($this->ssl ? [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ] : []);

        $errno = 0;
        $errstr = '';

        $socket = @stream_socket_client(
            "{$transport}://{$this->host}:{$this->port}",
            $errno,
            $errstr,
            $this->connectTimeoutSeconds,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($socket === false) {
            throw new MikrotikConnectionException("Unable to connect to {$this->host}:{$this->port} ({$errstr})");
        }

        stream_set_timeout($socket, $this->readTimeoutSeconds);

        $this->socket = $socket;
    }

    /**
     * @throws MikrotikConnectionException
     */
    public function login(string $username, string $password): void
    {
        try {
            $this->execute([
                '/login',
                '=name='.$username,
                '=password='.$password,
            ]);

            return;
        } catch (MikrotikConnectionException) {
            // Fall through to the legacy challenge-response login below —
            // this happens on RouterOS versions older than 6.43.
        }

        $legacy = $this->execute(['/login']);
        $challengeHex = $legacy['done']['ret'] ?? null;

        if ($challengeHex === null) {
            throw new MikrotikConnectionException('RouterOS login failed: unexpected response from device.');
        }

        $challenge = pack('H*', $challengeHex);
        $hash = md5(chr(0).$password.$challenge, true);

        $this->execute([
            '/login',
            '=name='.$username,
            '=response=00'.bin2hex($hash),
        ]);
    }

    /**
     * Send a sentence and collect every !re row until !done/!trap/!fatal.
     *
     * @param  array<int, string>  $sentence
     * @return array{data: array<int, array<string, string>>, done: array<string, string>}
     *
     * @throws MikrotikConnectionException
     */
    public function execute(array $sentence): array
    {
        $this->writeSentence($sentence);

        $data = [];
        $done = [];

        while (true) {
            $reply = $this->readSentence();
            $type = $reply[0] ?? null;

            if ($type === '!done') {
                $done = $this->wordsToAssoc($reply);
                break;
            }

            if ($type === '!trap' || $type === '!fatal') {
                $attrs = $this->wordsToAssoc($reply);

                throw new MikrotikConnectionException(
                    'RouterOS API error: '.($attrs['message'] ?? $type)
                );
            }

            if ($type === '!re') {
                $data[] = $this->wordsToAssoc($reply);
            }
        }

        return ['data' => $data, 'done' => $done];
    }

    /**
     * Convenience wrapper for /print-style commands: returns only the
     * matched rows, discarding the (empty) !done sentence.
     *
     * @param  array<int, string>  $sentence
     * @return array<int, array<string, string>>
     *
     * @throws MikrotikConnectionException
     */
    public function query(array $sentence): array
    {
        return $this->execute($sentence)['data'];
    }

    public function close(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }

        $this->socket = null;
    }

    /**
     * @param  array<int, string>  $words
     */
    private function writeSentence(array $words): void
    {
        foreach ($words as $word) {
            $this->writeLength(strlen($word));
            $this->send($word);
        }

        $this->writeLength(0);
    }

    private function writeLength(int $length): void
    {
        if ($length < 0x80) {
            $this->send(chr($length));
        } elseif ($length < 0x4000) {
            $length |= 0x8000;
            $this->send(chr(($length >> 8) & 0xFF).chr($length & 0xFF));
        } elseif ($length < 0x200000) {
            $length |= 0xC00000;
            $this->send(chr(($length >> 16) & 0xFF).chr(($length >> 8) & 0xFF).chr($length & 0xFF));
        } elseif ($length < 0x10000000) {
            $length |= 0xE0000000;
            $this->send(chr(($length >> 24) & 0xFF).chr(($length >> 16) & 0xFF).chr(($length >> 8) & 0xFF).chr($length & 0xFF));
        } else {
            $this->send(chr(0xF0));
            $this->send(chr(($length >> 24) & 0xFF).chr(($length >> 16) & 0xFF).chr(($length >> 8) & 0xFF).chr($length & 0xFF));
        }
    }

    private function send(string $bytes): void
    {
        if (! is_resource($this->socket)) {
            throw new MikrotikConnectionException('Not connected to RouterOS.');
        }

        if (@fwrite($this->socket, $bytes) === false) {
            throw new MikrotikConnectionException('Failed writing to RouterOS socket.');
        }
    }

    /**
     * @return array<int, string>
     */
    private function readSentence(): array
    {
        $words = [];

        while (true) {
            $word = $this->readWord();

            if ($word === '') {
                break;
            }

            $words[] = $word;
        }

        return $words;
    }

    private function readWord(): string
    {
        $length = $this->readLength();

        return $length === 0 ? '' : $this->readBytes($length);
    }

    private function readLength(): int
    {
        $byte = ord($this->readBytes(1));

        if (($byte & 0x80) === 0x00) {
            return $byte;
        }

        if (($byte & 0xC0) === 0x80) {
            $next = ord($this->readBytes(1));

            return (($byte & 0x3F) << 8) | $next;
        }

        if (($byte & 0xE0) === 0xC0) {
            $next = $this->readBytes(2);

            return (($byte & 0x1F) << 16) | (ord($next[0]) << 8) | ord($next[1]);
        }

        if (($byte & 0xF0) === 0xE0) {
            $next = $this->readBytes(3);

            return (($byte & 0x0F) << 24) | (ord($next[0]) << 16) | (ord($next[1]) << 8) | ord($next[2]);
        }

        $next = $this->readBytes(4);

        return (ord($next[0]) << 24) | (ord($next[1]) << 16) | (ord($next[2]) << 8) | ord($next[3]);
    }

    private function readBytes(int $length): string
    {
        if (! is_resource($this->socket)) {
            throw new MikrotikConnectionException('Not connected to RouterOS.');
        }

        $data = '';

        while (strlen($data) < $length) {
            $chunk = fread($this->socket, $length - strlen($data));

            if ($chunk === false || $chunk === '') {
                $meta = stream_get_meta_data($this->socket);

                if (! empty($meta['timed_out'])) {
                    throw new MikrotikConnectionException('RouterOS API read timed out.');
                }

                throw new MikrotikConnectionException('RouterOS API connection closed unexpectedly.');
            }

            $data .= $chunk;
        }

        return $data;
    }

    /**
     * Turns ['!re', '=name=JKT0001', '=profile=2 Hours'] into
     * ['name' => 'JKT0001', 'profile' => '2 Hours'].
     *
     * @param  array<int, string>  $words
     * @return array<string, string>
     */
    private function wordsToAssoc(array $words): array
    {
        $assoc = [];

        foreach ($words as $word) {
            if (! str_starts_with($word, '=')) {
                continue;
            }

            $pair = substr($word, 1);
            $pos = strpos($pair, '=');

            if ($pos === false) {
                continue;
            }

            $assoc[substr($pair, 0, $pos)] = substr($pair, $pos + 1);
        }

        return $assoc;
    }
}
