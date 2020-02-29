<?php

namespace Rocketeers\Laravel;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Rocketeers\Rocketeers;

class RocketeersLoggerHandler extends AbstractProcessingHandler
{
    protected $client;
    protected $request;

    public function __construct(Rocketeers $client, $level = Logger::DEBUG, $bubble = true)
    {
        $this->client = $client;
        $this->request = Request::createFromGlobals();

        parent::__construct($level, $bubble);
    }

    protected function write(array $report): void
    {
        $this->client->report([
            'channel' => $report['channel'],
            'code' => 500,
            'context' => $report['context'],
            'datetime' => $report['datetime'],
            'exception' => $report['context']['exception'],
            'extra' => $report['extra'],
            'level_name' => $report['level_name'],
            'level' => $report['level'],
            'message' => $report['message'],

            'method' => $this->request->getMethod(),
            'url' => $this->request->getUri(),
            'referrer' => $this->request->server('HTTP_REFERER'),
            'querystring' => $this->request->query->all(),
            'ip_address' => $this->request->getClientIp(),
            'hostname' => gethostbyaddr($this->request->getClientIp()),
            'user_agent' => $this->request->headers->get('User-Agent'),
            'inputs' => $this->request->all(),
            'inputs' => $this->getFiles(),
            'headers' => $this->request->headers->all(),
            'session' => $this->session->all(),
            'cookies' => $this->cookies->all(),
        ]);

        return;
    }

    protected function getFiles(): array
    {
        if (is_null($this->request->files)) {
            return [];
        }

        return $this->mapFiles($this->request->files->all());
    }

    protected function mapFiles(array $files)
    {
        return array_map(function ($file) {
            if (is_array($file)) {
                return $this->mapFiles($file);
            }

            if (!$file instanceof UploadedFile) {
                return;
            }

            try {
                $fileSize = $file->getSize();
            } catch (\RuntimeException $e) {
                $fileSize = 0;
            }

            try {
                $mimeType = $file->getMimeType();
            } catch (\Exception $e) {
                $mimeType = 'undefined';
            }

            return [
                'pathname' => $file->getPathname(),
                'size' => $fileSize,
                'mimeType' => $mimeType,
            ];
        }, $files);
    }
}
