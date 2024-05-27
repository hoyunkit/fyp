<?php

/**
 * Implementation of RemoveAccess view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for RemoveAccess view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_RemoveAccess extends SeedDMS_Bootstrap_Style
{

  function show()
  { /* {{{ */
    $access = $this->params['access'];

    $this->htmlStartPage(getMLText("admin_tools"));
    $this->globalNavigation();
    $this->contentStart();
    $this->pageNavigation(getMLText("admin_tools"), "admin_tools");
    $this->contentHeading(getMLText("rm_access"));
    $this->contentContainerStart();

?>
    <form action="../op/op.AccessMgr.php" name="form1" method="post">
      <input type="hidden" name="accessName" value="<?php print $access->getName(); ?>">
      <input type="hidden" name="action" value="dropaccess">
      <?php echo createHiddenFieldWithKey('dropaccess'); ?>
      <p>
        <?php printMLText("confirm_rm_access", array("accessname" => htmlspecialchars($access->getName()))); ?>
      </p>
      <p><button type="submit" class="btn"><i class="icon-remove"></i> <?php printMLText("rm_access"); ?></button></p>
    </form>
<?php
    $this->contentContainerEnd();
    $this->contentEnd();
    $this->htmlEndPage();
  } /* }}} */
}
?>