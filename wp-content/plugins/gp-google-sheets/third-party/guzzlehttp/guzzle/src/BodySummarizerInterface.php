<?php

namespace GP_Google_Sheets\Dependencies\GuzzleHttp;

use GP_Google_Sheets\Dependencies\Psr\Http\Message\MessageInterface;
interface BodySummarizerInterface
{
    /**
     * Returns a summarized message body.
     */
    public function summarize(MessageInterface $message) : ?string;
}
