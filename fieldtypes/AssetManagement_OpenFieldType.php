<?php
namespace Craft;

class AssetManagement_OpenFieldType extends BaseFieldType
{
	public function getName()
	{
		return Craft::t('Show Asset File');
	}

	public function getInputHtml($name, $value)
	{
		$element = $this->element;
		$url = $element->getUrl();
		$title = $element->getTitle();
		return "<a target='_blank' href='$url'>$title</a>";
	}
}
