<?php
/* Smarty version 3.1.31, created on 2021-09-15 13:05:19
  from "/var/www/html/formtools/themes/default/footer.tpl" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.31',
  'unifunc' => 'content_614227cf7963a4_47251130',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '642da14d58ef5bc0c448e8e00d41f3afe3b8db87' => 
    array (
      0 => '/var/www/html/formtools/themes/default/footer.tpl',
      1 => 1573338106,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_614227cf7963a4_47251130 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_function_show_page_load_time')) require_once '/var/www/html/formtools/global/smarty_plugins/function.show_page_load_time.php';
?>

      </div>
    </td>
  </tr>
  </table>

</div>


<?php if ($_smarty_tpl->tpl_vars['footer_text']->value != '' || $_smarty_tpl->tpl_vars['g_enable_benchmarking']->value) {?>
  <div class="footer">
    <?php echo $_smarty_tpl->tpl_vars['footer_text']->value;?>

    <?php echo smarty_function_show_page_load_time(array(),$_smarty_tpl);?>

  </div>
<?php }?>

</body>
</html>
<?php }
}
