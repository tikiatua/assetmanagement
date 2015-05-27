<?php
namespace Craft;

class AssetManagementVariable
{
	public function name()
	{
		$plugin = craft()->plugins->getPlugin('assetmanagement');
		return $plugin->getName();
	}

	public function settings()
	{
		$plugin = craft()->plugins->getPlugin('assetmanagement');
		return $plugin->getSettings();
	}

	public function localAssetSources()
	{
		// load all available asset sources
		$assetSources = craft()->assetSources->getAllSources();

		// initialize a new return array
		$localSources = array();

		// we have to format our sources to be used in the form
		foreach($assetSources as &$source) {

			// skip all non local source types
			if ($source->type != "Local") {
				continue;
			}

			// extract the name as label and the id as value
			// and append it to our sources array
			$localSources[] = array(
				"value" => $source->id,
				"label" => $source->name,
			);
		}

		return $localSources;
	}

	public function currentlyPubliclyAccessible(){
		// load the currently accessible sources from our save settings
		return craft()->assetManagement->publiclyAccessibleSources();
	}
}