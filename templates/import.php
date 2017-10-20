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
  <?php if ($this->access('module_translator_upload') && $this->access('file_upload')) { ?>
  <li><a href="<?php echo $this->url("admin/tool/translator/{$language['code']}/upload", array('tab' => 'upload')); ?>"><?php echo $this->text('Upload'); ?></a></li>
  <?php } ?>
  <li class="active"><a href="<?php echo $this->url("admin/tool/translator/{$language['code']}/import", array('tab' => 'import')); ?>"><?php echo $this->text('Import'); ?></a></li>
</ul>
<div class="tab-content">
  <form method="post">
    <input type="hidden" name="token" value="<?php echo $_token; ?>">
    <?php if (!empty($time)) { ?>
    <p>
      <?php echo $this->text('Last updated: @date', array('@date' => $this->date($time))); ?>
      <a href="<?php echo $this->e($download_url); ?>"><?php echo $this->text('Source'); ?></a>
    </p>
    <?php } ?>
    <div class="form-inline actions">
      <?php $access_actions = false; ?>
      <?php if ($this->access('module_translator_edit') && !empty($list)) { ?>
      <?php $access_actions = true; ?>
      <div class="input-group">
        <select name="action[name]" class="form-control" onchange="Gplcart.action(this);">
          <option value=""><?php echo $this->text('With selected'); ?></option>
          <option value="import" data-confirm="<?php echo $this->text('Are you sure you want to import selected translations? If these translations already exist they will be overridden without ability to undo!'); ?>">
            <?php echo $this->text('Import'); ?>
          </option>
        </select>
        <span class="input-group-btn hidden-js">
          <button class="btn btn-default" name="action[submit]" value="1"><?php echo $this->text('OK'); ?></button>
        </span>
      </div>
      <?php } ?>
      <button class="btn btn-default" name="update" value="1">
        <i class="fa fa-refresh"></i> <?php echo $this->text('Update'); ?>
      </button>
    </div>
    <?php if (empty($list)) { ?>
    <?php echo $this->text('Nothing to display. Possible reason: failed to download/parse a source translation file or it has no content. Check system events report for possible errors and notifications'); ?>
    <?php } else { ?>
    <table class="table table-condensed import-translations">
      <thead>
        <tr>
          <?php if ($access_actions) { ?>
          <th><input type="checkbox" onchange="Gplcart.selectAll(this);"></th>
          <?php } ?>
          <th><?php echo $this->text('ID'); ?></th>
          <th><?php echo $this->text('Module'); ?></th>
          <th><?php echo $this->text('Version'); ?></th>
          <th><?php echo $this->text('Progress'); ?></th>
          <th><?php echo $this->text('Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($list as $module_id => $translations) { ?>
        <?php if(!empty($translations[$language['code']])) { ?>
        <?php $primary = array_shift($translations[$language['code']]); ?>
        <tr>
          <?php if ($access_actions) { ?>
          <td class="middle">
            <input type="checkbox" name="action[items][]" value="<?php echo $this->e($module_id); ?>-<?php echo $this->e($primary['file']); ?>">
          </td>
          <?php } ?>
          <td class="middle">
            <?php if(empty($translations[$language['code']])) { ?>
            <?php echo $this->e($module_id); ?>
            <?php } else { ?>
            <a data-toggle="collapse" href="#translation-<?php echo $this->e($module_id); ?>"><?php echo $this->e($module_id); ?></a>
            <?php } ?>
          </td>
          <td class="middle">
            <?php echo empty($modules[$module_id]['name']) ? '--' : $this->e($modules[$module_id]['name']); ?>
          </td>
          <td class="middle">
            <?php echo $primary['version'] === '' ? '--' : $this->e($primary['version']); ?>
          </td>
          <td class="middle">
            <div class="progress">
              <div class="progress-bar" role="progressbar" style="width: <?php echo $primary['progress']; ?>%;">
                <?php echo $primary['translated']; ?> / <?php echo $primary['total']; ?>
              </div>
            </div>
          </td>
          <td>
            <ul class="list-inline">
              <li><a href="<?php echo $this->url('', array('download' => "$module_id-{$primary['file']}")); ?>"><?php echo $this->lower($this->text('Download')); ?></a></li>
              <li><a href="<?php echo $this->url("admin/tool/translator/{$language['code']}/view/$module_id-{$primary['file']}", array('tab' => 'import')); ?>"><?php echo $this->lower($this->text('View')); ?></a></li>
            </ul>
          </td>
        </tr>
        <?php foreach ($translations[$language['code']] as $item) { ?>
        <tr class="active collapse" id="translation-<?php echo $this->e($module_id); ?>">
          <?php if ($access_actions) { ?>
          <td class="middle">
            <input type="checkbox" data-module-id="<?php echo $this->e($module_id); ?>" class="select-all" name="selected[]" value="<?php echo $this->e($module_id); ?>-<?php echo $this->e($item['file']); ?>">
          </td>
          <?php } ?>
          <td></td>
          <td class="middle">
            <?php echo empty($modules[$module_id]['name']) ? '--' : $this->e($modules[$module_id]['name']); ?>
          </td>
          <td class="middle">
            <?php echo $this->e($item['version']); ?>
          </td>
          <td class="middle">
            <div class="progress">
              <div class="progress-bar" role="progressbar" style="width: <?php echo $item['progress']; ?>%;">
                <?php echo $item['translated']; ?> / <?php echo $item['total']; ?>
              </div>
            </div>
          </td>
          <td>
            <ul class="list-inline">
              <li><a href="<?php echo $this->url('', array('download' => "$module_id-{$item['file']}")); ?>"><?php echo $this->lower($this->text('Download')); ?></a></li>
              <li><a href="<?php echo $this->url("admin/tool/translator/{$language['code']}/view/$module_id-{$item['file']}", array('tab' => 'import')); ?>"><?php echo $this->lower($this->text('View')); ?></a></li>
            </ul>
          </td>
        </tr>
        <?php } ?>
        <?php } ?>
        <?php } ?>
      </tbody>
    </table>
    <?php } ?>
  </form>
</div>
<style>
  .import-translations .progress {
      margin: 0;
  }
  .import-translations .progress .progress-bar {
      color: #333;
  }
</style>