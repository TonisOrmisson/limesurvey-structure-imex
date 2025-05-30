# Installation & set-up
## Install to plugins folder

```
cd /LimeSurveyFolder/plugins
```


```
git clone https://github.com/TonisOrmisson/limesurvey-structure-imex.git  StructureImEx
```

```
cd StructureImEx && composer install
```

##
Activate plugin from Plugin manager

##
Find the plugin Import / Export buttons from survey tools menu.

![menu](images/menu.png)

# Updating

go to plugin folder
```
cd /LimeSurveyFolder/plugins/StructureImEx
```

Get updates via git.
`git pull` or `git fetch --all && git checkout my-version-tag`


Run install command to make sure dependencies are updated if necessary.
```
composer install --no-dev && composer dump-autoload
```
