<div class="fullWidth">
	<div class="innerWidth940">
		<h2>Call logs</h2>
        <br />
        
        <div class="paginatorContainer">
        	<?php
				echo $this->Paginator->prev('«', array('tag'=>'div'), null, null);
				echo $this->Paginator->numbers(array('tag'=>'div','separator'=>'','first'=>'123'));
                echo $this->Paginator->next('»', array('tag'=>'div'), null, null);
			?>
        </div><!--paginatorContainer-->
		<div class="clear"></div>
        <br />
        <table cellspacing="0" cellpadding="5" class="table table-bordered" id="tableHistory">
        	<thead>
            	<tr>
                    <th><span>ID</span></th>
                    <th><span>Número</span></th>
                    <th><span>Operadora</span></th>
                    <th><span>Data</span></th>
                    <th><span>Hora</span></th>
            	</tr>
            </thead>
            <tbody>
            	<?php
					foreach($history as $row){
						?>
                        	<tr>
                            	<td><span><?php echo $row['Controle']['id']?></span></td>
                                <td><span><?php echo $row['Controle']['numero']?></span></td>
                                <td><span><?php echo $row['Controle']['operadora']?></span></td>
                                <td><span><?php echo $row['Controle']['data']?></span></td>
                                <td><span><?php echo $row['Controle']['hora']?></span></td>
                            </tr>
                        <?php
					}
					
				?>
            </tbody>
        </table>
        
        
	</div><!--innerWidth940-->
</div><!--fullWidth-->
