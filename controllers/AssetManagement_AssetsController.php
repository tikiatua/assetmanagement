<?php
namespace Craft;

class AssetManagement_AssetsController extends BaseController {

	protected $allowAnonymous = array('actionFindById', 'actionFindInDirectory');

	// find the asset by its id
    public function actionFindById()
    {
        // extract the file id from the parameters
        $fileId = $this->actionParams['variables']['matches']['id'];

	    // find the corresponding file using the craft assets service
        $file = craft()->assets->findFile(array('id' => $fileId));

	    // throw an error if there is no file
	    if (!$file){
		    throw new HttpException(404, 'Asset not available');
	    }

        // deliver file to the client (if permission is granted)
	    craft()->assetManagement->sendFileToClient($file);

    }

    // find the asset through its path with directory and id
	// (this is also used from the control panel)
    public function actionFindInDirectory()
    {
        // get the directory and filename from the paramaters
        $directoryName = $this->actionParams['variables']['matches']['directory'];
        $fileName= $this->actionParams['variables']['matches']['name'];

        // create a resolved path from the directory name
        $filePath = craft()->config->parseEnvironmentString($directoryName);

        // load all transforms to check if the requested file is a transformed version
	    // thanks to @FrankZwiers for implementing this part
        $availableTransforms = craft()->assetTransforms->getAllTransforms();
        $requestedTransform = '';

        if (count($availableTransforms) > 0) {
            foreach ($availableTransforms as $transform) {
                // Check if the file path ends with the handle of the specified transform
                if (substr($filePath, -strlen($transform->handle)) === $transform->handle) {
                    $requestedTransform = '_' . $transform->handle;
                }
            }
        }

        // find all files with matching file names
        $files = craft()->assetManagement->findFilesByName($fileName);

        // we are going to need the full path to the filename
        foreach($files as $file) {

            // get the full path to the file directory
            $path = craft()->assetManagement->getFullDirectoryPath($file) . $requestedTransform;

            // check if our requested filepath matches the files path
            if (strpos($path, $filePath)) {

                if ($file) {
                    // deliver file to the client (if permission is granted)
	                craft()->assetManagement->sendFileToClient($file);
                    exit;
                }
            }
        }

        // throw 404 if no matching file was found
	    throw new HttpException(404, 'Asset not available');

    }

}
