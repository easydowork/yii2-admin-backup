# yii2-admin-backup
a tools for yii2-admin role back up

### add controller in yii2 console command
```php
'controllerMap' => [
    'rbac' => 'easydowork\rbacBackup\RbacController',
],
or
'controllerMap' => [
    'rbac' => [
        'class' => 'easydowork\rbacBackup\RbacController',
        'cachePath' => '@console/rbacBackUpCache',
        'roleCachePath' => '@backend/runtime/cache',
    ],
],
```

```shell script
php yii rbac/back-up
php yii rbac/update
```