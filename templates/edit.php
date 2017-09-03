<?php
/**
 * @package Translator
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2017, Iurii Makukh <gplcart.software@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0+
 * @var $this \gplcart\core\controllers\backend\Controller
 */
?>
<form method="post" class="form-horizontal">
  <input type="hidden" name="token" value="<?php echo $_token; ?>">
  <table class="table table-condensed table-hover edit-translations">
    <thead>
      <tr>
        <th>#</th>
        <th><?php echo $this->text('Source'); ?></th>
        <th><?php echo $this->text('Translation'); ?></th>
      </tr>
      <tr>
        <th></th>
        <th><input class="form-control" name="filter[0]" placeholder="<?php echo $this->text('Search in source'); ?>" value=""></th>
        <th><input class="form-control" name="filter[1]" placeholder="<?php echo $this->text('Search in translations'); ?>" value=""></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($strings as $pos => $string) { ?>
      <tr class="<?php echo isset($string[1]) && $string[1] !== '' ? 'bg-success' : 'bg-warning'; ?>">
        <td class="middle"><?php echo $pos + 1; ?></td>
        <td>
          <input class="form-control" name="strings[<?php echo $pos; ?>][0]" value="<?php echo $this->e($string[0]); ?>" readonly>
        </td>
        <td>
          <input class="form-control" name="strings[<?php echo $pos; ?>][1]" value="<?php echo isset($string[1]) ? $this->e($string[1]) : ''; ?>">
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>
  <div class="btn-toolbar">
    <a href="<?php echo $this->url("admin/tool/translator/{$language['code']}"); ?>" class="btn btn-default"><?php echo $this->text("Cancel"); ?></a>
    <button class="btn btn-default save" name="save" value="1"><?php echo $this->text('Save'); ?></button>
    <button class="btn btn-default save-refresh" name="save_refresh" value="1" onclick="return confirm(GplCart.text('Upon refreshing all the compiled translations for the language will be deleted and recreated again without changes you made'));"><?php echo $this->text('Save and refresh'); ?></button>
  </div>
</form>
