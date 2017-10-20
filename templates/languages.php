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
  <?php foreach ($languages as $items) { ?>
  <div class="col-md-<?php echo 12 / count($languages); ?>">
    <ul class="list-unstyled">
      <?php foreach ($items as $code => $language) { ?>
      <li>
        <a href="<?php echo $this->url("admin/tool/translator/$code"); ?>">
          <?php echo $this->text($language['name']); ?> <small class="text-muted"><?php echo $this->e($code); ?></small>
        </a>
      </li>
      <?php } ?>
    </ul>
  </div>
  <?php } ?>
</div>
