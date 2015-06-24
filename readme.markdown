# Tropo transcription through Voicebase

Copyright (c) 2015 Tropo. Released under MIT license. See LICENSE file for
details.

How to integrate Tropo with VoiceBase's audio indexing and transcription API.

Full explanation of the code is at https://www.tropo.com/2015/06/advanced-transcription-analytics-voicebase-tropo/

VoiceBase provides an API that can transcribe in multiple languages, provides fast, accurate transcription for longer-form text, and can classify and analyze the resulting transcription. At the basic level, you can simply get a transcription back. The audio file and transcription are then stored in your Voicebase account for searching and detailed analytics. 

A Tropo recording file gets sent to a URL of your choice. To send this to Voicebase, you'll need a small application that receives the Tropo upload and then creates the Voicebase API call to send the recording for transcription. The application then waits for the transcription to be completed and does something with it, perhaps emailing it, storing in a database, or sending a text message.

The sample application uses the Slim Framework and will run on any PHP web server. It receives the Tropo recording and saves it to disk. It then asks for a basic machine transcription from Voicebase, and once Voicebase has finished transcribing the file, places the transcription in a text file with a filename that matches the audio file name.

## Installing

Rename `sample.config.json` to `config.json` and edit to add your Voicebase API key and password. Copy `config.json`. `.htaccess` and `index.php` to your web server. On your web server, use [Composer](https://getcomposer.org/) to install the dependancies [Slim Framework](http://www.slimframework.com/) and [Requests](https://github.com/rmccue/Requests). Create a directory called `audio` in the same location as index.php and make sure it is writable by the web server.

## Your Tropo application

In your Tropo application, use one of the recording methods to make a recording and set the `recordURI` to `http://your-server.com/path/to/application/recording/{uniqueid}`, replacing _your-server.com_ with the hostname of your server, _path/to/application_ with the directory where you installed the application, and {uniqueid} with an ID that will be unique per call.

Generating a unique ID can be as simple as using the timestamp and caller's phone number, like so:

	<?php
	$id = $currentCall->callerID . '-' . date('Y-m-d-His');

	record('Leave your message',
		array(
			'recordURI' => 'http://your-server.com/path/to/application/recording/' . $id
			)
		);
	?>
