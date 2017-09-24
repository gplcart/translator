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
  <?php if($this->access('module_translator')) { ?>
  <li><a href="<?php echo $this->url("admin/tool/translator/{$language['code']}"); ?>"><?php echo $this->text('Original translations'); ?></a></li>
  <li><a href="<?php echo $this->url("admin/tool/translator/{$language['code']}", array('tab' => 'compiled')); ?>"><?php echo $this->text('Compiled translations'); ?></a></li>
  <?php } ?>
  <li class="active"><a href="<?php echo $this->url("admin/tool/translator/{$language['code']}/upload", array('tab' => 'upload')); ?>"><?php echo $this->text('Upload'); ?></a></li>
  <?php if($this->access('module_translator_import')) { ?>
  <li><a href="<?php echo $this->url("admin/tool/translator/{$language['code']}/import", array('tab' => 'import')); ?>"><?php echo $this->text('Import'); ?></a></li>
  <?php } ?>
</ul>
<div class="tab-content">
  <form method="post" enctype="multipart/form-data" class="form-horizontal">
    <input type="hidden" name="token" value="<?php echo $_token; ?>">
    <div class="form-group<?php echo $this->error('scope', ' has-error'); ?>">
      <label class="col-md-2 control-label"><?php echo $this->text('Scope'); ?></label>
      <div class="col-md-4">
        <select name="translation[scope]" class="form-control">
          <option value=""><?php echo $this->text('Core'); ?></option>
          <optgroup label="<?php echo $this->text('Modules'); ?>">
            <?php foreach ($modules as $id => $module) { ?>
            <option value="<?php echo $id; ?>"<?php echo isset($translation['scope']) && $translation['scope'] == $id ? ' selected' : ''; ?>><?php echo $this->e($module['name']); ?></option>
            <?php } ?>
          </optgroup>
        </select>
        <div class="help-block">
          <?php echo $this->error('scope'); ?>
          <div class="text-muted"><?php echo $this->text('Scope defines the directory where the file will be uploaded into'); ?></div>
        </div>
      </div>
    </div>
    <div class="form-group required<?php echo $this->error('file', ' has-error'); ?>">
      <label class="col-md-2 control-label"><?php echo $this->text('CSV file'); ?></label>
      <div class="col-md-4">
        <input type="file" class="form-control" name="file">
        <div class="help-block">
          <?php echo $this->error('file'); ?>
          <div class="text-muted"><?php echo $this->text('Select a CSV file containing translations for the selected scope'); ?></div>
        </div>
      </div>
    </div>
    <div class="form-group">
      <div class="col-md-4 col-md-offset-2">
        <button class="btn btn-default save" name="save" value="1" onclick="return confirm('<?php echo $this->text('Are you sure? Existing translation for the selected context will be overridden!'); ?>');"><?php echo $this->text('Upload'); ?></button>
      </div>
    </div>
  </form>
</div>