<div class="fullWidth">
	<div class="innerWidth940">
		<h2>Lista de usu&aacute;rios</h2>
        <?php echo $this->Session->flash(); ?>
        <?php echo $this->Form->button('Novo',array('class'=>'btn btn-success','data-toggle'=>"modal", 'data-target'=>"#myModal")); ?>
        <div class="clear"></div>
        <br />
 		<table cellspacing="5" cellpadding="5" class="table table-bordered" id="tableUsers">
        	<thead>
            	<th><strong>ID</strong></th>
                <th><strong>Usu&aacute;rio</strong></th>
                <th><strong>Editar</strong></th>
            	<th><strong>Remover</strong></th>
            </thead>
            <tbody>
            	<?php
					foreach($users as $user){
						?>
                        	<tr>
                            	<td><?php echo $user['User']['user_id'] ?></td>
                                <td><?php echo $user['User']['username'] ?></td>
                                <td><?php echo $this->Form->button('Editar',array('class'=>'btn btn-warning','onclick'=>"edit_user('" . $user['User']['user_id'] . "','" . $user['User']['username'] . "')")); ?></td>
                                <td><?php echo $this->Form->button('Remover',array('class'=>'btn btn-danger','onclick'=>"removeUser('" . $user['User']['user_id'] . "')")); ?></td>
                            </tr>
                        <?php
					}
				?>
            </tbody>
        </table>
	</div><!--innerWidth940-->
</div><!--fullWidth-->


<!-- Modal -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
				<?php echo $this->Form->button('&times',array('class'=>'close','data-dismiss'=>"modal",'aria-hidden'=>"true")); ?>
                <h4 class="modal-title" id="myModalLabel" style="color:#000;">Novo Usuário</h4>
            </div>
        <div class="modal-body">
        	<?php echo $this->Form->create('new_user',array('url'=>array('controller'=>'Users','action'=>'new_user'))); ?>
            <div class="form-group">
            	<?php echo $this->Form->input('username',array('div'=>false,'placeholder'=>'Username','class'=>'form-control','label'=>'Usuario')); ?>
            </div>
            <div class="clear"></div>
            <div class="form-group">
            	<?php echo $this->Form->input('password',array('div'=>false,'placeholder'=>'Password','class'=>'form-control','type'=>'password','label'=>'Password')); ?>
            </div>
            <div class="clear"></div>
        </div>
        <div class="modal-footer">
        	<?php echo $this->Form->button('Close',array('class'=>'btn btn-default','data-dismiss'=>"modal",'aria-hidden'=>"true",'type'=>'button')); ?>
            <?php echo $this->Form->button('Save',array('class'=>'btn btn-success','aria-hidden'=>"true",'type'=>'submit')); ?>
        	<?php echo $this->Form->end() ?>
        </div>
        </div>
    </div>
</div>


<div class="modal fade" id="myModal2" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
				<?php echo $this->Form->button('&times',array('class'=>'close','data-dismiss'=>"modal",'aria-hidden'=>"true")); ?>
                <h4 class="modal-title" id="myModalLabel" style="color:#000;">Novo Usuário</h4>
            </div>
        <div class="modal-body">
        	<?php echo $this->Form->create('update_user',array('url'=>array('controller'=>'Users','action'=>'update_user'))); ?>
            <?php echo $this->Form->input('userID',array('type'=>'hidden')); ?>
            <div class="form-group">
            	<?php echo $this->Form->input('upd_username',array('div'=>false,'placeholder'=>'Username','class'=>'form-control','label'=>'Usuario')); ?>
            </div>
            <div class="clear"></div>
            <div class="form-group">
            	<?php echo $this->Form->input('upd_password',array('div'=>false,'placeholder'=>'Password','class'=>'form-control','type'=>'password','label'=>'Password')); ?>
            </div>
            <div class="clear"></div>
        </div>
        <div class="modal-footer">
        	<?php echo $this->Form->button('Close',array('class'=>'btn btn-default','data-dismiss'=>"modal",'aria-hidden'=>"true",'type'=>'button')); ?>
            <?php echo $this->Form->button('Update',array('class'=>'btn btn-success','aria-hidden'=>"true",'type'=>'submit')); ?>
        	<?php echo $this->Form->end() ?>
        </div>
        </div>
    </div>
</div>


<script>
	function removeUser(userID){
		if (confirm("Deseja remover usuário?")){
			window.location = "<?php echo $this->Html->url(array('controller'=>'Users','action'=>'remove_user'))?>/" + userID;
		}
	}
	
	function edit_user(userID,username){
		$("#update_userUpdUsername").val(username);
		$("#update_userUserID").val(userID);
		$('#myModal2').modal('show');
	}
</script>
