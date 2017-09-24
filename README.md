[![Build Status](https://scrutinizer-ci.com/g/gplcart/translator/badges/build.png?b=master)](https://scrutinizer-ci.com/g/gplcart/translator/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gplcart/translator/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/gplcart/translator/?branch=master)

Translator is a [GPL Cart](https://github.com/gplcart/gplcart) module that allows site administrators to manage UI translations.

**Features**

- View, delete, download existing translations (including compiled files)
- Upload new translations
- Import translations from [Crowdin localization server](https://crowdin.com/project/gplcart)


**Installation**

1. Download and extract to `system/modules` manually or using composer `composer require gplcart/translator`. IMPORTANT: If you downloaded the module manually, be sure that the name of extracted module folder doesn't contain a branch/version suffix, e.g `-master`. Rename if needed.
2. Go to `admin/module/list` end enable the module
3. Grant permissions to manage translations at `admin/user/role`

**Usage**

- Start from `admin/tool/translator`