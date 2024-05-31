<?php

namespace GP_Google_Sheets\Dependencies\GuzzleHttp;

use GP_Google_Sheets\Dependencies\Psr\Http\Message\MessageInterface;
final class BodySummarizer implements BodySummarizerInterface
{
    /**
     * @var int|null
     */
    private $truncateAt;
    public function __construct(int $truncateAt = null)
    {
        $this->truncateAt = $truncateAt;
    }
    /**
     * Returns a summarized message body.
     */
    public function summarize(MessageInterface $message) : ?string
    {
        return $this->truncateAt === null ? \GP_Google_Sheets\Dependencies\GuzzleHttp\Psr7\Message::bodySummary($message) : \GP_Google_Sheets\Dependencies\GuzzleHttp\Psr7\Message::bodySummary($message, $this->truncateAt);
    }
}
