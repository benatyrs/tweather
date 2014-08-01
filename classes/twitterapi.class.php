<?php
require_once('TwitterAPIExchange.php');

class twitter {
    public static $APICallsT = 0;
    public static $FoundTweets = 0;
    public static $TrashedTweets = 0;
    private static function ReceiveTweets($OAuthArray, $SearchFor) {
        // Twitter API limits [search only]: 180 per 15 minute window
        // This means we can update our tweet script containing 5 words, in theory, every 25 seconds. We'll keep this at 30 seconds.
        // 1 word = 5 seconds per API call (180 api calls in 15 minutes) -- too fast
        // 2 words = 10 seconds per API call (90x2 api calls in 15 minutes) -- too fast
        // 3 words = 15 seconds per API call (60x3 api calls in 15 minutes) -- fine
        // 4 words = 20 seconds per API call (45x4 api calls in 15 minutes -- fine +
        // 5 words = 25 seconds... etc -- perfect ++

        // Current: 6 words, 45 seconds
        $Url = "https://api.twitter.com/1.1/search/tweets.json";

        $Fields = '?q='.$SearchFor.'&result_type=recent&count=100&include_entities=true';

        $TwitterAPI = new TwitterAPIExchange($OAuthArray);
        $Out = $TwitterAPI->setGetfield($Fields)->buildOauth($Url, "GET")->performRequest();
       /* if (!isset(json_decode($Out, true)['statuses'])) {
            return 0;
        }*/
        self::$APICallsT++;
        return $Out;
    }

    public static function Search($OAuthArray) {
        $Output = array();
        $Searches = array("Sunny", "Rain", "Cloudy", "Thunderstorm", "Windy", "Snowing");

       #$Searches = array("Sunny");
        foreach ($Searches as $Key => $Val) {
            $r = json_decode(self::ReceiveTweets($OAuthArray, $Val), true)['statuses'];
            if (!is_array($r)) { return 0; }
            foreach($r as $Key2 => $Val2) {
                self::$FoundTweets++;
                $q[$Key2]['coordinates'] = $r[$Key2]['coordinates'];
                $q[$Key2]['created_at'] = $r[$Key2]['created_at'];
                $q[$Key2]['id_str'] = $r[$Key2]['id_str'];
                $q[$Key2]['text'] = $r[$Key2]['text'];
                $q[$Key2]['geo'] = $r[$Key2]['geo'];
                $q[$Key2]['user']['name'] = $r[$Key2]['user']['name'];
                $q[$Key2]['user']['profile_image_url'] = $r[$Key2]['user']['profile_image_url'];
                $q[$Key2]['user']['location'] = $r[$Key2]['user']['location'];
                $q[$Key2]['user']['screen_name'] = $r[$Key2]['user']['screen_name'];
                $q[$Key2]['type'] = $Val;
                if ((stripos($r[$Key2]['text'], "mm") !== false || stripos($r[$Key2]['text'], "Pres") !== false)
                    && (stripos($r[$Key2]['text'], "mph") !== false || stripos($r[$Key2]['text'], "kmph") !== false || stripos($r[$Key2]['text'], "m/h") !== false || stripos($r[$Key2]['text'], "km/h") !== false)
                    && (stripos($r[$Key2]['text'], "°C") !== false || stripos($r[$Key2]['text'], "%") !== false)) {
                    $q[$Key2]['type'] = "Report";

                }
                $r[$Key2] = $q[$Key2];
                if (stripos($r[$Key2]['text'], $Val) !== false) {
                    $Continue = true;
                }
                else {
                    self::$TrashedTweets++;
                }
            // All we need is the above - a lot of unneeded data is returned in the API call.
            }
           if (isset($Continue)) {
                $Output = array_merge($Output, (array)$r);
           }
        }
        /*if (empty($Output)) {
            return 0;
        }*/

        return $Output;
    }
}