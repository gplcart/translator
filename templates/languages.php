<?php
/**
 * @package Translator
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2017, Iurii Makukh <gplcart.software@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0+
 * @var $this \gplcart\core\controllers\backend\Controller
 */
?>
<div class="row">
  <div class="col-md-12">
    <?php echo $this->text('Select language'); ?>:
    <ul>
      <?php foreach ($_languages as $code => $language) { ?>
      <li>
        <a href="<?php echo $this->url("admin/tool/translator/$code"); ?>">
          <?php echo $this->e($language['name']); ?>
        </a>
      </li>
      <?php } ?>
    </ul>
  </div>
</div>