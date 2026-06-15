<?php

namespace api\components;
// require_once 'james-heinrich\getid3\getid3.php';
use james_heinrich\getid3\getID3; // Use the correct namespace here
// use james_heinrich\getid3;
class VideoHelper {
    public static function getVideoInfo($videoUrl)
    {
        $getID3 = new \getID3; // Use the class directly
        try {
        if ($fp_remote = fopen($videoUrl, 'rb')) {
            $localtempfilename = tempnam('/tmp', 'getID3');
            if ($fp_local = fopen($localtempfilename, 'wb')) {
                while ($buffer = fread($fp_remote, 8192)) {
                    fwrite($fp_local, $buffer);
                }
                fclose($fp_local);

                // Analyze the video file
                $fileInfo = $getID3->analyze($localtempfilename);

                // Delete temporary file
                unlink($localtempfilename);
            }
            fclose($fp_remote);
            // mov ,mpeg , mp4, avi, webm , mp3
            if($fileInfo['fileformat']=='mpeg'){
                $mpegfileInfo = $fileInfo['mpeg']['group_of_pictures'];
                if(!empty($mpegfileInfo)){
                    $timeCode =0;
                    foreach($mpegfileInfo as $mpegData){
                        if (isset($mpegData['time_code'])) {
                            $timeCode = $mpegData['time_code'];
                        }
                    }
                    $frameRate = 30; // Frame rate of the video (adjust as needed)

                    list($hours, $minutes, $seconds, $frames) = explode(":", $timeCode);

                    $totalTimeInSeconds = ($hours * 3600) + ($minutes * 60) + $seconds + ($frames / $frameRate);
                    $videoInfo = [
                        'duration' => isset($totalTimeInSeconds) ? $totalTimeInSeconds : null,
                    ];
                
                }
            
            }else{
                $videoInfo = [
                    'duration' => isset($fileInfo['playtime_seconds']) ? $fileInfo['playtime_seconds'] : null,
                    // 'thumbnails' => isset($fileInfo['comments']['picture']) ? $fileInfo['comments']['picture'] : null,
                ];
            }
            return $videoInfo;

        }else{
            return 'Url is not found!';
        }
    } catch (\Exception $e) {
        return ['error' => 'An error occurred: ' . $e->getMessage()];
    }
    
    }
    
}
