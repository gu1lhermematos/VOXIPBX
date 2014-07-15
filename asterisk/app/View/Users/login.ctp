<div class="fullWidth">
	<div class="innerWidth940">
    	<br /><br /><br />
    	<center><h2>Login | Asterisk</h2></center>
        <br /><br />
        <?php echo $this->Session->flash(); ?>
    	<?php echo $this->Form->create('user_auth',array('url'=>array('controller'=>'Users','action'=>'do_auth'),'inputDefaults'=>array('label'=>false,'div'=>false))); ?>
        <table cellspacing="0" cellpadding="0" border="0">
        	<tr>
            	<td>
                	<?php echo $this->Form->input('username',array('class'=>'','placeholder'=>'Username'));  ?>
                </td>
            </tr>
            <tr>
            	<td>
                	<?php echo $this->Form->input('password',array('class'=>'','placeholder'=>'Password','type'=>'password'));  ?>
                </td>
            </tr>
            <tr>
            	<td align="left">
                	<br />
                	<?php echo $this->Form->button('Login',array('type'=>'submit','class'=>'btn btn-success')); ?>
                </td>
            </tr>
        </table>
        <?php echo $this->Form->end() ?>
    </div><!--innerWidth940-->
</div><!--fullWidth-->