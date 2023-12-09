<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "user_roles".
 *
 * @property int $id
 * @property string|null $role_name
 * @property int|null $parent_role_id
 * @property int|null $status
 * @property string|null $created_date
 * @property string|null $updated_date
 * @property string|null $role_short_name
 */
class Usre_Role extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_roles';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['parent_role_id', 'status'], 'default', 'value' => null],
            [['parent_role_id', 'status'], 'integer'],
            [['created_date', 'updated_date'], 'safe'],
            [['role_name'], 'string', 'max' => 255],
            [['role_short_name'], 'string', 'max' => 155],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'role_name' => 'Role Name',
            'parent_role_id' => 'Parent Role ID',
            'status' => 'Status',
            'created_date' => 'Created Date',
            'updated_date' => 'Updated Date',
            'role_short_name' => 'Role Short Name',
        ];
    }
}
