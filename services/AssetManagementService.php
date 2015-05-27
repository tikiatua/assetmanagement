<?php
namespace Craft;

class AssetManagementService extends BaseApplicationComponent
{

	// --- FILE SERVING ---

	// find all files with the given filename
	public function findFilesByName($fileName)
	{
		// define our matching criteria
		$match = array('filename' => $fileName);

		// get an element criteria model for our files
		$criteria = craft()->elements->getCriteria(ElementType::Asset, $match);

		// fetch the results from the database
		return $criteria->find();
	}

	// get the full directory path for an asset
	public function getFullDirectoryPath(AssetFileModel $file)
	{
		// get the path to our file and replace environment specific variables
		// NOTE: this will only get the path to the root folder
		$source = $file->getSource();
		$settings = $source->getAttribute('settings');

		$path = $settings['path'];

		// now we have to attach the path to the subfolder
		// NOTE: sub-sub-folders store the path relative to the root (so were good for now)
		$path = $path . $file->folder->getAttribute('path');

		// and parse the path-string as environment variable (resolving placeholders)
		$path = craft()->config->parseEnvironmentString($path);

		return $path;
	}

	// send the file to the client
	public function sendFileToClient(AssetFileModel $file, $path = NULL)
	{
		// get the full path to the file, if not supplied by the caller
		if (is_null($path)) {
			$path = craft()->assetManagement->getFullDirectoryPath($file);
		}

		// we have to init the session to get the user-model
		craft()->userSession->init();
		$user = craft()->userSession->getUser();

		if (is_null($user)) {
			$user = new UserModel();
		}

		// throw an error if the current user does not have permission to view the file
		if ($this->permissionToViewFile($user, $file) == false) {
			throw new HttpException(404, 'Asset not available');
		}

		// construct the full filepath
		$filename = $file->getAttribute('filename');
		$filepath = $path . '/' . $filename;

		// get the files mime type
		$mimeType = $file->getMimeType();

		// use an optimized header for pdfs
		if ($mimeType == "application/pdf") {

			// we need some special handling for ie10
			$userAgent = craft()->getRequest()->getUserAgent();

			if (preg_match('/(MSIE\s10.0\;)/', $userAgent)) {
				header('Content-type: application/force-download');
				header('Content-Disposition: attachment; filename="' . $filename . '"');
			} else {
				header('Content-type: application/pdf');
				header('Content-Disposition: inline; filename="' . $filename . '"');
				header('Content-Transfer-Encoding: binary');
			}

			header('Content-Length: ' . filesize($filepath));

			// note: do not send the header 'Accept-Ranges: bytes', as this
			// would allow the browser to request the pdf in slices
			// -> triggering a file-tracking with each request

		}
		else {
			header('Content-type: ' . $mimeType);
		}

		// NOTE: we could also use file_get_contents($filepath)
		// in contrast to readfile this will read the complete file content
		// into a string which can then be sent to the output.
		// This will however use more memory than the readfile-approach.

		// render the file content directly to the output
		readfile($filepath);

		// track download
		$this->trackDownload($file, $path);

	}


	// check if the current user should have access to a certain asset
	public function permissionToViewFile(UserModel $user, AssetFileModel $file) {

		// we have multiple access levels

		// 1. asset source should be publicly available (defined in the plugin settings)
		if ($this->isPubliclyAccessible($file->sourceId) == true) {
			return true;
		}

		// 2. user has access to the assets source through the control panel settings
		//    (i.e. via group or profile permissions, includes all admins)
		if ($user->can('viewassetsource:' . $file->sourceId) == true){
			return true;
		}

		// 3. user is granted access through a custom plugin hook
		//    (access is granted if any of the plugins returns true)
		$otherPlugins = craft()->plugins->call('grantAccessToAssets', array($user, $file));
		if (in_array(true, $otherPlugins) == true) {
			return true;
		}

		// user does not have access
		return false;

	}


	// --- PUBLICLY AVAILABLE SOURCES ---

	// save public access settings
	public function setAccessSettings(AssetManagement_AccessModel $accessSettings)
	{
		// clear out all our previous records
		$record = AssetManagement_AccessRecord::model();
		$record->deleteAll();

		// create a new data-model and save it
		$record = new AssetManagement_AccessRecord();
		$record->publicAccess = $accessSettings->publicAccess;
		return $record->save();

	}

	// get the existing access settings
	public function getAccessSettings() {
		$record = AssetManagement_AccessRecord::model();

		// get the first element
		$settings = $record->find();

		// return the data as model
		return AssetManagement_AccessModel::populateModel($settings);
	}

	// get all asset sources that are currently publiclyAccessible
	public function publiclyAccessibleSources()
	{
		// load the currently accessible sources from our save settings
		$currentAccessSettings = craft()->assetManagement->getAccessSettings();
		$publicAccess = $currentAccessSettings->publicAccess;
		return is_array($publicAccess) ? $publicAccess : [];
	}

	// check if the given source is publicly accessible
	public function isPubliclyAccessible($sourceid)
	{
		// load the currently accessible sources from our save settings
		$currentlyPubliclyAccessible = $this->publiclyAccessibleSources();
		return in_array($sourceid, $currentlyPubliclyAccessible);
	}


	// --- ASSET PATHS AND REPLACEMENT ---

	// convert a string to ascii characters only
	public function convertToAscii($text)
	{
		$textarr = str_split($text);
		$asAscii = '';
		foreach ($textarr as $char) {
			$charno = ord($char);
			if ($charno > 31 && $charno < 127) {
				$asAscii .= $char;
			}
		}
		return $asAscii;
	}

	// register event handlers to prevent asset replacement
	public function registerHandlerForFileReplacement($errorMessage)
	{

		// prevent replacement of assets through the corresponding control panel action
		$handleOnBeforeReplaceFile = function (Event $event) use ($errorMessage) {

			// cancel the replace event
			$event->performAction = FALSE;

			// add a message for the user
			throw new Exception($errorMessage);

		};

		// prevent replacement when uploading a file that already exists
		$handleOnBeforeUploadAsset = function (Event $event) use ($errorMessage) {

			// get the target folder and filename
			$folder = $event->params["folder"];
			$filename = $event->params["filename"];

			// find all files in the specified target folder
			$criteria = craft()->elements->getCriteria(ElementType::Asset);
			$criteria->folderId = $folder->id;
			$files = $criteria->find();

			$filenames = array_map(function ($file) {
				return $file->filename;
			}, $files);

			if (in_array($filename, $filenames)) {
				// cancel the upload event event
				$event->performAction = FALSE;

				throw new Exception($errorMessage);
			}

			return TRUE;

		};

		// attach our handler to the corresponding events
		craft()->on('assets.onBeforeReplaceFile', $handleOnBeforeReplaceFile);
		craft()->on('assets.onBeforeUploadAsset', $handleOnBeforeUploadAsset);

	}

	// register event handlers to sanitize or timestap filenames for assets
	public function registerHandlerForSanitizingAndStamping($sanitizeIt, $stampIt)
	{
		// get the timezone to add stamps to files
		$timezone = craft()->getTimeZone();

		// define a handler that starts up when we save an asset.
		// please be aware, that this handler will also be fired when
		// the asset index is updated. to avoid handling assets when
		// the index is updated use the isNewAsset param.
		$handleOnSaveAsset = function (Event $event) use ($sanitizeIt, $stampIt, $timezone) {

			$asset = $event->params['asset'];
			$isNewAsset = $event->params['isNewAsset'];
			$fullFilename = $asset->filename;

			// should the filename be changed
			$changeFilenameForCharacters = FALSE;
			$changeFilenameForTimestamp = FALSE;

			// get only the filename without extension
			$filepath = pathinfo($fullFilename);
			$extension = '.' . $filepath['extension'];
			$filename = $filepath['filename'];

			// check the url for invalid characters
			if ($sanitizeIt) {
				$notAllowedCharactersPattern = '/[^a-z0-9\-\_\.]/';
				preg_match($notAllowedCharactersPattern, $filename, $matchNotAllowedCharacters);
				$changeFilenameForCharacters = count($matchNotAllowedCharacters) > 0;

				if ($changeFilenameForCharacters) {
					// convert the name to lower case
					$filename = mb_strtolower($filename, 'UTF-8');

					// replace all umlauts
					$filename = str_replace('ü', 'ue', $filename);
					$filename = str_replace('ö', 'oe', $filename);
					$filename = str_replace('ä', 'ae', $filename);

					// remove all other non ascii characters (the rest should have been handled by craft)
					$filename = craft()->assetManagement->convertToAscii($filename);
				}

			}

			// add a timestamp
			if ($stampIt) {
				$timestampPattern = '/([\_]{1}[0-9]{14}[\.]{1})/';
				preg_match($timestampPattern, $fullFilename, $matchTimestamp);
				$changeFilenameForTimestamp = count($matchTimestamp) == 0;

				if ($changeFilenameForTimestamp) {
					date_default_timezone_set($timezone);
					$filename = $filename . '_' . date('YmdHis');
				}
			}

			// apply the new file name to the asset
			// note: this will trigger another onSaveAsset-event, so be careful with this
			if ($changeFilenameForCharacters == TRUE || $changeFilenameForTimestamp == TRUE) {

				// re-add the extension to our filename
				$filename = $filename . $extension;

				// use the moveFiles method to rename our asset
				craft()->assets->moveFiles([$asset->id], $asset->folderId, $filename, [NULL]);
			}
		};

		// attach the handler to the corresponding event
		craft()->on('assets.onSaveAsset', $handleOnSaveAsset);
	}


	// --- TRACK FILE DOWNLOADS ---

	// track all downloads
	public function trackDownload(AssetFileModel $file){

		// create a new log record
		$record = new AssetManagement_LogRecord();

		// define the attributes to keep track of
		$record->fileid = $file->id;
		$record->filename = $file->getAttribute('filename');
		$record->filetype = $file->getAttribute('kind');

		$record->file_modified = $file->dateModified;
		$record->file_created = $file->dateCreated;
		$record->file_updated = $file->dateUpdated;

		$record->ip = craft()->request->getIpAddress();
		$record->date_downloaded = DateTimeHelper::currentUTCDateTime();

		$record->source_id = $file->getAttribute('sourceId');
		$record->folder_id = $file->getAttribute('folderId');

		if (craft()->userSession->isGuest()) {
			$record->member_id = null;
			$record->member_name = "public access";
		} else {
			$user = craft()->userSession->getUser();
			$record->member_id = $user->id;
			$record->member_name = $user->username;
		}

		$record->save(false);

	}

}