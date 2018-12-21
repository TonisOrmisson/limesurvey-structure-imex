# Installation & set-up
## Install to plugins folder

```
cd /LimeSurveyFolder/plugins
```


```
git clone https://github.com/TonisOrmisson/limesurvey-relevance-imex.git  RelevanceImEx
```

```
cd RelevanceImEx && composer install
```

##
Activate plugin from Plugin manager

##
Find the plugin Import / Export buttons from survey tools menu.

![menu](images/menu.png)

# Usage
# Conditions will be deleted!

Be careful. If you use the Import to upload survey logic, ALL MANUAL CONDITIONS WILL BE REMOVED!

# Updating

go to plugin folder
```
cd /LimeSurveyFolder/plugins/RelevanceImEx
```

Get updates via git.
`git pull` or `git fetch --all && git checkout my-version-tag`


Run install command to make sure dependencies are updated if necessary.
```
composer install --no-dev
```
