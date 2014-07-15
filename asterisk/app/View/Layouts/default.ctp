<!DOCTYPE html>
<html>
<head>
	<?php echo $this->Html->charset(); ?>
	<title>Asterisk</title>
	<?php
		echo $this->Html->meta('icon');

		echo $this->Html->css('bootstrap');
		echo $this->Html->css('bootstrap-theme');
		echo $this->Html->css('datepicker');
		echo $this->Html->css('main');
		
		echo $this->Html->script('jquery.1.11.0');
		echo $this->Html->script('bootstrap');
		echo $this->Html->script('highcharts');
		echo $this->Html->script('mootools-adapter');
		echo $this->Html->script('prototype-adapter');
		echo $this->Html->script('exporting');		
		echo $this->Html->script('bootstrap-datepicker');
		echo $this->Html->script('scripts');
				
		echo $this->fetch('meta');
		echo $this->fetch('css');
		echo $this->fetch('script');
	?>
</head>
<body>
	<?php 
	if (isset($thisUser)){
	?>
	<div id="header" class="fullWidth">
    	<div class="innerWidth940">
            <ul> 
                <li><?php echo $this->Html->link($this->Form->button('Dashboard',array('class'=>'btn btn-primary ' . ($page == "dashboard"? "active":""))),array('controller'=>'Dashboard','action'=>'dashboard'),array('escape'=>false))?></li>
                <li><?php echo $this->Html->link($this->Form->button('Gráficos',array('class'=>'btn btn-primary ' . ($page == "graphs"? "active":""))),array('controller'=>'Dashboard','action'=>'graphs'),array('escape'=>false))?></li>
                <li><?php echo $this->Html->link($this->Form->button('Histórico de Chamadas',array('class'=>'btn btn-primary ' . ($page == "call_history"? "active":""))),array('controller'=>'Dashboard','action'=>'call_history'),array('escape'=>false))?></li>
                <li><?php echo $this->Html->link($this->Form->button('Exportar Logs',array('class'=>'btn btn-primary ' . ($page == "log_export"? "active":""))),array('controller'=>'Dashboard','action'=>'log_export'),array('escape'=>false))?></li>
                <li><?php echo $this->Html->link($this->Form->button('Usu&aacute;rios',array('class'=>'btn btn-primary ' . ($page == "user_list"? "active":""))),array('controller'=>'Dashboard','action'=>'list_users'),array('escape'=>false))?></li>
		<li><?php echo $this->Html->link($this->Form->button('CDR',array('class'=>'btn btn-primary ' . ($page == "cdr"? "active":""))),array('controller'=>'Dashboard','action'=>'load_cdr'),array('escape'=>false))?></li>
		<li><?php echo $this->Html->link($this->Form->button('Sair',array('class'=>'btn btn-danger')),array('controller'=>'Users','action'=>'logout'),array('escape'=>false))?></li>
            </ul>
        </div><!--innerWidth940-->
    </div><!--header-->
  	<?php
	}
	?>
	<?php echo $this->fetch('content'); ?>
    
    
	<div id="footer" class="fullWidth">
    	<div class="innerWidth940">
        </div><!--inenrWidth940-->
    </div><!--footer-->
</body>
</html>
