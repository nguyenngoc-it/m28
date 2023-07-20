<?php

namespace Gobiz\Email;

interface EmailProviderInterface
{
    /**
     * Send email
     *
     * @param string|array $to
     * @param string $subject
     * @param string $content
     * @param array $options
     * @return bool
     */
    public function send($to, $subject, $content, array $options = []);
}