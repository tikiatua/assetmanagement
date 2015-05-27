# Craft CMS: Asset Management Plugin #

## Please be aware that this plugin is currently in beta. It might not work for your setup without additional tweaks, although we already use it on a daily basis in production on one of our systems.

## Description
This plugin will help you to restrict access to assets for permitted users only. Access to a given asset is only granted if one of the following conditions is met:

1. The user has view-permissions for the given asset source (this can be set in the user- or group-settings).
2. The asset source is set as publicly available in the admin panel (there is a custom page for asset management)
3. The user is given permission based on some specific code by you (can be set through a hook on «grantAccessToAssets(Usermodel $user, AssetFileModel $file)»

Additional features that can be toggled in the admin panel are:

1. Ensuring that filenames are url conform (lowercase, no special characters, umlauts are replaced by vowel-combination)
2. Add a timestamp to each filename (for version control and to prevent users from overriding files accidentally)
3. Prevent file replacement
4. Logging of all asset downloads (report view not yet available)

The asset source folder should be moved out of the webroot (set it to ../files for example) so the files are never accessible without this plugin.

## Setup & Use
The plugin registers some additional routes to check the asset permissions before it is served to the user. You can serve the asset using its fileid or the url of the file (as defined in the asset-folder configuration)

	- internal/<fileid>
	- internal/<folder>/<filename>

I would recommend to create some [environmnentVariables](http://buildwithcraft.com/docs/multi-environment-configs) to store the basepath of your assets folder and the base url of the assets-url for convenient use in the admin-panel.

**Please note that the internal assets url should use an absolute url to the "internal" route**

<pre>
define('CRAFT_SITE_URL', "http://craft.dev:8888/");

return array(
	'.dev' => array(
		'siteUrl' => CRAFT_SITE_URL,
		'environmentVariables' => array(
			'internalAssetsPath' => CRAFT_BASE_PATH . "../_files/",
			'internalAssetsUrl' => CRAFT_SITE_URL . "internal/"
		)
	)
);
</pre>

You can then use placeholders in the assets configuration.

	i.e. for a asset folder called documents
	- directory: {internalAssetsPath}/documents/
	- url: {internalAssetsUrl}/documents/


## Attribution
This plugin is initially based on the Member Assets Plugin by Jeroen Kenters, but extends it's functionality in various ways.
