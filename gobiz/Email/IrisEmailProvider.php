<?php

namespace Gobiz\Email;

use Closure;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class IrisEmailProvider implements EmailProviderInterface
{
    /**
     * @var string
     */
    protected $apiUrl;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * Email gửi mặc định
     *
     * @var string
     */
    protected $defaultSender;

    /**
     * Request options (https://guzzle.readthedocs.io/en/stable/request-options.html)
     *
     * @var array
     */
    protected $options = [];

    /**
     * @var Client
     */
    protected $curl;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Iris constructor
     *
     * @param array $config
     * @param LoggerInterface $logger
     */
    public function __construct(array $config, LoggerInterface $logger)
    {
        $this->apiUrl = rtrim($config['api_url'], '/') . '/';
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->defaultSender = $config['default_sender'] ?? '';
        $this->options = $config['options'] ?? [];
        $this->logger = $logger;
        $this->curl = $this->newCurl();
    }

    /**
     * @return Client
     */
    protected function newCurl()
    {
        return new Client(array_merge($this->options, [
            'base_uri' => $this->apiUrl,
            'auth' => [$this->username, $this->password],
        ]));
    }

    /**
     * Send email
     *
     * @param string|array $to
     * @param string $subject
     * @param string $content
     * @param array $options
     * @return bool
     */
    public function send($to, $subject, $content, array $options = [])
    {
        $options = Arr::add($options, 'from', $this->defaultSender);

        $options = array_merge($options, [
            'to' => (array)$to,
            'subject' => $subject,
            'content' => $content,
        ]);

        return !!$this->request(function () use ($options) {
            return $this->curl->post('messages', ['json' => $options]);
        }, $options);
    }

    /**
     * @param Closure $handler
     * @param array $request
     * @return ResponseInterface
     */
    protected function request(Closure $handler, array $request = [])
    {
        try {
            return $handler();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request' => $request,
            ]);

            return null;
        }
    }
}