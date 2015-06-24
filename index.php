<?php
error_reporting(E_ALL | E_STRICT); 
require 'vendor/autoload.php';
$app = new \Slim\Slim(array(
	'debug' => true,
	'log.level' => \Slim\Log::INFO,
	'log.enabled' => true,
	'log.writer' => new Slim\Extras\Log\DateTimeFileWriter(
	 	array(
	 		'path' => __DIR__ . '/logs',
	 		'name_format' => 'y-m-d'
	 		)
	 	)
));

$conf = json_decode(file_get_contents('config.json'), true);
$conf['endpoint'] = array_key_exists('endpoint', $conf) ? $conf['loglevel'] : 'https://api.voicebase.com/services';

// Allow the log level to be set from the config
if (array_key_exists('loglevel', $conf)) {
	$app->log->setLevel(constant('\Slim\Log::' . $conf['loglevel']));
	unset($conf['loglevel']); // no need to pass this to the app config
}

$app->config($conf);


$app->get('/test', function() use($app) {
	$dir = getcwd();
	print '<p>Your Tropo upload URL is <code>' . $app->request()->getUrl() . $app->request()->getScriptName() . '/recording</code></p>';
	print '<p>Your files will be stored in <code>' . $dir . '/audio</code></p>';
});

$app->post('/recording/:id', function($id) use($app) {
	$dir = getcwd();
	move_uploaded_file($_FILES['filename']['tmp_name'], "$dir/audio/$id.wav");
    $app->log->debug("SAVE $id / $dir/audio/$id.wav");

	$params = array(
		"version" => "1.1",
		"apikey" => $app->config('apikey'),
		"password" => $app->config('password'),
		"action" => "uploadMedia",
        "transcriptType" => "machine-best",
		"mediaURL" => $app->request()->getUrl() . $app->request()->getScriptName() . "/audio/$id.wav",
		"machineReadyCallBack" => $app->request()->getUrl() . $app->request()->getScriptName() . "/transcription",
		"speakerChannelFlag" =>  'true',
		"speakerNames" => 'speaker-1,speaker-2',
		"externalID" => $id
	);
	$response = Requests::post("{$app->config('endpoint')}", array(), $params);
    $app->log->debug("UPLOAD $id / " . $response->body);
    if ('SUCCESS' != json_decode($response->body)->requestStatus) {
    	$app->log->error("UPLOAD $id / " . json_decode($response->body)->statusMessage);
    }
});

$app->post('/transcription', function() use($app)  {
	$req = $app->request();
    $state = $req->params('state');
    $id = $req->params('externalId');
    $app->log->debug("CALLBACK $id / " . json_encode($req->params()));

    if ($state != 'MACHINEREADY') {
    	$app->log->error("TRANSCRIBE $id / Error in callback: $state. " . json_encode($req->params()));
    } else {
		$params = array(
			"version" => "1.1",
			"apikey" => $app->config('apikey'),
			"password" => $app->config('password'),
			"action" => "getTranscript",
			"format" => "TXT",
			"externalId" => $id
		);
		$qs = '';
		foreach ($params as $k => $v) {
			$k = urlencode($k);
			$v = urlencode($v);
			$qs .= "$k=$v&";
		}
	    $app->log->debug("REQ TRANSCRIPT $id / {$app->config('endpoint')}?$qs");

		$response = Requests::get("{$app->config('endpoint')}?$qs", array(), $params);
	    $app->log->debug("TRANSCRIPT $id / " . $response->body);

		$transcript = json_decode($response->body)->transcript;
		$dir = getcwd();

		$file = fopen("$dir/audio/$id.txt","w");
		fwrite($file,$transcript);
		fclose($file);
		$app->log->info("transcribed $id / $transcript");
    }
});

$app->run();