<?php
namespace Craft;

class AssetManagement_AdminController extends BaseController
{

	public function actionSetAccess()
	{
		$this->requirePostRequest();
		$data = craft()->request->getPost();
		$accessSettings = new AssetManagement_AccessModel();
		$accessSettings->publicAccess = $data['publicAccess'];

		if (craft()->assetManagement->setAccessSettings($accessSettings))
		{
			craft()->userSession->setNotice(Craft::t('Access settings saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			// Prepare a flash error message for the user.
			craft()->userSession->setError(Craft::t('Couldn’t save your settings.'));
		}
	}
}