<?php

declare(strict_types=1);

namespace Tym\Smart\Http\Message;

use Psr\Http\Message\StreamInterface;

/**
 * @author Yvan Tchuente <yvantchuente@gmail.com>
 */
class Stream implements StreamInterface
{
    /**
     * Valid mode parameters to the fopen function.
     */
    private const ALLOWED_MODES = '/^r\+?|w\+?|a\+?|x\+?|c\+$/';

    /**
     * Access types of read-only streams.
     */
    private const READABLE_ONLY = '/r(b|t)?/';

    /**
     * Access types of write-only streams.
     */
    private const WRITABLE_ONLY = '/(w|a|x|c)(b|t)?/';

    /**
     * Access types of both readable and writable stream.
     */
    private const BOTH_READABLE_WRITABLE = '/(r+|w+|a+|x+|c+)(b|t)?/';

    /**
     * The data stream.
     * 
     * @var resource
     */
    private $stream;

    /**
     * The stream's size in bytes.
     */
    private ?int $size;

    /**
     * Indicates if the stream is readable.
     */
    private bool $readable;

    /**
     * Indicates if the stream is writable.
     */
    private bool $writable;

    /**
     * Indicates if the stream is seekable.
     */
    private bool $seekable;

    /**
     * The stream's metadata.
     */
    private ?array $metadata;

    /**
     * Initializes the stream
     * 
     * It accepts an associative array of options, these options applies only if `stream` is a string.
     * 
     * - isText: (bool) If set to true, `stream` is considered to be the content of the stream to wrap.
     *                  Otherwise it is considered as the URI/filename of the stream.
     * 
     * - mode: (string|null) If `isText` is set to false, `mode` is provided as argument to the fopen function. 
     *         It must be a valid mode. If the option is unset, it defaults to the 'r+' mode.
     * 
     * @param resource|string $stream The data stream.
     * @param array $options The list of options.
     * 
     * @throws \InvalidArgumentException If the stream is invalid.
     * @throws \RuntimeException If an error occurs.
     **/
    public function __construct($stream, array $options = [])
    {
        switch (true) {
            case (is_resource($stream)):
                $resource = $stream;
                break;
            case (is_string($stream)):
                $this->validateOptions($options);
                extract($options);
                if ($isText) {
                    $resource = fopen("php://temp", "r+");
                    fwrite($resource, $stream);
                    rewind($resource);
                } else {
                    $resource = fopen($stream, $mode);
                    if (!is_resource($resource)) {
                        throw new \RuntimeException(sprintf('%s could not be opened', $stream));
                    }
                }
                break;
            default:
                throw new \InvalidArgumentException("The stream must be a resource or a string");
                break;
        }
        $this->stream = $resource;
        $this->metadata = stream_get_meta_data($this->stream);
        $this->seekable = $this->metadata['seekable'];
        $this->readable = self::readable($this->stream);
        $this->writable = self::writable($this->stream);
        $this->size = $this->size($this->stream);
    }

    public function __destruct()
    {
        $this->close();
    }

    public function __toString()
    {
        try {
            $this->rewind();
            $contents = $this->getContents();
            $this->rewind();
            return $contents;
        } catch (\Throwable $e) {
            trigger_error(sprintf("%s::__toString exception: %s", self::class, $e));
            return '';
        }
    }

    public function close()
    {
        if (isset($this->stream)) {
            fclose($this->stream);
        }
        $this->detach();
    }

    public function detach()
    {
        if (!isset($this->stream)) {
            return null;
        }
        $stream = $this->stream;
        unset($this->stream);
        $this->metadata = $this->size = null;
        $this->readable = $this->writable = $this->seekable = false;
        return $stream;
    }

    public function getSize()
    {
        if (!isset($this->stream)) {
            return null;
        }
        $this->size = $this->size($this->stream);
        return $this->size;
    }

    public function tell()
    {
        $this->isDetached();
        $position = ftell($this->stream);
        if ($position === false) {
            throw new \RuntimeException("Could not tell the position of the pointer");
        }
        return $position;
    }

    public function eof()
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException("Stream is detached");
        }
        return feof($this->stream);
    }

    public function isSeekable()
    {
        return $this->seekable;
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        $this->isDetached();
        if (!$this->metadata['seekable']) {
            throw new \RuntimeException("The stream is not seekable.");
        }
        $result = fseek($this->stream, $offset, $whence);
        if ($result < 0) {
            throw new \RuntimeException("Could not seek into the stream.");
        }
        return $result;
    }

    public function rewind()
    {
        $this->seek(0);
    }

    public function isWritable()
    {
        return $this->writable;
    }

    public function write($string)
    {
        $this->isDetached();
        if (!is_string($string)) {
            throw new \RuntimeException('Invalid string.');
        }
        $bytes = fwrite($this->stream, $string);
        if ($bytes === false) {
            throw new \RuntimeException("String could not be written to the stream");
        }
        $this->size = $this->size($this->stream);
        return $bytes;
    }

    public function isReadable()
    {
        return $this->readable;
    }

    public function read($length)
    {
        $this->isDetached();
        if (!(is_numeric($length) and $length)) {
            throw new \RuntimeException('Invalid length.');
        }
        $content = fread($this->stream, $length);
        if (!$content) {
            throw new \RuntimeException("Stream could not be read.");
        }
        $this->metadata = stream_get_meta_data($this->stream);
        return $content;
    }

    public function getContents()
    {
        $this->isDetached();
        $contents = stream_get_contents($this->stream);
        if ($contents === false) {
            throw new \RuntimeException("Stream could not be read.");
        }
        return $contents;
    }

    public function getMetadata($key = null)
    {
        if (!isset($this->stream)) {
            return ($key) ? null : [];
        } elseif (!$key) {
            return $this->metadata;
        } else {
            return $this->metadata[$key] ?? null;
        }
    }

    /**
     * Tells if the stream is detached.
     *
     * @return bool
     */
    private function isDetached()
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException("The stream is detached.");
        }
    }

    /**
     * Determines the size of a stream.
     * 
     * @param resource $stream
     * @return int|null
     */
    private function size($stream)
    {
        $wrapper_type = stream_get_meta_data($this->stream)['wrapper_type'];
        if ($wrapper_type == 'PHP') {
            $this->rewind();
            $size = strlen($this->getContents());
            $this->rewind();
        } else {
            $size = fstat($stream)['size'];
        }
        $size = (isset($size)) ? $size : null;
        return $size;
    }

    /**
     * Validates all of the given options.
     * 
     * @throws \InvalidArgumentException If an option is invalid
     */
    private function validateOptions(array &$options)
    {
        $optionsValid = true;
        foreach ($options as $key => $value) {
            switch (true) {
                case ($key === 'isText' and !is_bool($value)): // fall-through
                case ($key === 'mode' and !is_string($value)):
                case (!preg_match('/isText|mode/', $key)):
                    $optionsValid = false;
                    break;
            }
        }
        if (!$optionsValid) {
            throw new \InvalidArgumentException("Invalid options");
        }
        // Set options to default values if they are unset
        if (!isset($options['isText'])) {
            $options['isText'] = false;
        }
        if (isset($options['mode']) && !preg_match(self::ALLOWED_MODES, $options['mode'])) {
            throw new \InvalidArgumentException("Invalid mode option");
        } elseif (!isset($options['mode'])) {
            $options['mode'] = 'r+';
        }
    }

    /**
     * Tells whether a stream is readable.
     * 
     * @param resource $stream
     * @return bool
     */
    public static function readable($stream)
    {
        if (!(is_resource($stream) and get_resource_type($stream) === "stream")) {
            throw new \InvalidArgumentException("Invalid stream.");
        }
        $mode = stream_get_meta_data($stream)['mode'];
        return (bool) preg_match(self::BOTH_READABLE_WRITABLE, $mode) or preg_match(self::READABLE_ONLY, $mode);
    }

    /**
     * Tells whether a stream is writable.
     * 
     * @param resource $stream
     * @return bool
     */
    public static function writable($stream)
    {
        if (!(is_resource($stream) and get_resource_type($stream) === "stream")) {
            throw new \InvalidArgumentException("Invalid stream.");
        }
        $mode = stream_get_meta_data($stream)['mode'];
        return (bool) preg_match(self::BOTH_READABLE_WRITABLE, $mode) or preg_match(self::WRITABLE_ONLY, $mode);
    }
}
