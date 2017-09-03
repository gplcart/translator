<?php
/**
 * @package Translator
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2017, Iurii Makukh <gplcart.software@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0+
 * @var $this \gplcart\core\controllers\backend\Controller
 */
?>
<ul class="nav nav-tabs">
  <li><a href="<?php echo $this->url("admin/tool/translator/{$language['code']}"); ?>"><?php echo $this->text('Primary'); ?></a></li>
  <li><a href="<?php echo $this->url("admin/tool/translator/{$language['code']}", array('compiled' => true)); ?>"><?php echo $this->text('Compiled'); ?></a></li>
  <li class="active"><a href="<?php echo $this->url("admin/tool/translator/{$language['code']}/add"); ?>"><?php echo $this->text('Upload'); ?></a></li>
</ul>
<div class="tab-content">
  <form method="post" enctype="multipart/form-data" class="form-horizontal">
    <input type="hidden" name="token" value="<?php echo $_token; ?>">
    <div class="form-group">
      <label class="col-md-2 control-label"><?php echo $this->text('Context'); ?></label>
      <div class="col-md-4">
        <select name="translation[context]" class="form-control">
          <option value=""><?php echo $this->text('Core'); ?></option>
          <optgroup label="<?php echo $this->text('Modules'); ?>">
            <?php foreach ($modules as $id => $module) { ?>
            <option value="<?php echo $id; ?>"<?php echo isset($translation['context']) && $translation['context'] == $id ? ' selected' : ''; ?>><?php echo $this->e($module['name']); ?></option>
            <?php } ?>
          </optgroup>
        </select>
        <div class="help-block">
          <?php echo $this->text('Select a context for this translation. Context defines where the file will be uploaded'); ?>
        </div>
      </div>
    </div>
    <div class="form-group required<?php echo $this->error('file', ' has-error'); ?>">
      <label class="col-md-2 control-label"><?php echo $this->text('CSV file'); ?></label>
      <div class="col-md-4">
        <input type="file" class="form-control" name="file">
        <div class="help-block">
          <?php echo $this->error('file'); ?>
          <div class="text-muted"><?php echo $this->text('Select a CSV file containing translations for the selected context'); ?></div>
        </div>
      </div>
    </div>
    <div class="form-group">
      <div class="col-md-4 col-md-offset-2 text-right">
        <button class="btn btn-default save" name="save" value="1" onclick="return confirm(GplCart.text('Are you sure? If the selected context already has a translation it will be overridden!'));"><?php echo $this->text('Save'); ?></button>
      </div>
    </div>
  </form>
</div>