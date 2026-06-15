<?php

namespace common\models;

use yii\db\ActiveRecord;

class SafetyScanQueue extends ActiveRecord
{
    const STATUS_QUEUED = 'queued';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    public static function tableName()
    {
        return 'safety_scan_queue';
    }

    public static function enqueue($videoId, $mediaUrl, $textPayload = null)
    {
        $job = new static([
            'video_id' => (int) $videoId,
            'media_url' => $mediaUrl,
            'text_payload' => $textPayload,
            'status' => self::STATUS_QUEUED,
        ]);
        $job->save(false);
        return $job;
    }
}
