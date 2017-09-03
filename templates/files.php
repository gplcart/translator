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
  <li class="<?php echo empty($compiled) ? 'active' : ''; ?>"><a href="<?php echo $this->url("admin/tool/translator/{$language['code']}"); ?>"><?php echo $this->text('Primary'); ?></a></li>
  <li class="<?php echo empty($compiled) ? '' : 'active'; ?>"><a href="<?php echo $this->url("admin/tool/translator/{$language['code']}", array('compiled' => true)); ?>"><?php echo $this->text('Compiled'); ?></a></li>
  <?php if($this->access('module_translator_add') && $this->access('file_upload')) { ?>
  <li><a href="<?php echo $this->url("admin/tool/translator/{$language['code']}/add"); ?>"><?php echo $this->text('Upload'); ?></a></li>
  <?php } ?>
</ul>
<div class="tab-content">
  <?php if (empty($files)) { ?>
  <?php echo $this->text('There are no items yet'); ?>
  <?php } else { ?>
  <?php $access_actions = false; ?>
  <?php if ($this->access('module_translator_delete')) { ?>
  <?php $access_actions = true; ?>
  <div class="btn-toolbar actions">
    <div class="btn-group">
      <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
        <?php echo $this->text('With selected'); ?> <span class="caret"></span>
      </button>
      <ul class="dropdown-menu">
        <li>
          <a data-action="delete" data-action-confirm="<?php echo $this->text('Are you sure? Primary translations will be lost forever, compiled - recreated again without the current translations'); ?>" href="#">
            <?php echo $this->text('Delete'); ?>
          </a>
        </li>
      </ul>
    </div>
  </div>
  <?php } ?>
  <table class="table table-condensed translation-files">
    <thead>
      <tr>
        <?php if ($access_actions) { ?>
        <th><input type="checkbox" id="select-all" value="1"></th>
        <?php } ?>
        <th><?php echo $this->text('File'); ?></th>
        <th><?php echo empty($compiled) ? $this->text('Module') : $this->text('Class'); ?></th>
        <th><?php echo $this->text('Filesize'); ?></th>
        <th><?php echo $this->text('Modified'); ?></th>
        <th><?php echo $this->text('Progress'); ?></th>
        <th><?php echo $this->text('Actions'); ?></th>
      </tr>
    </thead>
    <?php foreach ($files as $id => $file) { ?>
    <tr>
      <?php if ($access_actions) { ?>
      <td class="middle"><input type="checkbox" class="select-all" name="selected[]" value="<?php echo $file['hash']; ?>"></td>
      <?php } ?>
      <td><span title="<?php echo $this->e($file['path']); ?>"><?php echo $this->e($this->truncate($file['path'], 50)); ?></span></td>
      <td><?php echo is_numeric($id) ? $this->e($file['context']) : $this->e($id); ?></td>
      <td><?php echo $this->e($file['filesize']); ?></td>
      <td><?php echo $this->date($file['modified']); ?></td>
      <td>
        <div class="progress">
          <div class="progress-bar" role="progressbar" style="width: <?php echo $this->e($file['progress']['progress']); ?>%;">
            <?php echo $this->e($file['progress']['translated']); ?>/<?php echo $this->e($file['progress']['total']); ?>
          </div>
        </div>
      </td>
      <td>
        <ul class="list-inline">
          <li><a href="<?php echo $this->url('', array('download' => $file['hash'])); ?>"><?php echo $this->lower($this->text('Download')); ?></a></li>
          <?php if ($this->access('module_translator_edit') && !empty($file['progress']['total'])) { ?>
          <li><a href="<?php echo $this->url("admin/tool/translator/{$language['code']}/edit/{$file['hash']}"); ?>"><?php echo $this->lower($this->text('Edit')); ?></a></li>
          <?php } ?>
        </ul>
      </td>
    </tr>
    <?php } ?>
  </table>
  <?php } ?>
</div>
<style>
  .translation-files .progress {
      margin: 0;
  }
  .translation-files .progress .progress-bar {
      color: #333;
  }
</style>