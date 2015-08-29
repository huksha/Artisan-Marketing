<meta http-equiv="content-type" content="text/html; charset=utf-8"></meta>
<?php    
    require_once 'vendor/autoload.php';
    session_start(); 

    // For loging out.
    if (isset($_GET['logout'])) {
		unset($_SESSION['token']);
    }

    define('CLIENT_SECRET_PATH', 'client_secret.json');
	define('SCOPES', implode(' ', array(
		Google_Service_Calendar::CALENDAR) // For readonly -> Google_Service_Calendar::CALENDAR_READONLY)
	));

    $client = new Google_Client();
    $client->setApplicationName("Google Calendar API for Artisan Marketing");
    $client->setAuthConfigFile(CLIENT_SECRET_PATH);
    $client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/ArtisanMarketing/');
    $client->setAccessType('offline');   // Gets us our refreshtoken
	$client->setScopes(SCOPES);

    // The user accepted your access now you need to exchange it.
    if (isset($_GET['code'])) {
		$client->authenticate($_GET['code']);  
		$_SESSION['token'] = $client->getAccessToken();
		$redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
		header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
    }

    // The user has not authenticated we give them a link to login    
    if (!isset($_SESSION['token'])) {
		$authUrl = $client->createAuthUrl();
		print "<a class='login' href='$authUrl'>Connect me!</a>";
    }    

    // We have access we can now create our service
    if (isset($_SESSION['token'])) {
		$client->setAccessToken($_SESSION['token']);	
		$service = new Google_Service_Calendar($client);    
		$calendarList  = $service->calendarList->listCalendarList();;
		print "Sikeres csatlakozás!<br><br>";
		print "Az adott e-mail címmel rendelkező fiókban ezek a naptárak találhatók meg:<br>";
	
		while(true) {
			foreach ($calendarList->getItems() as $calendarListEntry) {
				if(filter_var($calendarListEntry->getSummary(), FILTER_VALIDATE_EMAIL)) {
			    	echo $calendarListEntry->getSummary()."<br>\n";
				}
			}
			$pageToken = $calendarList->getNextPageToken();
			if ($pageToken) {
				$optParams = array('pageToken' => $pageToken);
				$calendarList = $service->calendarList->listCalendarList($optParams);
			} else { break; }
		}

		// Creating an event	
		$event = new Google_Service_Calendar_Event(array(
		  'summary' => 'Google Calendar API testing',
		  'location' => 'Budapest',
		  'description' => 'It is a created event by a script. :)',
		  'start' => array(
		    'dateTime' => '2015-08-31T09:00:00',
		    'timeZone' => 'Europe/Budapest',
		  ),
		  'end' => array(
		    'dateTime' => '2015-08-31T17:00:00',
		    'timeZone' => 'Europe/Budapest',
		  ),
		));

		$calendarId = 'primary';
		$event = $service->events->insert($calendarId, $event);
		printf('<br>Event created: %s', $event->htmlLink);

		// Creating a logout link
		$redirect = 'http://' . $_SERVER['HTTP_HOST'];
		print "<br><a class='logout' href='$redirect'><br>Kijelentkezés</a><br>";
    }
?>