<?php
namespace Craft;

class AssetManagement_AccessRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'assetmanagement_access';
	}

	protected function defineAttributes()
	{
		return array(
			'publicAccess' => AttributeType::Mixed
		);
	}
}