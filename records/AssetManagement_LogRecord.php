<?php
namespace Craft;

class AssetManagement_LogRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'assetmanagement_log';
	}

	protected function defineAttributes()
	{
		return array(
			'fileid' => AttributeType::Number,
			'filename' => AttributeType::String,
			'filetype' => AttributeType::String,

			'file_modified' => AttributeType::DateTime,
			'file_created' => AttributeType::DateTime,
			'file_updated' => AttributeType::DateTime,

			'ip' => AttributeType::String,
			'date_downloaded' => AttributeType::DateTime,

			'source_id' => AttributeType::Number,
			'folder_id' => AttributeType::Number,

			'member_id' => AttributeType::Number,
			'member_name' => AttributeType::String
		);
	}
}