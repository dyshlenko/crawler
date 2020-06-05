<?php


namespace Domain;


use Infrastructure\Repository\PageRepository;

class PayloadReport implements Report
{
    /**
     * @var PageRepository
     */
    protected $payloadTimes;

    /**
     * @var string|null
     */
    protected $text;

    /** @var string */
    protected $method;

    /** @var string */
    protected $url;

    /** @var int */
    protected $concurrentRequests;

    /** @var int */
    protected $iterations;

    /** @var array|null */
    protected $headers;

    /** @var string|null */
    protected $data;

    public function __construct(array $payloadTimes, string $method, string $url, int $iterations,
                                int $concurrentRequests, ?array $headers, ?string $data)
    {
        $this->payloadTimes       = $payloadTimes;
        $this->method             = $method;
        $this->url                = $url;
        $this->iterations         = $iterations;
        $this->concurrentRequests = $concurrentRequests;
        $this->headers            = $headers ?? [];
        $this->data               = $data;
    }

    /**
     * Get default report filename
     *
     * @return string
     */
    public function getDefaultFilename(): string
    {
        return 'report_' . date('d.m.Y-H.i.s') . '.txt';
    }

    public function getContent(): string
    {
        if ($this->text === null) {
            $this->text = "Request: $this->method $this->url\n";
            if (!empty($this->headers)) {
                $this->text .= "Headers:\n";
                foreach ($this->headers as $header) {
                    $this->text .= $header . "\n";
                }
            }
            $this->text .= "Iterations: $this->iterations, concurrent requests: $this->concurrentRequests\n";
            if (!empty($this->data)) {
                $this->text .= "Request data: \n" . var_export($this->data, true) . "\n";
            }
            $this->text .= sprintf("Average request time: %.4f sec.\n", array_sum($this->payloadTimes) / count($this->payloadTimes));
        }

        return $this->text;
    }
}
