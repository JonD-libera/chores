<?php
/* Smarty version 3.1.31, created on 2021-09-15 13:41:30
  from "/var/www/html/formtools/themes/default/tabset_open.tpl" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.31',
  'unifunc' => 'content_6142304ab9a988_88317522',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '3ec16a6af43a50b4b6889d220adaff42ed1c8624' => 
    array (
      0 => '/var/www/html/formtools/themes/default/tabset_open.tpl',
      1 => 1573338107,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6142304ab9a988_88317522 (Smarty_Internal_Template $_smarty_tpl) {
?>
<ul class="main_tabset">
    <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['tabs']->value, 'curr_tab', false, 'curr_tab_key');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['curr_tab_key']->value => $_smarty_tpl->tpl_vars['curr_tab']->value) {
?>
        
        <?php if ($_smarty_tpl->tpl_vars['curr_tab_key']->value == $_smarty_tpl->tpl_vars['page']->value || (isset($_smarty_tpl->tpl_vars['curr_tab']->value['pages']) && is_array($_smarty_tpl->tpl_vars['curr_tab']->value['pages']) && in_array($_smarty_tpl->tpl_vars['page']->value,$_smarty_tpl->tpl_vars['curr_tab']->value['pages'])) || (isset($_smarty_tpl->tpl_vars['tab_number']->value) && $_smarty_tpl->tpl_vars['tab_number']->value == $_smarty_tpl->tpl_vars['curr_tab_key']->value)) {?>
            <li class="selected"><a href="<?php echo $_smarty_tpl->tpl_vars['curr_tab']->value['tab_link'];?>
"><?php echo $_smarty_tpl->tpl_vars['curr_tab']->value['tab_label'];?>
</a></li>
        <?php } else { ?>
            <li><a href="<?php echo $_smarty_tpl->tpl_vars['curr_tab']->value['tab_link'];?>
"><?php echo $_smarty_tpl->tpl_vars['curr_tab']->value['tab_label'];?>
</a></li>
        <?php }?>
    <?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);
?>

</ul>

<div class="prevnext_links">
    <?php if (isset($_smarty_tpl->tpl_vars['show_tabset_nav_links']->value) && $_smarty_tpl->tpl_vars['show_tabset_nav_links']->value) {?>
        <?php $_smarty_tpl->_assignInScope('prev_label', (($tmp = @$_smarty_tpl->tpl_vars['prev_tabset_link_label']->value)===null||$tmp==='' ? $_smarty_tpl->tpl_vars['LANG']->value['word_previous_leftarrow'] : $tmp));
?>
        <?php if ($_smarty_tpl->tpl_vars['prev_tabset_link']->value) {?>
            <span><a href="<?php echo $_smarty_tpl->tpl_vars['prev_tabset_link']->value;?>
"><?php echo $_smarty_tpl->tpl_vars['prev_label']->value;?>
</a></span>
        <?php } else { ?>
            <span class="no_link"><?php echo $_smarty_tpl->tpl_vars['prev_label']->value;?>
</span>
        <?php }?>

        <?php $_smarty_tpl->_assignInScope('next_label', (($tmp = @$_smarty_tpl->tpl_vars['next_tabset_link_label']->value)===null||$tmp==='' ? $_smarty_tpl->tpl_vars['LANG']->value['word_next_rightarrow'] : $tmp));
?>
        <?php if ($_smarty_tpl->tpl_vars['next_tabset_link']->value) {?>
            <span><a href="<?php echo $_smarty_tpl->tpl_vars['next_tabset_link']->value;?>
"><?php echo $_smarty_tpl->tpl_vars['next_label']->value;?>
</a></span>
        <?php } else { ?>
            <span class="no_link"><?php echo $_smarty_tpl->tpl_vars['next_label']->value;?>
</span>
        <?php }?>
    <?php }?>
</div>

<div class="clear"></div>
<div class="tab_content">
<?php }
}
