<?php
namespace Craft;

class AssetManagement_LogModel extends BaseModel
{
	protected function defineAttributes()
	{
		return array(
			'id' => AttributeType::Number,
			'date' => AttributeType::DateTime,
			'ip' => AttributeType::String,
			'source_id' => AttributeType::Number,
			'source_name' => AttributeType::String,
			'filename' => AttributeType::String,
			'filetype' => AttributeType::String,
			'member_id' => AttributeType::Number,
			'member_name' => AttributeType::String
		);
	}
}