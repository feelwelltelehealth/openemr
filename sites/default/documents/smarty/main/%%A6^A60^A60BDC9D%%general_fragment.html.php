<?php /* Smarty version 2.6.31, created on 2021-05-11 01:08:45
         compiled from /var/www/localhost/htdocs/openemr/templates/prescription/general_fragment.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('function', 'xlt', '/var/www/localhost/htdocs/openemr/templates/prescription/general_fragment.html', 15, false),array('modifier', 'text', '/var/www/localhost/htdocs/openemr/templates/prescription/general_fragment.html', 28, false),)), $this); ?>
<table class="table">
    <?php if (empty ( $this->_tpl_vars['prescriptions'] )): ?>
    <tr class='text'>
        <td>&nbsp;&nbsp;<?php echo smarty_function_xlt(array('t' => 'None'), $this);?>
</td>
    </tr>
    <?php else: ?>
        <tr>
            <th><?php echo smarty_function_xlt(array('t' => 'Drug'), $this);?>
</th>
            <th>&nbsp;&nbsp;&nbsp;</th>
            <th><?php echo smarty_function_xlt(array('t' => 'Qty'), $this);?>
</th>
            <th><?php echo smarty_function_xlt(array('t' => 'Refills'), $this);?>
</th>
            <th><?php echo smarty_function_xlt(array('t' => 'Filled'), $this);?>
</th>
        </tr>
        <?php $_from = $this->_tpl_vars['prescriptions']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['prescription']):
?>
        <?php if ($this->_tpl_vars['prescription']->get_active() > 0): ?>
        <tr class='text'>
            <td><?php echo ((is_array($_tmp=$this->_tpl_vars['prescription']->drug)) ? $this->_run_mod_handler('text', true, $_tmp) : text($_tmp)); ?>
&nbsp;</td>
            <td><?php echo ((is_array($_tmp=$this->_tpl_vars['prescription']->get_size())) ? $this->_run_mod_handler('text', true, $_tmp) : text($_tmp)); ?>
<?php echo ((is_array($_tmp=$this->_tpl_vars['prescription']->get_unit_display())) ? $this->_run_mod_handler('text', true, $_tmp) : text($_tmp)); ?>
&nbsp;
                <?php echo ((is_array($_tmp=$this->_tpl_vars['prescription']->get_dosage_display())) ? $this->_run_mod_handler('text', true, $_tmp) : text($_tmp)); ?>
</td>
            <td><?php echo ((is_array($_tmp=$this->_tpl_vars['prescription']->get_quantity())) ? $this->_run_mod_handler('text', true, $_tmp) : text($_tmp)); ?>
</td>
            <td><?php echo ((is_array($_tmp=$this->_tpl_vars['prescription']->get_refills())) ? $this->_run_mod_handler('text', true, $_tmp) : text($_tmp)); ?>
</td>
            <td><?php echo ((is_array($_tmp=$this->_tpl_vars['prescription']->get_date_added())) ? $this->_run_mod_handler('text', true, $_tmp) : text($_tmp)); ?>
</td>
        </tr>
        <?php endif; ?>
        <?php endforeach; endif; unset($_from); ?>
    <?php endif; ?>
</table>