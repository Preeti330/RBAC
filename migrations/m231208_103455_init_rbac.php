<?php

use yii\db\Migration;

/**
 * Class m231208_103455_init_rbac
 */
class m231208_103455_init_rbac extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
 
        $auth = Yii::$app->authManager;

        // add "createPost" permission
        $Rbac = $auth->createPermission('Rbac');
        $Rbac->description = 'Rbac As post method';
        $auth->add($Rbac);

         // add "createGet" permission
         $Displaysum = $auth->createPermission('Displaysum');
         $Displaysum->description = 'Displaysum As get method';
         $auth->add($Displaysum);

		
		//create role
		$hubadmin = $auth->createRole('hubadmin');
        $auth->add($hubadmin);

        //add child-prenet config for / permison for Rbac 
        $auth->addChild($hubadmin, $Rbac);
         //assign user id for hubadmin
         $auth->assign($hubadmin, 301);

        //add child-prenet config for / permison for  Displaysum
        $auth->addChild($hubadmin, $Displaysum);
		
		//assign roles to users ,301 is user id
		 $auth->assign($hubadmin,301);



    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m231208_103455_init_rbac cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m231208_103455_init_rbac cannot be reverted.\n";

        return false;
    }
    */
}
