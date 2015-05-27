<?php
namespace Craft;

class AssetManagement_AccessModel extends BaseModel
{
	protected function defineAttributes()
	{
		return array(
			'publicAccess' => AttributeType::Mixed
		);
	}
}