<?php

namespace Craft;

class AssetManagementPlugin extends BasePlugin
{
//	protected $allowedCharactersPattern = '[a-z0-9\-\_\.]';

	public function getName()
	{
		return $this->getSettings()->pluginName;
	}

	public function getVersion()
	{
		return '2.0';
	}

	public function getDeveloper()
	{
		return 'Dr. Ramon Saccilotto';
	}

	public function getDeveloperUrl()
	{
		return 'https://github.com/tikiatua';
	}

	// the plugin does indeed have a control panel section
	public function hasCpSection()
	{
		return true;
	}

	// register our public site routes
	public function registerSiteRoutes()
	{
		// NOTE: the second route is used to capture files in subdirectories as well
		return array(
			'internal/(?P<id>.\d*)' => array('action' => 'AssetManagement/Assets/findById'),
			'internal/(?P<directory>.*)/(?P<name>.*)' => array('action' => 'AssetManagement/Assets/findInDirectory')
		);
	}

	// register our admin site routes
	public function registerCpSiteRoutes()
	{
		return array();
	}

	public function registerUserPermissions()
	{
		return array(
			'canSetPublicAccessToAssets' => array('label' => Craft::t('Kann die Einstellungen für den öffentlichen Zugang zu geschützten Dateien ändern')),
		);
	}

	// our custom plugin settings
	protected function defineSettings()
	{
		return array(
			// set a custom plugin name for the control panel
			"pluginName"              => array(AttributeType::String, 'default' => 'Asset Management'),

			"sanitizeFilenames"       => array(AttributeType::Bool, 'default' => false),        // will make sure that our file-names do not mess up urls
			"addTimestampToFilename" => array(AttributeType::Bool, 'default' => false),         // adds a timestamp to each file
			"preventFileReplacement"  => array(AttributeType::Bool, 'default' => false),        // prevents replacement of files

			// the error message to show, if replacement of files is prevented
			"replacementErrorMessage" => array(AttributeType::String , 'default' => 'Replacement of files has been disabled by the administrator')
		);
	}

	public function getSettingsHtml()
	{
		return false;
	}

	// add some event handlers for assets
	public function init()
	{
		// load the plugin settings
		$settings = $this->getSettings();

		// prevent replacement of assets option is activated
		if ($settings->preventFileReplacement == true) {

			// MAYBE: we could add localization to this message
			$errorMessage = $settings->replacementErrorMessage;
			craft()->assetManagement->registerHandlerForFileReplacement($errorMessage);

		}

		$sanitizeIt = $settings->sanitizeFilenames;
		$stampIt = $settings->addTimestampToFilename;

		// should we handle saving of assets
		if ($sanitizeIt == true || $stampIt == true) {
			craft()->assetManagement->registerHandlerForSanitizingAndStamping($sanitizeIt, $stampIt);
		}

	}

}
