<?php

class ga4_event_tracker {

	// your measurement id
	private const MEASUREMENT_ID = 'G-ABCDEFGHI1';

	public static function track_event_ga4($ga_category, $ga_action = '', $ga_label = '', $ga_value = '') : bool
	{
		$data = [
			'v' => 2, // version num
			'tid' => self::MEASUREMENT_ID, // GA4 Measurement ID
			'cid' => self::ga_extract_cid_from_cookies(),
			'en' => $ga_category, // event name
			'ep.eventAction' => $ga_action, // Action
			'ep.eventLabel' => $ga_label, // Label
			'epn.eventValue' => $ga_value, // Value
			'seg' => 1, // Session engaged - not sure what this means but might be similar to the old ni/Non-Interaction field. Thinking the value should be 1 to indicate the user is engaged in this action.
			'sid' => '', // session id - we pick this up later
			'sct' => '', // session count i.e. the number of sessions counted for a user - we pick this up later

			'ua' => rawurlencode($_SERVER['HTTP_USER_AGENT']), // User Agent - I'm not sure GA4 actually uses this but passing it seemingly does no harm (universal analytics did use it afaik)

			/////////////////////////
			// Unused/Unknown vars //
			/////////////////////////
			// 'dl' => 'https://www.example.com/the/page/123/', // document location (not relevant server-side)
			// 'dt' => 'The Page Title | Acme Inc', // document title (not relevant server-side)
			// 'dr' => 'https://www.anothersite.com/the/page/123/', // document referer
			// 'sr' => '1920x1080', // screen resolution
			// 'ul' => 'en-gb', // user language - could add this in future?
			// 'gtm' => '2oe9d0', // unsure what this is
			// '_s' => '2', // session hit count, not sure if we can find this reliably, could be in cookies perhaps
			// '_p' => '38757555', // don't know what this is
			// '_et' => '3309', // don't know what this is
		];

		// add tid/sid/sct
		$sid_and_sct = self::ga_extract_sid_and_sct_from_cookies(self::MEASUREMENT_ID);
		$data['sid'] = $sid_and_sct['sid'];
		$data['sct'] = $sid_and_sct['sct'];

		self::track_event_send_data($data);

		return(true);
	}

	// Send Data to Google Analytics
	private static function track_event_send_data(array $data)
	{
		$post_url = 'https://www.google-analytics.com/g/collect?';
		// remove empty values and add to $post_url
		$post_url .= http_build_query( array_filter($data) );

		// send via curl
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $post_url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		curl_close($ch);

		return $result;
	}

	// handle the parsing of the _ga cookie or setting it to a unique identifier
	private static function ga_extract_cid_from_cookies() {

		if(isset($_COOKIE['_ga']))
		{
			preg_match_all('/^(\w+\.){2}(?<cid>\w+\.\w+)/', $_COOKIE['_ga'], $matches);
			$cid = $matches['cid'][0];
		}
		else
		{
			$time = time();
			// make up a _ga cookie using variables based on what we know about the _ga cookie, advice taken from multiple sources including - http://taylrr.co.uk/blog/server-side-analytics/
			// will need to check against the js cookies now and then to ensure compatability
			$cid = rand(1000000000, 2147483647).".{$time}";
			$numDomainComponents = count(explode('.', preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'])));
			$ga = "GA1.{$numDomainComponents}.{$cid}";
			setcookie('_ga', $ga, $time+63115200, '/', $_SERVER['HTTP_HOST'], false, false);
			$_COOKIE['_ga'] = $ga;
		}

		return $cid;
	}

	// attempt to extract sid and sct from cookies
	private static function ga_extract_sid_and_sct_from_cookies(string $measurement_id) : array {

		$result = ['sid' => '', 'sct' => ''];

		$measurement_id_suffix = substr($measurement_id, 2);

		$cookie_name = "_ga_{$measurement_id_suffix}";
		if(isset($_COOKIE[$cookie_name]))
		{
			preg_match_all('/^(\w+\.){2}(?<sid>\w+)\.(?<sct>\w+)/', $_COOKIE[$cookie_name], $matches);
			$result['sid'] = $matches['sid'][0];
			$result['sct'] = $matches['sct'][0];
		}
		else
		{
			// In future we could attempt to create our own sid and sct,
			// but not sure if this is safe or worth doing at this point.
			// It would probably start like this though:
			// $result['sid'] = rand(1000000000, 2147483647);
			// $result['sct'] = 1;
		}

		return $result;
	}
}
?>