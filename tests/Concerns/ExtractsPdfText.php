<?php

declare(strict_types=1);

namespace Tests\Concerns;

trait ExtractsPdfText
{
    protected function extractPdfText(string $path): string
    {
        $binary = (string) file_get_contents($path);

        preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $binary, $matches);

        $text = '';

        foreach ($matches[1] ?? [] as $stream) {
            $decoded = @gzuncompress($stream);

            if ($decoded === false) {
                $decoded = @gzinflate($stream);
            }

            $text .= $decoded !== false ? $decoded : $stream;
        }

        return $text;
    }
}
