<?php

namespace Podlove\Modules\AssemblyAI;

class VttConverter
{
    public const MAX_SEGMENT_DURATION = 5000; // 5 seconds in ms
    public const MAX_CHARS_PER_LINE = 42;
    public const MAX_LINES_PER_SEGMENT = 2;

    /**
     * Convert AssemblyAI transcript response to WebVTT string.
     *
     * @param array $response decoded AssemblyAI transcript JSON
     *
     * @return string WebVTT content
     */
    public static function convert($response)
    {
        $words = isset($response['words']) ? $response['words'] : [];
        $utterances = isset($response['utterances']) ? $response['utterances'] : [];

        if (!empty($words)) {
            return self::generateVttFromWords($words, $utterances);
        }

        return "WEBVTT\n\n";
    }

    private static function generateVttFromWords($words, $utterances)
    {
        $segments = self::createSubtitleSegments($words, $utterances);

        if (empty($segments)) {
            return "WEBVTT\n\n";
        }

        $vtt = "WEBVTT\n\n";

        foreach ($segments as $index => $segment) {
            $startTime = self::formatTimestamp($segment['start']);
            $endTime = self::formatTimestamp($segment['end']);
            $cueNumber = $index + 1;

            $vtt .= "{$cueNumber}\n";
            $vtt .= "{$startTime} --> {$endTime}\n";

            if (!empty($segment['speaker'])) {
                $speaker = 'Speaker '.$segment['speaker'];
                $vtt .= "<v {$speaker}>{$segment['text']}\n\n";
            } else {
                $vtt .= "{$segment['text']}\n\n";
            }
        }

        return $vtt;
    }

    private static function createSubtitleSegments($words, $utterances)
    {
        if (empty($words)) {
            return [];
        }

        // Build speaker map: word index -> speaker label
        // Both arrays are sorted by time, so we use a pointer walk (O(n+m))
        $wordCount = count($words);
        $speakerMap = [];
        if (!empty($utterances)) {
            $utteranceIndex = 0;
            $utteranceCount = count($utterances);

            for ($i = 0; $i < $wordCount; ++$i) {
                $word = $words[$i];

                // Advance utterance pointer past utterances that end before this word
                while ($utteranceIndex < $utteranceCount && $utterances[$utteranceIndex]['end'] < $word['start']) {
                    ++$utteranceIndex;
                }

                if ($utteranceIndex < $utteranceCount
                    && $word['start'] >= $utterances[$utteranceIndex]['start']
                    && $word['end'] <= $utterances[$utteranceIndex]['end']) {
                    $speakerMap[$i] = $utterances[$utteranceIndex]['speaker'];
                }
            }
        }

        $segments = [];
        $currentSegment = null;

        for ($i = 0; $i < $wordCount; ++$i) {
            $word = $words[$i];
            $speaker = isset($speakerMap[$i]) ? $speakerMap[$i] : null;

            // Start new segment if needed
            if ($currentSegment === null) {
                $currentSegment = [
                    'start' => $word['start'],
                    'end' => $word['end'],
                    'text' => $word['text'],
                    'speaker' => $speaker,
                ];

                continue;
            }

            // Check if we should start a new segment
            $duration = $word['end'] - $currentSegment['start'];
            $textLength = strlen($currentSegment['text']) + 1 + strlen($word['text']);
            $speakerChanged = $speaker && $currentSegment['speaker'] && $speaker !== $currentSegment['speaker'];

            $shouldBreak = $duration > self::MAX_SEGMENT_DURATION
                || $textLength > self::MAX_CHARS_PER_LINE * self::MAX_LINES_PER_SEGMENT
                || $speakerChanged;

            if ($shouldBreak) {
                $segments[] = $currentSegment;

                $currentSegment = [
                    'start' => $word['start'],
                    'end' => $word['end'],
                    'text' => $word['text'],
                    'speaker' => $speaker ?: $currentSegment['speaker'],
                ];
            } else {
                $currentSegment['end'] = $word['end'];
                $currentSegment['text'] .= ' '.$word['text'];
                if ($speaker) {
                    $currentSegment['speaker'] = $speaker;
                }
            }
        }

        // Add final segment
        if ($currentSegment !== null) {
            $segments[] = $currentSegment;
        }

        return $segments;
    }

    /**
     * Format milliseconds to VTT timestamp (HH:MM:SS.mmm).
     *
     * @param int $ms milliseconds
     *
     * @return string formatted timestamp
     */
    private static function formatTimestamp($ms)
    {
        $totalSeconds = intdiv((int) $ms, 1000);
        $hours = intdiv($totalSeconds, 3600);
        $minutes = intdiv($totalSeconds % 3600, 60);
        $seconds = $totalSeconds % 60;
        $milliseconds = (int) $ms % 1000;

        return sprintf('%02d:%02d:%02d.%03d', $hours, $minutes, $seconds, $milliseconds);
    }
}
