<?php


namespace Domain;


use Infrastructure\Repository\PageRepository;

class PayloadReport implements Report
{
    /** @var PageRepository $payloadTimes */
    protected $payloadTimes;

    /** @var array $details */
    protected $details;

    /** @var string|null $text */
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

    protected $withDetails = false;

    public function __construct(array $payloadTimes, string $method, string $url, int $iterations,
                                int $concurrentRequests, ?array $headers, ?string $data, array $details = [])
    {
        $this->payloadTimes       = $payloadTimes;
        $this->method             = $method;
        $this->url                = $url;
        $this->iterations         = $iterations;
        $this->concurrentRequests = $concurrentRequests;
        $this->headers            = $headers ?? [];
        $this->data               = $data;
        $this->details            = $details;
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

    public function withDetails(bool $value = true): self
    {
        $this->withDetails = $value;
        return $this;
    }

    public function getContent(): string
    {
        if ($this->text === null) {
            $this->text = $this->getReportHeader();

            $headerSize   = $requestSize = $totalTime = $nameLookupTime = $connectTime = $preTransferTime = $uploadSize = 0;
            $downloadSize = $uploadSpeed = $downloadSpeed = $startTransferTime = $errorCounts = 0;

            foreach ($this->details as $iteration => $iterationDetails) {
                $iHeaderSize = $iRequestSize = $iTotalTime = $iNameLookupTime = $iConnectTime = $iPreTransferTime = 0;
                $iUploadSize = $iDownloadSize = $iUploadSpeed = $iDownloadSpeed = $iStartTransferTime = 0;

                foreach ($iterationDetails as $requestInfo) {
                    $headerSize        += $requestInfo['header_size'];
                    $requestSize       += $requestInfo['request_size'];
                    $totalTime         += $requestInfo['total_time'];
                    $nameLookupTime    += $requestInfo['namelookup_time'];
                    $connectTime       += $requestInfo['connect_time'];
                    $preTransferTime   += $requestInfo['pretransfer_time'];
                    $uploadSize        += $requestInfo['size_upload'];
                    $downloadSize      += $requestInfo['size_download'];
                    $uploadSpeed       += $requestInfo['speed_upload'];
                    $downloadSpeed     += $requestInfo['speed_download'];
                    $startTransferTime += $requestInfo['starttransfer_time'];

                    $iHeaderSize        += $requestInfo['header_size'];
                    $iRequestSize       += $requestInfo['request_size'];
                    $iTotalTime         += $requestInfo['total_time'];
                    $iNameLookupTime    += $requestInfo['namelookup_time'];
                    $iConnectTime       += $requestInfo['connect_time'];
                    $iPreTransferTime   += $requestInfo['pretransfer_time'];
                    $iUploadSize        += $requestInfo['size_upload'];
                    $iDownloadSize      += $requestInfo['size_download'];
                    $iUploadSpeed       += $requestInfo['speed_upload'];
                    $iDownloadSpeed     += $requestInfo['speed_download'];
                    $iStartTransferTime += $requestInfo['starttransfer_time'];

                    if ($requestInfo['http_code'] > 299) {
                        echo "\nProblem occurred: HTTP code ", $requestInfo['http_code'], ', iteration ', $iteration +
                                                                                                          1;
                        $errorCounts++;
                    }
                }

                if ($this->withDetails) {
                    $this->text .= sprintf("\n%5d. ", $iteration + 1) .
                                   $this->getFormattedReportString($iHeaderSize / $this->concurrentRequests,
                                                                   $iRequestSize / $this->concurrentRequests,
                                                                   $iUploadSize / $this->concurrentRequests,
                                                                   $iDownloadSize / $this->concurrentRequests,
                                                                   $iUploadSpeed / $this->concurrentRequests,
                                                                   $iDownloadSpeed / $this->concurrentRequests,
                                                                   $iTotalTime / $this->concurrentRequests,
                                                                   $iNameLookupTime / $this->concurrentRequests,
                                                                   $iConnectTime / $this->concurrentRequests,
                                                                   $iPreTransferTime / $this->concurrentRequests,
                                                                   $iStartTransferTime / $this->concurrentRequests);
                }
            }

            if ($errorCounts) {
                $this->text .= "\n$errorCounts errors (HTTP code not equal 2xx)";
            }

            $count      = $this->iterations * $this->concurrentRequests;
            $this->text .= "\n\nAverage request parameters:\n";
            $this->text .= $this->getFormattedReportString($headerSize / $count, $requestSize / $count,
                                                           $uploadSize / $count, $downloadSize / $count,
                                                           $uploadSpeed / $count, $downloadSpeed / $count,
                                                           $totalTime / $count, $nameLookupTime / $count,
                                                           $connectTime / $count, $preTransferTime / $count,
                                                           $startTransferTime / $count);
        }

        return $this->text;
    }

    protected function getReportHeader(): string
    {
        $text = "Request: $this->method $this->url\n";
        if (!empty($this->headers)) {
            $text .= "Headers:\n";
            foreach ($this->headers as $header) {
                $text .= $header . "\n";
            }
        }
        $text .= "Iterations: $this->iterations, concurrent requests: $this->concurrentRequests\n";
        if (!empty($this->data)) {
            $text .= "Request data: \n" . var_export($this->data, true) . "\n";
        }

        return $text;
    }

    protected function getFormattedReportString(int $headerSize, int $requestSize, int $uploadSize, int $downloadSize,
                                                float $uploadSpeed, float $downloadSpeed, float $totalTime,
                                                float $nameLookupTime, float $connectTime, float $preTransferTime,
                                                float $startTransferTime): string
    {
        return sprintf("Sent %d b (%d b/s), received %d b (%d b/s), lookup %7.4F s, connect %7.4F s, pre transfer %7.4F s, start transfer %8.4F s, full time %8.4F s",
                       $uploadSize, $uploadSpeed, $downloadSize, $downloadSpeed, $nameLookupTime, $connectTime,
                       $preTransferTime, $startTransferTime, $totalTime);
    }
}
