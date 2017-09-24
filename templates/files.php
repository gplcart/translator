<?php
/**
 * @package Translator
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2017, Iurii Makukh <gplcart.software@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0+
 * @var $this \gplcart\core\controllers\backend\Controller
 */
?>
<form method="post">
  <input type="hidden" name="token" value="<?php echo $_token; ?>">
  <ul class="nav nav-tabs">
    <li class="<?php echo empty($_query['tab']) ? 'active' : ''; ?>"><a href="<?php echo $this->url("admin/tool/translator/{$language['code']}"); ?>"><?php echo $this->text('Original translations'); ?></a></li>
    <li class="<?php echo (isset($_query['tab']) && $_query['tab'] === 'compiled') ? 'active' : ''; ?>"><a href="<?php echo $this->url("admin/tool/translator/{$language['code']}", array('tab' => 'compiled')); ?>"><?php echo $this->text('Compiled translations'); ?></a></li>
    <?php if ($this->access('module_translator_upload') && $this->access('file_upload')) { ?>
    <li class="<?php echo (isset($_query['tab']) && $_query['tab'] === 'upload') ? 'active' : ''; ?>"><a href="<?php echo $this->url("admin/tool/translator/{$language['code']}/upload", array('tab' => 'upload')); ?>"><?php echo $this->text('Upload'); ?></a></li>
    <?php } ?>
    <?php if ($this->access('module_translator_import')) { ?>
    <li class="<?php echo (isset($_query['tab']) && $_query['tab'] === 'import') ? 'active' : ''; ?>"><a href="<?php echo $this->url("admin/tool/translator/{$language['code']}/import", array('tab' => 'import')); ?>"><?php echo $this->text('Import'); ?></a></li>
    <?php } ?>
  </ul>
  <div class="tab-content">
    <?php if (empty($files)) { ?>
    <?php echo $this->text('There are no items yet'); ?>
    <?php } else { ?>
    <?php $access_actions = false; ?>
    <?php if ($this->access('module_translator_delete')) { ?>
    <?php $access_actions = true; ?>
    <div class="form-inline actions">
      <div class="input-group">
        <select name="action[name]" class="form-control" onchange="Gplcart.action(this);">
          <option value=""><?php echo $this->text('With selected'); ?></option>
          <option value="delete" data-confirm="<?php echo $this->text('Are you sure? Original translations will be lost forever, compiled - recreated again without the current translations'); ?>">
            <?php echo $this->text('Delete'); ?>
          </option>
        </select>
        <span class="input-group-btn hidden-js">
          <button class="btn btn-default" name="action[submit]" value="1"><?php echo $this->text('OK'); ?></button>
        </span>
      </div>
    </div>
    <?php } ?>
    <table class="table table-condensed translation-files">
      <thead>
        <tr>
          <?php if ($access_actions) { ?>
          <th><input type="checkbox" onchange="Gplcart.selectAll(this);"></th>
          <?php } ?>
          <th><?php echo $this->text('File'); ?></th>
          <th><?php echo (isset($_query['tab']) && $_query['tab'] === 'compiled') ? $this->text('URL') : $this->text('Module'); ?></th>
          <th><?php echo $this->text('Filesize'); ?></th>
          <th><?php echo $this->text('Modified'); ?></th>
          <th><?php echo $this->text('Progress'); ?></th>
          <th><?php echo $this->text('Actions'); ?></th>
        </tr>
      </thead>
      <?php foreach ($files as $id => $file) { ?>
      <tr>
        <?php if ($access_actions) { ?>
        <td class="middle"><input type="checkbox" class="select-all" name="action[items][]" value="<?php echo $file['hash']; ?>"></td>
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
          <?php if($this->access('module_translator_download')) { ?>
          <ul class="list-inline">
            <li><a href="<?php echo $this->url('', array('download' => $file['hash'])); ?>"><?php echo $this->lower($this->text('Download')); ?></a></li>
            <li><a href="<?php echo $this->url("admin/tool/translator/{$language['code']}/view/{$file['hash']}", array('tab' => isset($_query['tab']) && $_query['tab'] === 'compiled' ? 'compiled' : '')); ?>"><?php echo $this->lower($this->text('View')); ?></a></li>
          </ul>
          <?php } ?>
        </td>
      </tr>
      <?php } ?>
    </table>
    <?php } ?>
  </div>
</form>
<style>
  .translation-files .progress {
      margin: 0;
  }
  .translation-files .progress .progress-bar {
      color: #333;
  }
</style>