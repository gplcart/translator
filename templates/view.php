<?php
/**
 * @package Translator
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2017, Iurii Makukh <gplcart.software@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0+
 * @var $this \gplcart\core\controllers\backend\Controller
 */
?>
<?php if (empty($strings)) { ?>
<?php echo $this->text('There are no items yet'); ?>
<?php } else { ?>
<ul class="nav nav-tabs">
  <?php if ($this->access('module_translator')) { ?>
  <li><a href="<?php echo $this->url("admin/tool/translator/{$language['code']}"); ?>"><?php echo $this->text('Original translations'); ?></a></li>
  <li><a href="<?php echo $this->url("admin/tool/translator/{$language['code']}", array('tab' => 'compiled')); ?>"><?php echo $this->text('Compiled translations'); ?></a></li>
  <?php } ?>
  <?php if ($this->access('module_translator_upload') && $this->access('file_upload')) { ?>
  <li><a href="<?php echo $this->url("admin/tool/translator/{$language['code']}/upload"); ?>"><?php echo $this->text('Upload'); ?></a></li>
  <?php } ?>
  <li class="active"><a><?php echo $this->text('View'); ?></a></li>
</ul>
<div class="tab-content">
  <table class="table table-condensed table-striped view-translation">
    <thead>
      <tr>
        <th>#</th>
        <th><?php echo $this->text('Source'); ?></th>
        <th><?php echo $this->text('Translation'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($strings as $pos => $string) { ?>
      <tr>
        <td class="middle"><?php echo $pos + 1; ?></td>
        <td><?php echo $this->e($string[0]); ?></td>
        <td class="active"><?php echo isset($string[1]) ? $this->e($string[1]) : ''; ?></td>
      </tr>
      <?php } ?>
    </tbody>
  </table>
</div>
<?php } ?>